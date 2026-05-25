<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Actualiza rol/estado de usuarios desde el panel admin y valida reglas de seguridad.
require_admin($pdo);
require_post_request();
verify_csrf_token($_POST['_csrf'] ?? null);

try {
    $admin = current_user($pdo);
    update_user_admin_fields(
        $pdo,
        (int)($_POST['id'] ?? 0),
        (string)($_POST['role'] ?? 'user'),
        (string)($_POST['status'] ?? 'pending'),
        (int)$admin['id']
    );
    redirect('users.php');
} catch (Throwable $exception) {
    redirect('users.php?error=' . urlencode($exception->getMessage()));
}
