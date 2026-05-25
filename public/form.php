<?php
// Formulario reutilizable para crear y editar el documento completo.
// create.php y edit.php preparan la variable $plan antes de incluir este archivo.
$versions = $plan['versions'] ?: default_quality_plan_versions();
$baselines = $plan['baselines'] ?: default_quality_baselines();
$steps = $plan['steps'] ?: default_process_improvement_steps();
$activities = $plan['activities'] ?: default_quality_activity_matrix();
$roles = $plan['roles'] ?: default_quality_roles();
$organization = $plan['organization'] ?: default_quality_organization();
$docTypes = normative_document_types();
$groupedDocs = [];
foreach ($docTypes as $key => $label) $groupedDocs[$key] = [];
foreach (($plan['documents'] ?? []) as $doc) {
    if (isset($groupedDocs[$doc['document_type']])) {
        $groupedDocs[$doc['document_type']][] = $doc['document_name'];
    }
}
foreach ($docTypes as $key => $label) while (count($groupedDocs[$key]) < 4) $groupedDocs[$key][] = '';
$leftLogo = normalize_logo_public_path($plan['header_left_logo'] ?? '');
$rightLogo = normalize_logo_public_path($plan['header_right_logo'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<header class="app-header">
    <div class="brand">Plan de Gestión de Calidad</div>
    <div class="toolbar">
        <a class="btn btn-outline" href="index.php">Volver</a>
        <?php if ($isEdit): ?><a class="btn btn-outline" href="view.php?id=<?= e($plan['id']) ?>">Vista previa</a><?php endif; ?>
        <?= auth_toolbar($pdo) ?>
    </div>
</header>

<main class="container">
<form action="save.php" method="post" enctype="multipart/form-data">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= e($plan['id'] ?? '') ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= e((string) LOGO_UPLOAD_MAX_BYTES) ?>">

    <section class="card">
        <h1 class="section-title"><?= e($pageTitle) ?></h1>
        <p class="section-help">Llena las secciones del Plan de Gestión de Calidad. La vista previa se encarga de mostrarlo como documento tipo PDF.</p>
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre del proyecto *</label>
                <input name="project_name" required value="<?= e($plan['project_name'] ?? '') ?>" placeholder="Ej: Programa de capacitación 2007">
            </div>
            <div class="form-group">
                <label>Siglas del proyecto *</label>
                <input name="project_acronym" required value="<?= e($plan['project_acronym'] ?? '') ?>" placeholder="Ej: CASA">
            </div>
            <div class="form-group">
                <label>Estado del documento</label>
                <select name="status">
                    <?php foreach (quality_plan_statuses() as $value => $status): ?>
                        <option value="<?= e($value) ?>" <?= ($plan['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($status['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Encabezado del documento</h2>
        <p class="section-help">Opcional. Sube PNG, JPG o WebP; el sistema lo convierte a WebP, lo comprime y lo ajusta al tamano del encabezado. Si no subes logo, se usa una marca generica.</p>
        <div class="form-grid">
            <div class="form-group">
                <label>Logo izquierdo</label>
                <input type="hidden" name="current_header_left_logo" value="<?= e($leftLogo) ?>">
                <input class="file-input" type="file" name="header_left_logo_file" accept="image/png,image/jpeg,image/webp">
                <?php if ($leftLogo !== ''): ?>
                    <div class="logo-preview-row">
                        <img class="logo-preview-img" src="<?= e($leftLogo) ?>" alt="Logo izquierdo actual">
                        <label class="checkbox-inline"><input type="checkbox" name="remove_header_left_logo" value="1"> Quitar logo</label>
                    </div>
                <?php else: ?>
                    <div class="upload-note">Sin logo cargado. Se mostrara una marca generica en la plantilla.</div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Logo derecho</label>
                <input type="hidden" name="current_header_right_logo" value="<?= e($rightLogo) ?>">
                <input class="file-input" type="file" name="header_right_logo_file" accept="image/png,image/jpeg,image/webp">
                <?php if ($rightLogo !== ''): ?>
                    <div class="logo-preview-row">
                        <img class="logo-preview-img" src="<?= e($rightLogo) ?>" alt="Logo derecho actual">
                        <label class="checkbox-inline"><input type="checkbox" name="remove_header_right_logo" value="1"> Quitar logo</label>
                    </div>
                <?php else: ?>
                    <div class="upload-note">Sin logo cargado. El encabezado queda limpio y generico.</div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Titulo izquierdo</label>
                <input name="header_left_title" value="<?= e($plan['header_left_title'] ?? '') ?>" placeholder="ORGANIZACION">
            </div>
            <div class="form-group">
                <label>Subtitulo izquierdo</label>
                <input name="header_left_subtitle" value="<?= e($plan['header_left_subtitle'] ?? '') ?>" placeholder="Gestion de proyectos">
            </div>
            <div class="form-group">
                <label>Titulo derecho</label>
                <input name="header_right_title" value="<?= e($plan['header_right_title'] ?? '') ?>" placeholder="PLAN DE CALIDAD">
            </div>
            <div class="form-group">
                <label>Subtitulo derecho</label>
                <input name="header_right_subtitle" value="<?= e($plan['header_right_subtitle'] ?? '') ?>" placeholder="Documento configurable">
            </div>
            <div class="form-group">
                <label>Codigo o version del documento</label>
                <input name="document_code" value="<?= e($plan['document_code'] ?? '') ?>" placeholder="EGPR230 - Version 4.0">
            </div>
            <div class="form-group">
                <label>Nota de pie de pagina</label>
                <input name="footer_note" value="<?= e($plan['footer_note'] ?? '') ?>" placeholder="Documento generado por ProjectForge PMI.">
            </div>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Control de versiones</h2>
        <p class="section-help">Corresponde a la tabla superior del documento: versión, hecha por, revisada por, aprobada por, fecha y motivo.</p>
        <table class="dynamic-table" id="versionsTable">
            <thead><tr><th>Versión</th><th>Hecha por</th><th>Revisada por</th><th>Aprobada por</th><th>Fecha</th><th>Motivo</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($versions as $i => $row): ?>
                <tr>
                    <td><input name="versions[<?= $i ?>][version_number]" value="<?= e($row['version_number'] ?? '') ?>"></td>
                    <td><input name="versions[<?= $i ?>][made_by]" value="<?= e($row['made_by'] ?? '') ?>"></td>
                    <td><input name="versions[<?= $i ?>][reviewed_by]" value="<?= e($row['reviewed_by'] ?? '') ?>"></td>
                    <td><input name="versions[<?= $i ?>][approved_by]" value="<?= e($row['approved_by'] ?? '') ?>"></td>
                    <td><input type="date" name="versions[<?= $i ?>][version_date]" value="<?= e($row['version_date'] ?? '') ?>"></td>
                    <td><input name="versions[<?= $i ?>][reason]" value="<?= e($row['reason'] ?? '') ?>"></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <template>
                <tr>
                    <td><input name="versions[__INDEX__][version_number]"></td>
                    <td><input name="versions[__INDEX__][made_by]"></td>
                    <td><input name="versions[__INDEX__][reviewed_by]"></td>
                    <td><input name="versions[__INDEX__][approved_by]"></td>
                    <td><input type="date" name="versions[__INDEX__][version_date]"></td>
                    <td><input name="versions[__INDEX__][reason]"></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            </template>
        </table>
        <button class="btn btn-outline" type="button" onclick="addRow('versionsTable')">+ Agregar versión</button>
    </section>

    <section class="card">
        <h2 class="section-title">Política de calidad del proyecto</h2>
        <div class="form-group full">
            <textarea name="quality_policy" required><?= e($plan['quality_policy'] ?? '') ?></textarea>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Línea base de calidad</h2>
        <table class="dynamic-table" id="baselinesTable">
            <thead><tr><th>Factor</th><th>Objetivo</th><th>Métrica</th><th>Medición</th><th>Reporte</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($baselines as $i => $row): ?>
                <tr>
                    <td><textarea name="baselines[<?= $i ?>][quality_factor]"><?= e($row['quality_factor'] ?? '') ?></textarea></td>
                    <td><textarea name="baselines[<?= $i ?>][quality_objective]"><?= e($row['quality_objective'] ?? '') ?></textarea></td>
                    <td><textarea name="baselines[<?= $i ?>][metric]"><?= e($row['metric'] ?? '') ?></textarea></td>
                    <td><textarea name="baselines[<?= $i ?>][measurement_frequency]"><?= e($row['measurement_frequency'] ?? '') ?></textarea></td>
                    <td><textarea name="baselines[<?= $i ?>][report_frequency]"><?= e($row['report_frequency'] ?? '') ?></textarea></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <template>
                <tr>
                    <td><textarea name="baselines[__INDEX__][quality_factor]"></textarea></td>
                    <td><textarea name="baselines[__INDEX__][quality_objective]"></textarea></td>
                    <td><textarea name="baselines[__INDEX__][metric]"></textarea></td>
                    <td><textarea name="baselines[__INDEX__][measurement_frequency]"></textarea></td>
                    <td><textarea name="baselines[__INDEX__][report_frequency]"></textarea></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            </template>
        </table>
        <button class="btn btn-outline" type="button" onclick="addRow('baselinesTable')">+ Agregar métrica</button>
    </section>

    <section class="card">
        <h2 class="section-title">Plan de mejora de procesos</h2>
        <table class="dynamic-table" id="stepsTable">
            <thead><tr><th style="width:100px">Paso</th><th>Descripción</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($steps as $i => $row): ?>
                <tr>
                    <td><input type="number" name="steps[<?= $i ?>][step_number]" value="<?= e($row['step_number'] ?? ($i+1)) ?>"></td>
                    <td><input name="steps[<?= $i ?>][description]" value="<?= e($row['description'] ?? '') ?>"></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <template>
                <tr>
                    <td><input type="number" name="steps[__INDEX__][step_number]"></td>
                    <td><input name="steps[__INDEX__][description]"></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            </template>
        </table>
        <button class="btn btn-outline" type="button" onclick="addRow('stepsTable')">+ Agregar paso</button>
    </section>

    <section class="card">
        <h2 class="section-title">Matriz de actividades de calidad</h2>
        <table class="dynamic-table" id="activitiesTable">
            <thead><tr><th>Paquete de trabajo</th><th>Estándar o norma</th><th>Prevención</th><th>Control</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($activities as $i => $row): ?>
                <tr>
                    <td><textarea name="activities[<?= $i ?>][work_package]"><?= e($row['work_package'] ?? '') ?></textarea></td>
                    <td><textarea name="activities[<?= $i ?>][quality_standard]"><?= e($row['quality_standard'] ?? '') ?></textarea></td>
                    <td><textarea name="activities[<?= $i ?>][prevention_activities]"><?= e($row['prevention_activities'] ?? '') ?></textarea></td>
                    <td><textarea name="activities[<?= $i ?>][control_activities]"><?= e($row['control_activities'] ?? '') ?></textarea></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <template>
                <tr>
                    <td><textarea name="activities[__INDEX__][work_package]"></textarea></td>
                    <td><textarea name="activities[__INDEX__][quality_standard]"></textarea></td>
                    <td><textarea name="activities[__INDEX__][prevention_activities]"></textarea></td>
                    <td><textarea name="activities[__INDEX__][control_activities]"></textarea></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            </template>
        </table>
        <button class="btn btn-outline" type="button" onclick="addRow('activitiesTable')">+ Agregar actividad</button>
    </section>

    <section class="card">
        <h2 class="section-title">Roles para la gestión de calidad</h2>
        <table class="dynamic-table" id="rolesTable">
            <thead><tr><th>Rol</th><th>Detalles del rol</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($roles as $i => $row): ?>
                <tr>
                    <td><input name="roles[<?= $i ?>][role_name]" value="<?= e($row['role_name'] ?? '') ?>"></td>
                    <td class="form-grid">
                        <textarea name="roles[<?= $i ?>][role_objectives]" placeholder="Objetivos"><?= e($row['role_objectives'] ?? '') ?></textarea>
                        <textarea name="roles[<?= $i ?>][role_functions]" placeholder="Funciones"><?= e($row['role_functions'] ?? '') ?></textarea>
                        <textarea name="roles[<?= $i ?>][authority_level]" placeholder="Nivel de autoridad"><?= e($row['authority_level'] ?? '') ?></textarea>
                        <input name="roles[<?= $i ?>][reports_to]" value="<?= e($row['reports_to'] ?? '') ?>" placeholder="Reporta a">
                        <input name="roles[<?= $i ?>][supervises]" value="<?= e($row['supervises'] ?? '') ?>" placeholder="Supervisa a">
                        <textarea name="roles[<?= $i ?>][knowledge_requirements]" placeholder="Conocimientos"><?= e($row['knowledge_requirements'] ?? '') ?></textarea>
                        <textarea name="roles[<?= $i ?>][skill_requirements]" placeholder="Habilidades"><?= e($row['skill_requirements'] ?? '') ?></textarea>
                        <textarea name="roles[<?= $i ?>][experience_requirements]" placeholder="Experiencia"><?= e($row['experience_requirements'] ?? '') ?></textarea>
                    </td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <template>
                <tr>
                    <td><input name="roles[__INDEX__][role_name]"></td>
                    <td class="form-grid">
                        <textarea name="roles[__INDEX__][role_objectives]" placeholder="Objetivos"></textarea>
                        <textarea name="roles[__INDEX__][role_functions]" placeholder="Funciones"></textarea>
                        <textarea name="roles[__INDEX__][authority_level]" placeholder="Nivel de autoridad"></textarea>
                        <input name="roles[__INDEX__][reports_to]" placeholder="Reporta a">
                        <input name="roles[__INDEX__][supervises]" placeholder="Supervisa a">
                        <textarea name="roles[__INDEX__][knowledge_requirements]" placeholder="Conocimientos"></textarea>
                        <textarea name="roles[__INDEX__][skill_requirements]" placeholder="Habilidades"></textarea>
                        <textarea name="roles[__INDEX__][experience_requirements]" placeholder="Experiencia"></textarea>
                    </td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            </template>
        </table>
        <button class="btn btn-outline" type="button" onclick="addRow('rolesTable')">+ Agregar rol</button>
    </section>

    <section class="card">
        <h2 class="section-title">Organización para la calidad</h2>
        <p class="section-help">Indica cada cargo y su superior. En la vista previa se mostrará como organigrama simple.</p>
        <table class="dynamic-table" id="orgTable">
            <thead><tr><th>Cargo / rol</th><th>Depende de</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($organization as $i => $row): ?>
                <tr>
                    <td><input name="organization[<?= $i ?>][item_name]" value="<?= e($row['item_name'] ?? '') ?>"></td>
                    <td><input name="organization[<?= $i ?>][parent_name]" value="<?= e($row['parent_name'] ?? '') ?>"></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <template>
                <tr>
                    <td><input name="organization[__INDEX__][item_name]"></td>
                    <td><input name="organization[__INDEX__][parent_name]"></td>
                    <td><button class="btn btn-sm btn-danger" type="button" onclick="removeRow(this)">X</button></td>
                </tr>
            </template>
        </table>
        <button class="btn btn-outline" type="button" onclick="addRow('orgTable')">+ Agregar elemento</button>
    </section>

    <section class="card">
        <h2 class="section-title">Documentos normativos para la calidad</h2>
        <p class="section-help">Máximo 4 líneas por categoría para que la vista previa quede parecida al PDF.</p>
        <div class="form-grid">
        <?php foreach ($docTypes as $typeKey => $label): ?>
            <div class="form-group">
                <label><?= e($label) ?></label>
                <?php for ($i=0; $i<4; $i++): ?>
                    <input name="documents[<?= e($typeKey) ?>][]" value="<?= e($groupedDocs[$typeKey][$i] ?? '') ?>" placeholder="<?= ($i+1) ?>. <?= e($label) ?>">
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Procesos de gestión de la calidad</h2>
        <div class="form-grid">
            <div class="form-group full">
                <label>Enfoque de aseguramiento de la calidad</label>
                <textarea name="assurance_approach"><?= e($plan['assurance_approach'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Enfoque de control de la calidad</label>
                <textarea name="control_approach"><?= e($plan['control_approach'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Introducción del enfoque de mejora de procesos</label>
                <textarea name="improvement_approach_intro"><?= e($plan['improvement_approach_intro'] ?? '') ?></textarea>
            </div>
        </div>
    </section>

    <div class="actions-fixed">
        <div class="container toolbar" style="margin:0 auto;">
            <button class="btn btn-primary" type="submit">Guardar plan</button>
            <a class="btn btn-outline" href="index.php">Cancelar</a>
        </div>
    </div>
</form>
</main>
<script src="assets/js/app.js"></script>
</body>
</html>
