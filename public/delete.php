<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Elimina logicamente el plan para conservar trazabilidad en base de datos.
require_login($pdo);
require_post_request();
verify_csrf_token($_POST['_csrf'] ?? null);

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    soft_delete_plan($pdo, $id);
}
redirect('index.php');
