<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Formulario publico de alta: crea usuarios pendientes hasta aprobacion admin.
if (current_user($pdo) && current_user($pdo)['status'] === 'active') {
    redirect('index.php');
}

$error = '';
$success = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf_token($_POST['_csrf'] ?? null);

    try {
        register_user($pdo, $_POST);
        $success = true;
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
    <title>Solicitar usuario - ProjectForge PMI</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <h1>Solicitar usuario</h1>
    <p class="section-help">La cuenta queda pendiente hasta que un administrador la habilite.</p>

    <?php if ($success): ?>
        <div class="success">Solicitud creada. Espera a que un administrador active tu cuenta.</div>
        <a class="btn btn-outline full-button" href="login.php">Volver al inicio de sesion</a>
    <?php else: ?>
        <?php if ($error !== ''): ?>
            <div class="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <?= csrf_input() ?>
            <div class="form-group">
                <label>Nombre completo</label>
                <input name="full_name" required autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contrasena</label>
                <input type="password" name="password" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Confirmar contrasena</label>
                <input type="password" name="password_confirm" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary full-button" type="submit">Enviar solicitud</button>
        </form>
    <?php endif; ?>

    <p class="auth-switch">Ya tienes cuenta? <a href="login.php">Iniciar sesion</a></p>
</main>
</body>
</html>
