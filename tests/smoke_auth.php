<?php

// Prueba smoke de autenticacion: registro, aprobacion, password y rate limit.
require_once __DIR__ . '/../app/auth.php';

function fail(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$dsn = getenv('PROJECTFORGE_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=plan_calidad_mvp_test;charset=utf8mb4';
$user = getenv('PROJECTFORGE_TEST_DB_USER') ?: 'root';
$password = getenv('PROJECTFORGE_TEST_DB_PASSWORD') ?: '';

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$pdo->prepare("DELETE FROM login_attempts WHERE email IN ('admin.smoke@projectforge.local', 'user.smoke@projectforge.local', 'locked.smoke@projectforge.local') OR ip_address = '127.0.0.1'")->execute();
$pdo->prepare("DELETE FROM users WHERE email IN ('admin.smoke@projectforge.local', 'user.smoke@projectforge.local')")->execute();

$pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status, must_change_password, approved_at) VALUES (?, ?, ?, 'admin', 'active', 0, NOW())")->execute([
    'Admin Smoke',
    'admin.smoke@projectforge.local',
    password_hash('AdminSmoke123!', PASSWORD_DEFAULT),
]);
$adminId = (int)$pdo->lastInsertId();

try {
    register_user($pdo, [
        'full_name' => 'Usuario Debil',
        'email' => 'weak.smoke@projectforge.local',
        'password' => '1234567890',
        'password_confirm' => '1234567890',
    ]);
    fail('La contrasena debil no deberia ser aceptada.');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'mayuscula')) {
        fail('La validacion de contrasena debil devolvio un mensaje inesperado.');
    }
}

$userId = register_user($pdo, [
    'full_name' => 'Usuario Smoke',
    'email' => 'user.smoke@projectforge.local',
    'password' => 'UserSmoke123!',
    'password_confirm' => 'UserSmoke123!',
]);

$pending = find_user_by_id($pdo, $userId);
if (!$pending || $pending['status'] !== 'pending') {
    fail('El registro no quedo pendiente.');
}

try {
    authenticate_user($pdo, 'user.smoke@projectforge.local', 'UserSmoke123!');
    fail('Un usuario pendiente no deberia iniciar sesion.');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'pendiente')) {
        fail('El bloqueo de usuario pendiente devolvio un mensaje inesperado.');
    }
}

update_user_admin_fields($pdo, $userId, 'user', 'active', $adminId);

$logged = authenticate_user($pdo, 'user.smoke@projectforge.local', 'UserSmoke123!');
if ((int)$logged['id'] !== $userId || (int)($_SESSION['user_id'] ?? 0) !== $userId) {
    fail('El usuario activo no pudo iniciar sesion correctamente.');
}

update_user_admin_fields($pdo, $userId, 'user', 'disabled', $adminId);
$disabled = find_user_by_id($pdo, $userId);
if (!$disabled || $disabled['status'] !== 'disabled') {
    fail('No se pudo deshabilitar el usuario.');
}

$pdo->prepare("UPDATE users SET must_change_password = 1 WHERE id = ?")->execute([$adminId]);
change_user_password($pdo, $adminId, 'AdminSmoke123!', 'AdminSmoke456!', 'AdminSmoke456!');
$admin = find_user_by_id($pdo, $adminId);
if (!$admin || (int)$admin['must_change_password'] !== 0 || !password_verify('AdminSmoke456!', $admin['password_hash'])) {
    fail('No se pudo cambiar la contrasena obligatoria del administrador.');
}

for ($i = 0; $i < LOGIN_MAX_ATTEMPTS; $i++) {
    try {
        authenticate_user($pdo, 'locked.smoke@projectforge.local', 'bad-password');
    } catch (RuntimeException) {
        // Intento fallido esperado.
    }
}

try {
    authenticate_user($pdo, 'locked.smoke@projectforge.local', 'bad-password');
    fail('El rate limit de login no bloqueo los intentos repetidos.');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'Demasiados intentos')) {
        fail('El rate limit devolvio un mensaje inesperado.');
    }
}

$pdo->prepare("DELETE FROM login_attempts WHERE email IN ('admin.smoke@projectforge.local', 'user.smoke@projectforge.local', 'locked.smoke@projectforge.local') OR ip_address = '127.0.0.1'")->execute();
$pdo->prepare("DELETE FROM users WHERE email IN ('admin.smoke@projectforge.local', 'user.smoke@projectforge.local')")->execute();

echo "OK: registro, aprobacion, login, cambio de contrasena y rate limit verificados." . PHP_EOL;
