<?php

require_once __DIR__ . '/Database.php';

// Crea la conexion PDO global que usan las paginas publicas despues del bootstrap.
try {
    $pdo = Database::getConnection();
} catch (PDOException $exception) {
    http_response_code(500);
    echo '<h1>Error de conexion a la base de datos</h1>';
    echo '<p>Revisa app/config.php y valida que MySQL este activo.</p>';
    echo '<pre>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}
