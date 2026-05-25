<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Panel privado de administracion de cuentas y aprobaciones.
require_admin($pdo);
$users = list_users($pdo);
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios - ProjectForge PMI</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<header class="app-header">
    <div class="brand">Usuarios</div>
    <div class="toolbar">
        <a class="btn btn-outline" href="index.php">Volver a planes</a>
        <?= auth_toolbar($pdo) ?>
    </div>
</header>
<main class="container">
    <section class="card">
        <h1 class="section-title">Administrar usuarios</h1>
        <p class="section-help">Los usuarios nuevos quedan pendientes. Activalos solo cuando reconozcas la solicitud.</p>

        <?php if ($error !== ''): ?>
            <div class="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <table class="table-list">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Ultimo ingreso</th>
                    <th>Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= e($user['full_name']) ?></strong></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e(user_roles()[$user['role']] ?? $user['role']) ?></td>
                        <td><span class="badge <?= user_status_class($user['status']) ?>"><?= e(user_status_label($user['status'])) ?></span></td>
                        <td><?= e($user['last_login_at'] ?? 'Nunca') ?></td>
                        <td>
                            <form class="user-admin-form" action="user_status.php" method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= e($user['id']) ?>">
                                <select name="role">
                                    <?php foreach (user_roles() as $roleValue => $roleLabel): ?>
                                        <option value="<?= e($roleValue) ?>" <?= $user['role'] === $roleValue ? 'selected' : '' ?>><?= e($roleLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="status">
                                    <?php foreach (user_statuses() as $statusValue => $status): ?>
                                        <option value="<?= e($statusValue) ?>" <?= $user['status'] === $statusValue ? 'selected' : '' ?>><?= e($status['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-green" type="submit">Guardar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
