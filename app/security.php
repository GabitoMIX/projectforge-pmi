<?php

/**
 * Seguridad transversal para las paginas publicas.
 *
 * La autenticacion vive en app/auth.php. Este archivo se concentra en defensas
 * transversales: CSRF, sniffing de contenido, frames y cookies de sesion.
 */

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    /*
     * La app aun usa algunos onclick y estilos inline para mantener el MVP
     * simple. Por eso la CSP permite inline en script/style, pero cierra el
     * resto a recursos del mismo sitio y evita formularios hacia dominios
     * externos.
     */
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "img-src 'self' data:; " .
        "style-src 'self' 'unsafe-inline'; " .
        "script-src 'self' 'unsafe-inline'; " .
        "base-uri 'self'; form-action 'self'; frame-ancestors 'self'"
    );
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="_csrf" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function require_post_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo 'Metodo no permitido.';
        exit;
    }
}

function verify_csrf_token(?string $token): void
{
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        echo 'Sesion expirada o solicitud no valida. Vuelve al formulario e intenta otra vez.';
        exit;
    }
}
