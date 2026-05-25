<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Permite cambiar contrasena y cumple el flujo obligatorio del admin inicial.
require_login($pdo);
$user = current_user($pdo);
$error = '';
$success = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf_token($_POST['_csrf'] ?? null);

    try {
        change_user_password(
            $pdo,
            (int)$user['id'],
            (string)($_POST['current_password'] ?? ''),
            (string)($_POST['new_password'] ?? ''),
            (string)($_POST['new_password_confirm'] ?? '')
        );
        $success = true;
        $user = current_user($pdo);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar contrasena - ProjectForge PMI</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <h1>Cambiar contrasena</h1>
    <p class="section-help">Usa una contrasena de minimo 10 caracteres con mayuscula, minuscula y numero.</p>

    <?php if (user_must_change_password($user)): ?>
        <div class="alert">Debes cambiar la contrasena inicial antes de continuar.</div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">Contrasena actualizada correctamente.</div>
        <a class="btn btn-primary full-button" href="index.php">Ir a planes</a>
    <?php else: ?>
        <?php if ($error !== ''): ?>
            <div class="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="change_password.php">
            <?= csrf_input() ?>
            <div class="form-group">
                <label>Contrasena actual</label>
                <input type="password" name="current_password" required autocomplete="current-password" autofocus>
            </div>
            <div class="form-group">
                <label>Nueva contrasena</label>
                <input type="password" name="new_password" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Confirmar nueva contrasena</label>
                <input type="password" name="new_password_confirm" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary full-button" type="submit">Actualizar contrasena</button>
        </form>
    <?php endif; ?>

    <?php if (!user_must_change_password($user)): ?>
        <p class="auth-switch"><a href="index.php">Volver a planes</a></p>
    <?php endif; ?>
</main>
</body>
</html>
