<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Vista imprimible del plan. El navegador puede guardarla como PDF.
require_login($pdo);
$id = (int)($_GET['id'] ?? 0);
$plan = get_full_plan($pdo, $id);
if (!$plan) {
    http_response_code(404);
    echo 'Plan no encontrado';
    exit;
}

$docTypes = normative_document_types();
$groupedDocs = [];
foreach ($docTypes as $key => $label) $groupedDocs[$key] = [];
foreach ($plan['documents'] as $doc) {
    if (isset($groupedDocs[$doc['document_type']])) {
        $groupedDocs[$doc['document_type']][] = $doc['document_name'];
    }
}
foreach ($docTypes as $key => $label) while (count($groupedDocs[$key]) < 4) $groupedDocs[$key][] = '';

function plan_header_value(array $plan, string $key): string
{
    $defaults = default_document_header();
    $value = trim((string) ($plan[$key] ?? ''));

    return $value !== '' ? $value : (string) ($defaults[$key] ?? '');
}

function doc_logo(?string $src, string $fallbackText): void
{
    $src = normalize_logo_public_path($src);
    if ($src !== '') { ?>
        <img class="doc-logo-img" src="<?= e($src) ?>" alt="<?= e($fallbackText) ?>">
    <?php } else { ?>
        <div class="mark"><?= e(strtoupper(substr($fallbackText, 0, 1)) ?: 'P') ?></div>
    <?php }
}

function doc_header(array $plan): void
{
    $leftTitle = plan_header_value($plan, 'header_left_title');
    $leftSubtitle = plan_header_value($plan, 'header_left_subtitle');
    $rightTitle = plan_header_value($plan, 'header_right_title');
    $rightSubtitle = plan_header_value($plan, 'header_right_subtitle');
    ?>
    <div class="doc-header">
        <div>
            <div class="logo-left">
                <?php doc_logo(plan_header_value($plan, 'header_left_logo'), $leftTitle); ?>
                <div><?= e($leftTitle) ?><small><?= e($leftSubtitle) ?></small></div>
            </div>
        </div>
        <div>
            <div class="logo-right">
                <?php if (plan_header_value($plan, 'header_right_logo') !== ''): ?>
                    <?php doc_logo(plan_header_value($plan, 'header_right_logo'), $rightTitle); ?>
                <?php endif; ?>
                <span><?= e($rightTitle) ?></span>
                <small><?= e($rightSubtitle) ?></small>
            </div>
            <div class="version-code"><?= e(plan_header_value($plan, 'document_code')) ?></div>
        </div>
    </div>
<?php }
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista previa - <?= e($plan['project_name']) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/document.css">
</head>
<body class="document-body">
<div class="print-toolbar">
    <div>
        <strong><?= e($plan['project_name']) ?></strong><br>
        <span class="badge <?= status_class($plan['status']) ?>"><?= status_label($plan['status']) ?></span>
    </div>
    <div class="toolbar">
        <a class="btn btn-outline" href="index.php">Volver</a>
        <a class="btn btn-outline" href="edit.php?id=<?= e($plan['id']) ?>">Editar</a>
        <button class="btn btn-primary" onclick="window.print()">Imprimir / guardar PDF</button>
        <?= auth_toolbar($pdo) ?>
    </div>
</div>

<section class="page">
    <div class="status-watermark">Estado: <?= e(status_label($plan['status'])) ?></div>
    <?php doc_header($plan); ?>

    <table class="quality-table">
        <tr class="dark-row"><th colspan="6" class="center">CONTROL DE VERSIONES</th></tr>
        <tr>
            <th>Versión</th><th>Hecha por</th><th>Revisada por</th><th>Aprobada por</th><th>Fecha</th><th>Motivo</th>
        </tr>
        <?php foreach ($plan['versions'] as $v): ?>
            <tr class="center">
                <td><?= e($v['version_number']) ?></td>
                <td><?= e($v['made_by']) ?></td>
                <td><?= e($v['reviewed_by']) ?></td>
                <td><?= e($v['approved_by']) ?></td>
                <td><?= e($v['version_date']) ?></td>
                <td><?= e($v['reason']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h1 class="document-title">PLAN DE GESTION DE LA CALIDAD</h1>

    <table class="quality-table">
        <tr><th>NOMBRE DEL PROYECTO</th><th>SIGLAS DEL PROYECTO</th></tr>
        <tr class="center bold"><td><?= e($plan['project_name']) ?></td><td><?= e($plan['project_acronym']) ?></td></tr>
    </table>

    <table class="quality-table">
        <tr class="dark-row">
            <th>POLÍTICA DE CALIDAD DEL PROYECTO:<span class="section-subtitle"> ESPECIFICAR LA INTENCIÓN DE DIRECCIÓN QUE FORMALMENTE TIENE EL EQUIPO DE PROYECTO CON RELACIÓN A LA CALIDAD DEL PROYECTO.</span></th>
        </tr>
        <tr><td><?= text($plan['quality_policy']) ?></td></tr>
    </table>

    <table class="quality-table">
        <tr class="dark-row">
            <th colspan="5">LÍNEA BASE DE CALIDAD DEL PROYECTO:<span class="section-subtitle"> ESPECIFICAR LOS FACTORES DE CALIDAD RELEVANTES PARA EL PRODUCTO DEL PROYECTO Y PARA LA GESTIÓN DEL PROYECTO. PARA CADA FACTOR DEFINIR OBJETIVOS, MÉTRICAS, Y FRECUENCIAS.</span></th>
        </tr>
        <tr>
            <th>FACTOR DE CALIDAD RELEVANTE</th>
            <th>OBJETIVO DE CALIDAD</th>
            <th>MÉTRICA A UTILIZAR</th>
            <th>FRECUENCIA Y MOMENTO DE MEDICIÓN</th>
            <th>FRECUENCIA Y MOMENTO DE REPORTE</th>
        </tr>
        <?php foreach ($plan['baselines'] as $b): ?>
            <tr>
                <td class="center"><?= text($b['quality_factor']) ?></td>
                <td class="center"><?= text($b['quality_objective']) ?></td>
                <td><?= text($b['metric']) ?></td>
                <td><?= text($b['measurement_frequency']) ?></td>
                <td><?= text($b['report_frequency']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <table class="quality-table">
        <tr class="dark-row"><th>PLAN DE MEJORA DE PROCESOS:<span class="section-subtitle"> ESPECIFICAR LOS PASOS PARA ANALIZAR PROCESOS, LOS CUALES FACILITARÁN LA IDENTIFICACIÓN DE ACTIVIDADES QUE GENERAN DESPERDICIO O QUE NO AGREGAN VALOR.</span></th></tr>
        <tr>
            <td>
                Cada vez que se deba mejorar un proceso se seguirán los siguientes pasos:
                <ol style="margin:4px 0 0 55px;">
                    <?php foreach ($plan['steps'] as $s): ?>
                        <li><?= e($s['description']) ?></li>
                    <?php endforeach; ?>
                </ol>
            </td>
        </tr>
    </table>
    <div class="footer-line"><?= e(plan_header_value($plan, 'footer_note')) ?></div>
</section>

<section class="page">
    <?php doc_header($plan); ?>
    <table class="quality-table">
        <tr class="dark-row">
            <th colspan="4">MATRIZ DE ACTIVIDADES DE CALIDAD:<span class="section-subtitle"> ESPECIFICAR PARA CADA PAQUETE DE TRABAJO SI EXISTE UN ESTÁNDAR O NORMA DE CALIDAD APLICABLE A SU ELABORACIÓN.</span></th>
        </tr>
        <tr>
            <th>PAQUETE DE TRABAJO</th>
            <th>ESTÁNDAR O NORMA DE CALIDAD APLICABLE</th>
            <th>ACTIVIDADES DE PREVENCIÓN</th>
            <th>ACTIVIDADES DE CONTROL</th>
        </tr>
        <?php foreach ($plan['activities'] as $a): ?>
            <tr>
                <td><?= text($a['work_package']) ?></td>
                <td><?= text($a['quality_standard']) ?></td>
                <td><?= text($a['prevention_activities']) ?></td>
                <td><?= text($a['control_activities']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <table class="quality-table">
        <tr class="dark-row">
            <th colspan="2">ROLES PARA LA GESTIÓN DE LA CALIDAD:<span class="section-subtitle"> ESPECIFICAR LOS ROLES NECESARIOS, OBJETIVOS, FUNCIONES, AUTORIDAD, REPORTES, SUPERVISIÓN Y REQUISITOS.</span></th>
        </tr>
        <?php foreach ($plan['roles'] as $r): ?>
            <tr>
                <td class="role-name" rowspan="9">ROL No <?= e($r['sort_order'] ?: '') ?>:<br><?= e($r['role_name']) ?></td>
                <td><span class="role-label">Objetivos del rol:</span><br><?= text($r['role_objectives']) ?></td>
            </tr>
            <tr><td><span class="role-label">Funciones del rol:</span><br><?= text($r['role_functions']) ?></td></tr>
            <tr><td><span class="role-label">Niveles de autoridad:</span><br><?= text($r['authority_level']) ?></td></tr>
            <tr><td><span class="role-label">Reporta a:</span><br><?= e($r['reports_to']) ?></td></tr>
            <tr><td><span class="role-label">Supervisa a:</span><br><?= e($r['supervises']) ?></td></tr>
            <tr><td><span class="role-label">Requisitos de conocimientos:</span><br><?= text($r['knowledge_requirements']) ?></td></tr>
            <tr><td><span class="role-label">Requisitos de habilidades:</span><br><?= text($r['skill_requirements']) ?></td></tr>
            <tr><td><span class="role-label">Requisitos de experiencia:</span><br><?= text($r['experience_requirements']) ?></td></tr>
            <tr><td></td></tr>
        <?php endforeach; ?>
    </table>
    <div class="footer-line"><?= e(plan_header_value($plan, 'footer_note')) ?></div>
</section>

<section class="page">
    <?php doc_header($plan); ?>
    <table class="quality-table">
        <tr class="dark-row"><th>ORGANIZACIÓN PARA LA CALIDAD DEL PROYECTO:<span class="section-subtitle"> ESPECIFICAR EL ORGANIGRAMA DEL PROYECTO INDICANDO CLARAMENTE DONDE ESTARÁN SITUADOS LOS ROLES PARA LA GESTIÓN DE LA CALIDAD</span></th></tr>
        <tr>
            <td>
                <div class="org-chart">
                    <?php
                    $itemsByName = [];
                    foreach ($plan['organization'] as $item) $itemsByName[strtoupper($item['item_name'])] = $item;
                    $sponsor = $itemsByName['SPONSOR']['item_name'] ?? ($plan['organization'][0]['item_name'] ?? 'SPONSOR');
                    $pm = $itemsByName['PROJECT MANAGER']['item_name'] ?? 'PROJECT MANAGER';
                    $team = $itemsByName['EQUIPO DE PROYECTO']['item_name'] ?? 'EQUIPO DE PROYECTO';
                    $committee = $itemsByName['COMITÉ DE CONTROL DE CAMBIOS']['item_name'] ?? 'Comité de Control de Cambios';
                    ?>
                    <div class="org-box"><?= e($sponsor) ?></div>
                    <div class="org-line"></div>
                    <div class="org-row">
                        <div style="width:240px"></div>
                        <div class="org-side"><?= e($committee) ?></div>
                    </div>
                    <div class="org-box"><?= e($pm) ?></div>
                    <div class="org-line"></div>
                    <div class="org-box"><?= e($team) ?></div>
                </div>
            </td>
        </tr>
    </table>

    <table class="quality-table">
        <tr class="dark-row">
            <th colspan="2">DOCUMENTOS NORMATIVOS PARA LA CALIDAD:<span class="section-subtitle"> ESPECIFICAR QUE DOCUMENTOS NORMATIVOS REGIRÁN LOS PROCESOS Y ACTIVIDADES DE GESTIÓN DE LA CALIDAD</span></th>
        </tr>
        <?php foreach ($docTypes as $typeKey => $label): ?>
            <tr>
                <td class="left-category" rowspan="4"><?= e($label) ?></td>
                <td class="item-line">1. <?= e($groupedDocs[$typeKey][0]) ?></td>
            </tr>
            <tr><td class="item-line">2. <?= e($groupedDocs[$typeKey][1]) ?></td></tr>
            <tr><td class="item-line">3. <?= e($groupedDocs[$typeKey][2]) ?></td></tr>
            <tr><td class="item-line">4. <?= e($groupedDocs[$typeKey][3]) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <div class="footer-line"><?= e(plan_header_value($plan, 'footer_note')) ?></div>
</section>

<section class="page">
    <?php doc_header($plan); ?>
    <table class="quality-table">
        <tr class="dark-row">
            <th colspan="2">PROCESOS DE GESTIÓN DE LA CALIDAD:<span class="section-subtitle"> ESPECIFICAR EL ENFOQUE PARA REALIZAR LOS PROCESOS DE GESTIÓN DE LA CALIDAD INDICANDO EL QUÉ, QUIÉN, CÓMO, CUÁNDO, DÓNDE, CON QUÉ, Y PORQUÉ</span></th>
        </tr>
        <tr>
            <td class="process-title">ENFOQUE DE<br>ASEGURAMIENTO DE<br>LA CALIDAD</td>
            <td class="process-content"><?= text($plan['assurance_approach']) ?></td>
        </tr>
        <tr>
            <td class="process-title">ENFOQUE DE<br>CONTROL DE LA<br>CALIDAD</td>
            <td class="process-content"><?= text($plan['control_approach']) ?></td>
        </tr>
        <tr>
            <td class="process-title">ENFOQUE DE<br>MEJORA DE<br>PROCESOS</td>
            <td class="process-content">
                <?= text($plan['improvement_approach_intro']) ?>
                <ol class="process-steps">
                    <?php foreach ($plan['steps'] as $s): ?>
                        <li><?= e($s['description']) ?></li>
                    <?php endforeach; ?>
                </ol>
            </td>
        </tr>
    </table>
    <div class="footer-line"><?= e(plan_header_value($plan, 'footer_note')) ?></div>
</section>
</body>
</html>
