<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Cambia solo el estado del plan desde el listado, protegido por POST + CSRF.
require_login($pdo);
require_post_request();
verify_csrf_token($_POST['_csrf'] ?? null);

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? 'draft';
if ($id > 0) {
    update_plan_status($pdo, $id, $status);
}
redirect('index.php');
