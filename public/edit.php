<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Carga un plan existente y reutiliza el mismo formulario de creacion/edicion.
require_login($pdo);
$id = (int)($_GET['id'] ?? 0);
$plan = get_full_plan($pdo, $id);
if (!$plan) {
    http_response_code(404);
    echo 'Plan no encontrado';
    exit;
}
$isEdit = true;
$pageTitle = 'Editar Plan de Gestión de Calidad';
include __DIR__ . '/form.php';
