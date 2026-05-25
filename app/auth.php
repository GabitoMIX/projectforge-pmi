<?php

require_once __DIR__ . '/helpers.php';

/**
 * Autenticacion y autorizacion del MVP.
 *
 * Roles:
 * - admin: gestiona planes y aprueba/deshabilita usuarios.
 * - user: gestiona planes cuando su cuenta esta activa.
 *
 * Los usuarios registrados quedan pendientes hasta que un admin los active.
 */

const USER_ROLES = [
    'admin' => 'Administrador',
    'user' => 'Usuario',
];

const USER_STATUSES = [
    'pending' => [
        'label' => 'Pendiente',
        'class' => 'badge-yellow',
    ],
    'active' => [
        'label' => 'Activo',
        'class' => 'badge-green',
    ],
    'disabled' => [
        'label' => 'Deshabilitado',
        'class' => 'badge-red',
    ],
];

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_MINUTES = 15;

function user_roles(): array
{
    return USER_ROLES;
}

function user_statuses(): array
{
    return USER_STATUSES;
}

function user_status_label(string $status): string
{
    return USER_STATUSES[$status]['label'] ?? 'Sin estado';
}

function user_status_class(string $status): string
{
    return USER_STATUSES[$status]['class'] ?? 'badge-gray';
}

function find_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function current_user(PDO $pdo): ?array
{
    $userId = (int)($_SESSION['user_id'] ?? 0);

    return $userId > 0 ? find_user_by_id($pdo, $userId) : null;
}

function is_admin(PDO $pdo): bool
{
    $user = current_user($pdo);

    return $user !== null && $user['role'] === 'admin' && $user['status'] === 'active';
}

function require_login(PDO $pdo): void
{
    $user = current_user($pdo);

    if (!$user) {
        redirect('login.php');
    }

    if ($user['status'] !== 'active') {
        logout_user();
        redirect('login.php?inactive=1');
    }

    if (user_must_change_password($user) && !is_password_change_route()) {
        redirect('change_password.php');
    }
}

function require_admin(PDO $pdo): void
{
    require_login($pdo);

    if (!is_admin($pdo)) {
        http_response_code(403);
        echo '<link rel="stylesheet" href="assets/css/app.css">';
        echo '<main class="container"><div class="alert"><strong>Acceso denegado:</strong> esta seccion es solo para administradores.</div>';
        echo '<a class="btn btn-outline" href="index.php">Volver</a></main>';
        exit;
    }
}

function authenticate_user(PDO $pdo, string $email, string $password): array
{
    $email = strtolower(trim($email));
    assert_login_not_rate_limited($pdo, $email);

    $user = find_user_by_email($pdo, $email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_login_attempt($pdo, $email, false);
        throw new RuntimeException('Correo o contrasena incorrectos.');
    }

    if ($user['status'] === 'pending') {
        record_login_attempt($pdo, $email, false);
        throw new RuntimeException('Tu cuenta aun esta pendiente de aprobacion por un administrador.');
    }

    if ($user['status'] !== 'active') {
        record_login_attempt($pdo, $email, false);
        throw new RuntimeException('Tu cuenta esta deshabilitada. Contacta al administrador.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    record_login_attempt($pdo, $email, true);
    clear_failed_login_attempts($pdo, $email);

    $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?');
    $stmt->execute([(int)$user['id']]);

    return $user;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function register_user(PDO $pdo, array $data): int
{
    $fullName = trim((string)($data['full_name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    $passwordConfirm = (string)($data['password_confirm'] ?? '');

    if ($fullName === '' || strlen($fullName) < 3) {
        throw new RuntimeException('El nombre debe tener al menos 3 caracteres.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Escribe un correo valido.');
    }

    if (strlen($email) > 190) {
        throw new RuntimeException('El correo es demasiado largo.');
    }

    if (strlen($password) < 8) {
        throw new RuntimeException('La contrasena debe tener al menos 8 caracteres.');
    }

    if ($password !== $passwordConfirm) {
        throw new RuntimeException('Las contrasenas no coinciden.');
    }

    validate_password_strength($password);

    if (find_user_by_email($pdo, $email)) {
        throw new RuntimeException('Ya existe un usuario con ese correo.');
    }

    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $fullName,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        'user',
        'pending',
    ]);

    return (int)$pdo->lastInsertId();
}

function change_user_password(PDO $pdo, int $userId, string $currentPassword, string $newPassword, string $newPasswordConfirm): void
{
    $user = find_user_by_id($pdo, $userId);
    if (!$user) {
        throw new RuntimeException('Usuario no encontrado.');
    }

    if (!password_verify($currentPassword, $user['password_hash'])) {
        throw new RuntimeException('La contrasena actual no es correcta.');
    }

    if ($newPassword !== $newPasswordConfirm) {
        throw new RuntimeException('Las contrasenas nuevas no coinciden.');
    }

    validate_password_strength($newPassword);

    if (password_verify($newPassword, $user['password_hash'])) {
        throw new RuntimeException('La nueva contrasena debe ser diferente a la actual.');
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?');
    $stmt->execute([
        password_hash($newPassword, PASSWORD_DEFAULT),
        $userId,
    ]);
}

function validate_password_strength(string $password): void
{
    if (strlen($password) < 10) {
        throw new RuntimeException('La contrasena debe tener al menos 10 caracteres.');
    }

    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        throw new RuntimeException('La contrasena debe incluir mayuscula, minuscula y numero.');
    }
}

function user_must_change_password(array $user): bool
{
    return (int)($user['must_change_password'] ?? 0) === 1;
}

function is_password_change_route(): bool
{
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));

    return in_array($script, ['change_password.php', 'logout.php'], true);
}

function assert_login_not_rate_limited(PDO $pdo, string $email): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE success = 0
           AND created_at >= (NOW() - INTERVAL ' . LOGIN_WINDOW_MINUTES . ' MINUTE)
           AND (email = ? OR ip_address = ?)'
    );
    $stmt->execute([$email, client_ip()]);

    if ((int)$stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS) {
        throw new RuntimeException('Demasiados intentos fallidos. Espera 15 minutos e intenta de nuevo.');
    }
}

function record_login_attempt(PDO $pdo, string $email, bool $success): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)');
    $stmt->execute([$email, client_ip(), $success ? 1 : 0]);
}

function clear_failed_login_attempts(PDO $pdo, string $email): void
{
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE success = 0 AND (email = ? OR ip_address = ?)');
    $stmt->execute([$email, client_ip()]);
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 45);
}

function list_users(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM users ORDER BY status = "pending" DESC, created_at DESC, id DESC');

    return $stmt->fetchAll();
}

function update_user_admin_fields(PDO $pdo, int $targetUserId, string $role, string $status, int $adminId): void
{
    if (!array_key_exists($role, USER_ROLES)) {
        throw new RuntimeException('Rol invalido.');
    }

    if (!array_key_exists($status, USER_STATUSES)) {
        throw new RuntimeException('Estado de usuario invalido.');
    }

    $target = find_user_by_id($pdo, $targetUserId);
    if (!$target) {
        throw new RuntimeException('Usuario no encontrado.');
    }

    if ((int)$target['id'] === $adminId && ($role !== 'admin' || $status !== 'active')) {
        throw new RuntimeException('No puedes quitarte a ti mismo el acceso de administrador activo.');
    }

    if ($target['role'] === 'admin' && ($role !== 'admin' || $status !== 'active') && count_active_admins($pdo) <= 1) {
        throw new RuntimeException('Debe existir al menos un administrador activo.');
    }

    $approvedAt = $status === 'active' ? 'NOW()' : 'NULL';
    $approvedBy = $status === 'active' ? '?' : 'NULL';
    $params = [$role, $status];

    if ($status === 'active') {
        $params[] = $adminId;
    }

    $params[] = $targetUserId;

    $stmt = $pdo->prepare("UPDATE users SET role = ?, status = ?, approved_at = {$approvedAt}, approved_by = {$approvedBy}, updated_at = NOW() WHERE id = ?");
    $stmt->execute($params);
}

function count_active_admins(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");

    return (int)$stmt->fetchColumn();
}

function auth_toolbar(PDO $pdo): string
{
    $user = current_user($pdo);
    if (!$user) {
        return '';
    }

    $html = '<span class="user-chip">' . e($user['full_name']) . ' (' . e(USER_ROLES[$user['role']] ?? $user['role']) . ')</span>';
    $html .= '<a class="btn btn-outline" href="change_password.php">Cambiar contrasena</a>';

    if ($user['role'] === 'admin') {
        $html .= '<a class="btn btn-outline" href="users.php">Usuarios</a>';
    }

    $html .= '<form action="logout.php" method="post" class="inline-form">';
    $html .= csrf_input();
    $html .= '<button class="btn btn-outline" type="submit">Cerrar sesion</button>';
    $html .= '</form>';

    return $html;
}
