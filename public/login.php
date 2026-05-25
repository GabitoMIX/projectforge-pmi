<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Entrada publica al sistema. Valida credenciales y aplica rate limit desde auth.php.
if (current_user($pdo) && current_user($pdo)['status'] === 'active') {
    redirect('index.php');
}

$error = '';
$inactive = isset($_GET['inactive']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf_token($_POST['_csrf'] ?? null);

    try {
        authenticate_user($pdo, $_POST['email'] ?? '', $_POST['password'] ?? '');
        redirect('index.php');
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
    <title>Iniciar sesion - ProjectForge PMI</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <h1>Iniciar sesion</h1>
    <p class="section-help">Entra con una cuenta activa para gestionar planes de calidad.</p>

    <?php if ($inactive): ?>
        <div class="alert">Tu sesion fue cerrada porque la cuenta ya no esta activa.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <?= csrf_input() ?>
        <div class="form-group">
            <label>Correo</label>
            <input type="email" name="email" required autocomplete="email" autofocus>
        </div>
        <div class="form-group">
            <label>Contrasena</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary full-button" type="submit">Entrar</button>
    </form>

    <p class="auth-switch">No tienes cuenta? <a href="register.php">Solicitar registro</a></p>
</main>
</body>
</html>
