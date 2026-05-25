<?php

// Prueba smoke del repositorio: crea, lee, actualiza estado y borra logicamente.
require_once __DIR__ . '/../app/repository.php';

function fail(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

$dsn = getenv('PROJECTFORGE_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=plan_calidad_mvp_test;charset=utf8mb4';
$user = getenv('PROJECTFORGE_TEST_DB_USER') ?: 'root';
$password = getenv('PROJECTFORGE_TEST_DB_PASSWORD') ?: '';

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$planId = create_or_update_plan($pdo, [
    'project_name' => 'Proyecto Smoke Test',
    'project_acronym' => 'PST',
    'header_left_logo' => '',
    'header_left_title' => 'ACME PMO',
    'header_left_subtitle' => 'Oficina de proyectos',
    'header_right_logo' => '',
    'header_right_title' => 'CALIDAD',
    'header_right_subtitle' => 'Documento interno',
    'document_code' => 'QA-001',
    'footer_note' => 'Pie configurable de prueba.',
    'quality_policy' => 'Politica de calidad de prueba.',
    'assurance_approach' => 'Aseguramiento de prueba.',
    'control_approach' => 'Control de prueba.',
    'improvement_approach_intro' => 'Mejora de prueba.',
    'status' => 'draft',
    'versions' => [[
        'version_number' => '1.0',
        'made_by' => 'QA',
        'reviewed_by' => 'PM',
        'approved_by' => 'SP',
        'version_date' => '2026-05-23',
        'reason' => 'Prueba inicial',
    ]],
    'baselines' => [[
        'quality_factor' => 'Performance',
        'quality_objective' => 'CPI >= 0.95',
        'metric' => 'CPI acumulado',
        'measurement_frequency' => 'Semanal',
        'report_frequency' => 'Semanal',
    ]],
    'steps' => default_process_improvement_steps(),
    'activities' => [[
        'work_package' => '1.1 Entregable',
        'quality_standard' => 'Estandar interno',
        'prevention_activities' => 'Revision temprana',
        'control_activities' => 'Aprobacion final',
    ]],
    'roles' => [[
        'role_name' => 'Sponsor',
        'role_objectives' => 'Aprobar la calidad.',
        'role_functions' => 'Revisar resultados.',
        'authority_level' => 'Alto',
        'reports_to' => 'Direccion',
        'supervises' => 'Project Manager',
        'knowledge_requirements' => 'Gestion',
        'skill_requirements' => 'Liderazgo',
        'experience_requirements' => 'Experiencia en proyectos',
    ]],
    'organization' => default_quality_organization(),
    'documents' => [
        'procedure' => ['Procedimiento de calidad'],
        'template' => ['Plantilla de metricas'],
        'format' => ['Formato de reporte'],
        'checklist' => ['Checklist de calidad'],
    ],
]);

if ($planId <= 0) {
    fail('No se creo el plan.');
}

$plan = get_full_plan($pdo, $planId);
if (!$plan) {
    fail('No se pudo leer el plan creado.');
}

if ($plan['header_left_title'] !== 'ACME PMO' || $plan['document_code'] !== 'QA-001') {
    fail('El encabezado configurable no se guardo correctamente.');
}

if (count($plan['versions']) !== 1 || count($plan['baselines']) !== 1 || count($plan['roles']) !== 1) {
    fail('Las relaciones principales no se guardaron correctamente.');
}

update_plan_status($pdo, $planId, 'finalized');
$updated = find_plan($pdo, $planId);
if (!$updated || $updated['status'] !== 'finalized') {
    fail('No se pudo actualizar el estado.');
}

soft_delete_plan($pdo, $planId);
if (find_plan($pdo, $planId) !== null) {
    fail('El borrado logico no oculto el plan.');
}

echo "OK: CRUD y encabezado configurable verificados." . PHP_EOL;
