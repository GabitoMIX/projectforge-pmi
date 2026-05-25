<?php

/**
 * Carga comun para paginas publicas.
 *
 * Cada endpoint del MVP necesita conexion, helpers y funciones de persistencia.
 * Mantenerlo aqui reduce includes repetidos cuando se agreguen nuevos modulos.
 */

require_once __DIR__ . '/security.php';

send_security_headers();
start_secure_session();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logo_upload.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/auth.php';
