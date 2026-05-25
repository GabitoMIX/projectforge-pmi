<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Endpoint de escritura del plan: exige sesion, POST y token CSRF antes de guardar.
require_login($pdo);
require_post_request();
verify_csrf_token($_POST['_csrf'] ?? null);

try {
    $post = process_logo_uploads($_POST, $_FILES);
    $id = create_or_update_plan($pdo, $post);
    redirect('view.php?id=' . $id);
} catch (Throwable $e) {
    http_response_code(422);
    echo '<link rel="stylesheet" href="assets/css/app.css">';
    echo '<main class="container"><div class="alert"><strong>No se pudo guardar:</strong> ' . e($e->getMessage()) . '</div>';
    echo '<a class="btn btn-outline" href="javascript:history.back()">Volver</a></main>';
}
