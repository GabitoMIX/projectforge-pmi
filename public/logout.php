<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Cierre de sesion por POST para evitar enlaces externos que fuercen logout.
require_post_request();
verify_csrf_token($_POST['_csrf'] ?? null);
logout_user();
redirect('login.php');
