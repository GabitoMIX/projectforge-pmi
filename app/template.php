<?php

/**
 * Valores funcionales del formato EGPR230.
 *
 * Si despues se agregan otros documentos PMI, la idea es crear un archivo
 * equivalente por plantilla y no perseguir textos sueltos por cada vista.
 */

const QUALITY_PLAN_STATUSES = [
    'draft' => [
        'label' => 'Borrador',
        'class' => 'badge-gray',
    ],
    'in_progress' => [
        'label' => 'En proceso',
        'class' => 'badge-blue',
    ],
    'finalized' => [
        'label' => 'Finalizado',
        'class' => 'badge-green',
    ],
];

const NORMATIVE_DOCUMENT_TYPES = [
    'procedure' => 'Procedimientos',
    'template' => 'Plantillas',
    'format' => 'Formatos',
    'checklist' => 'Checklists',
    'other' => 'Otros documentos',
];

function quality_plan_statuses(): array
{
    return QUALITY_PLAN_STATUSES;
}

function normative_document_types(): array
{
    return NORMATIVE_DOCUMENT_TYPES;
}

function default_document_header(): array
{
    return [
        'header_left_logo' => '',
        'header_left_title' => 'ORGANIZACION',
        'header_left_subtitle' => 'Gestion de proyectos',
        'header_right_logo' => '',
        'header_right_title' => 'PLAN DE CALIDAD',
        'header_right_subtitle' => 'Documento configurable',
        'document_code' => 'Plan de Gestion de Calidad',
        'footer_note' => 'Documento generado por ProjectForge PMI.',
    ];
}

function default_quality_plan_versions(): array
{
    return [[
        'version_number' => '1.0',
        'made_by' => '',
        'reviewed_by' => '',
        'approved_by' => '',
        'version_date' => date('Y-m-d'),
        'reason' => 'Version inicial',
    ]];
}

function default_quality_baselines(): array
{
    return [[
        'quality_factor' => '',
        'quality_objective' => '',
        'metric' => '',
        'measurement_frequency' => '',
        'report_frequency' => '',
    ]];
}

function default_process_improvement_steps(): array
{
    return [
        ['step_number' => 1, 'description' => 'Delimitar el proceso'],
        ['step_number' => 2, 'description' => 'Determinar la oportunidad de mejora'],
        ['step_number' => 3, 'description' => 'Tomar informacion sobre el proceso'],
        ['step_number' => 4, 'description' => 'Analizar la informacion levantada'],
        ['step_number' => 5, 'description' => 'Definir las acciones correctivas para mejorar el proceso'],
        ['step_number' => 6, 'description' => 'Aplicar las acciones correctivas'],
        ['step_number' => 7, 'description' => 'Verificar si las acciones correctivas han sido efectivas'],
        ['step_number' => 8, 'description' => 'Estandarizar las mejoras logradas para hacerlas parte del proceso'],
    ];
}

function default_quality_activity_matrix(): array
{
    return [[
        'work_package' => '',
        'quality_standard' => '',
        'prevention_activities' => '',
        'control_activities' => '',
    ]];
}

function default_quality_roles(): array
{
    return [[
        'role_name' => '',
        'role_objectives' => '',
        'role_functions' => '',
        'authority_level' => '',
        'reports_to' => '',
        'supervises' => '',
        'knowledge_requirements' => '',
        'skill_requirements' => '',
        'experience_requirements' => '',
    ]];
}

function default_quality_organization(): array
{
    return [
        ['item_name' => 'SPONSOR', 'parent_name' => ''],
        ['item_name' => 'Comite de Control de Cambios', 'parent_name' => 'SPONSOR'],
        ['item_name' => 'PROJECT MANAGER', 'parent_name' => 'SPONSOR'],
        ['item_name' => 'EQUIPO DE PROYECTO', 'parent_name' => 'PROJECT MANAGER'],
    ];
}
