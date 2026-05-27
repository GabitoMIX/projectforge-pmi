<?php
// Configuración de ProjectForge PMI.
// Compatible con XAMPP/local y con Docker/Dokploy mediante variables de entorno.

function env_value(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

return [
    'db' => [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => env_value('DB_PORT', '3306'),
        'database' => env_value('DB_DATABASE', 'plan_calidad_mvp'),
        'username' => env_value('DB_USERNAME', 'root'),
        'password' => env_value('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => env_value('APP_NAME', 'MVP Plan de Gestión de Calidad'),
        'base_url' => env_value('APP_BASE_URL', ''),
    ],
];
