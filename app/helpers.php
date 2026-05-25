<?php

require_once __DIR__ . '/template.php';

/**
 * Utilidades pequenas compartidas por vistas, controladores y repositorio.
 *
 * Mantener estas funciones aqui evita repetir escapes HTML, redirecciones y
 * validaciones de filas en cada archivo publico.
 */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function text(?string $value): string
{
    return nl2br(e($value));
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function is_valid_status(string $status): bool
{
    return array_key_exists($status, quality_plan_statuses());
}

function status_label(string $status): string
{
    return quality_plan_statuses()[$status]['label'] ?? 'Sin estado';
}

function status_class(string $status): string
{
    return quality_plan_statuses()[$status]['class'] ?? 'badge-gray';
}

function arr(array $data, string $key, mixed $default = ''): mixed
{
    return $data[$key] ?? $default;
}

function normalize_rows(?array $rows): array
{
    if (!$rows) {
        return [];
    }

    return array_values($rows);
}

function row_has_any_value(array $row, array $fields): bool
{
    foreach ($fields as $field) {
        if (trim((string) ($row[$field] ?? '')) !== '') {
            return true;
        }
    }

    return false;
}

function require_row_fields(array $row, array $fields, string $section, int $index): void
{
    foreach ($fields as $field => $label) {
        if (trim((string) ($row[$field] ?? '')) === '') {
            throw new RuntimeException(sprintf(
                '%s, fila %d: el campo "%s" es obligatorio.',
                $section,
                $index + 1,
                $label
            ));
        }
    }
}
