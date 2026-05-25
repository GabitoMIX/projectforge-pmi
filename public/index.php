<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Pantalla principal privada: lista los planes activos y concentra acciones rapidas.
require_login($pdo);
$plans = get_all_plans($pdo);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planes de Gestión de Calidad</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<header class="app-header">
    <div class="brand">Plan de Gestión de Calidad</div>
    <div class="toolbar">
        <a class="btn btn-primary" href="create.php">+ Crear plan</a>
        <?= auth_toolbar($pdo) ?>
    </div>
</header>
<main class="container">
    <section class="card">
        <h1 class="section-title">Planes registrados</h1>
        <p class="section-help">MVP en PHP puro + MySQL para crear, editar, eliminar, cambiar estado y visualizar el documento tipo PDF.</p>

        <?php if (!$plans): ?>
            <p>No hay planes registrados todavía.</p>
            <a class="btn btn-primary" href="create.php">Crear el primer plan</a>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Proyecto</th>
                        <th>Siglas</th>
                        <th>Estado</th>
                        <th>Actualizado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?= e($plan['id']) ?></td>
                            <td><strong><?= e($plan['project_name']) ?></strong></td>
                            <td><?= e($plan['project_acronym']) ?></td>
                            <td><span class="badge <?= status_class($plan['status']) ?>"><?= status_label($plan['status']) ?></span></td>
                            <td><?= e($plan['updated_at']) ?></td>
                            <td>
                                <div class="toolbar">
                                    <a class="btn btn-sm btn-outline" href="view.php?id=<?= e($plan['id']) ?>">Ver</a>
                                    <a class="btn btn-sm btn-outline" href="edit.php?id=<?= e($plan['id']) ?>">Editar</a>
                                    <form action="status.php" method="post" style="display:inline-flex; gap:6px; align-items:center;">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= e($plan['id']) ?>">
                                        <select name="status" style="padding:6px; border-radius:8px; font-size:12px;">
                                            <?php foreach (quality_plan_statuses() as $value => $status): ?>
                                                <option value="<?= e($value) ?>" <?= $plan['status'] === $value ? 'selected' : '' ?>><?= e($status['label']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-green" type="submit">Cambiar</button>
                                    </form>
                                    <form action="delete.php" method="post" onsubmit="return confirmDelete();">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= e($plan['id']) ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<script src="assets/js/app.js"></script>
</body>
</html>
