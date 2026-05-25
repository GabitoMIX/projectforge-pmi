<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Prepara un plan vacio con los valores por defecto de la plantilla.
require_login($pdo);
$defaultHeader = default_document_header();
$plan = [
    'id' => '',
    'project_name' => '',
    'project_acronym' => '',
    'header_left_logo' => $defaultHeader['header_left_logo'],
    'header_left_title' => $defaultHeader['header_left_title'],
    'header_left_subtitle' => $defaultHeader['header_left_subtitle'],
    'header_right_logo' => $defaultHeader['header_right_logo'],
    'header_right_title' => $defaultHeader['header_right_title'],
    'header_right_subtitle' => $defaultHeader['header_right_subtitle'],
    'document_code' => $defaultHeader['document_code'],
    'footer_note' => $defaultHeader['footer_note'],
    'quality_policy' => '',
    'assurance_approach' => '',
    'control_approach' => '',
    'improvement_approach_intro' => 'Cada vez que se requiera mejorar un proceso se seguira lo siguiente:',
    'status' => 'draft',
    'versions' => [],
    'baselines' => [],
    'steps' => [],
    'activities' => [],
    'roles' => [],
    'organization' => [],
    'documents' => [],
];
$isEdit = false;
$pageTitle = 'Crear Plan de Gestión de Calidad';
include __DIR__ . '/form.php';
