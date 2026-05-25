<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logo_upload.php';

/**
 * Capa de persistencia del Plan de Gestion de Calidad.
 *
 * El PDF tiene secciones con filas repetibles; por eso cada replace_* borra e
 * inserta de nuevo las filas hijas dentro de la misma transaccion del plan.
 * Para este MVP es simple de entender y mantiene el orden visual del formato.
 */

function get_all_plans(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM quality_plans WHERE deleted_at IS NULL ORDER BY updated_at DESC, id DESC");
    return $stmt->fetchAll();
}

function find_plan(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM quality_plans WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    return $plan ?: null;
}

function fetch_children(PDO $pdo, string $table, int $planId, string $orderBy = 'sort_order ASC, id ASC'): array
{
    /*
     * $table y $orderBy no pueden venir libres desde formularios o URLs porque
     * SQL no permite parametrizar nombres de tabla/columnas. Por eso se usan
     * listas permitidas; asi este helper sigue siendo reutilizable sin abrir
     * riesgo de SQL Injection en futuras pantallas.
     */
    $allowed = [
        'quality_plan_versions',
        'quality_baselines',
        'process_improvement_steps',
        'quality_activity_matrix',
        'quality_roles',
        'quality_organization_items',
        'quality_normative_documents',
    ];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Tabla no permitida');
    }

    $allowedOrderBy = [
        'sort_order ASC, id ASC',
        'step_number ASC, id ASC',
        'document_type ASC, sort_order ASC, id ASC',
    ];
    if (!in_array($orderBy, $allowedOrderBy, true)) {
        throw new InvalidArgumentException('Orden no permitido');
    }

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE quality_plan_id = ? ORDER BY {$orderBy}");
    $stmt->execute([$planId]);
    return $stmt->fetchAll();
}

function get_full_plan(PDO $pdo, int $id): ?array
{
    $plan = find_plan($pdo, $id);
    if (!$plan) {
        return null;
    }

    $plan['versions'] = fetch_children($pdo, 'quality_plan_versions', $id, 'sort_order ASC, id ASC');
    $plan['baselines'] = fetch_children($pdo, 'quality_baselines', $id, 'sort_order ASC, id ASC');
    $plan['steps'] = fetch_children($pdo, 'process_improvement_steps', $id, 'step_number ASC, id ASC');
    $plan['activities'] = fetch_children($pdo, 'quality_activity_matrix', $id, 'sort_order ASC, id ASC');
    $plan['roles'] = fetch_children($pdo, 'quality_roles', $id, 'sort_order ASC, id ASC');
    $plan['organization'] = fetch_children($pdo, 'quality_organization_items', $id, 'sort_order ASC, id ASC');
    $plan['documents'] = fetch_children($pdo, 'quality_normative_documents', $id, 'document_type ASC, sort_order ASC, id ASC');

    return $plan;
}

function create_or_update_plan(PDO $pdo, array $post): int
{
    $id = isset($post['id']) && $post['id'] !== '' ? (int)$post['id'] : 0;
    $status = (string) ($post['status'] ?? 'draft');

    $data = [
        'project_name' => trim($post['project_name'] ?? ''),
        'project_acronym' => trim($post['project_acronym'] ?? ''),
        'header_left_logo' => normalize_logo_public_path($post['header_left_logo'] ?? ''),
        'header_left_title' => trim($post['header_left_title'] ?? ''),
        'header_left_subtitle' => trim($post['header_left_subtitle'] ?? ''),
        'header_right_logo' => normalize_logo_public_path($post['header_right_logo'] ?? ''),
        'header_right_title' => trim($post['header_right_title'] ?? ''),
        'header_right_subtitle' => trim($post['header_right_subtitle'] ?? ''),
        'document_code' => trim($post['document_code'] ?? ''),
        'footer_note' => trim($post['footer_note'] ?? ''),
        'quality_policy' => trim($post['quality_policy'] ?? ''),
        'assurance_approach' => trim($post['assurance_approach'] ?? ''),
        'control_approach' => trim($post['control_approach'] ?? ''),
        'improvement_approach_intro' => trim($post['improvement_approach_intro'] ?? ''),
        'status' => is_valid_status($status) ? $status : 'draft',
    ];

    if ($data['project_name'] === '' || $data['project_acronym'] === '' || $data['quality_policy'] === '') {
        throw new RuntimeException('Nombre del proyecto, siglas y política de calidad son obligatorios.');
    }

    foreach (default_document_header() as $field => $defaultValue) {
        if ($data[$field] === '') {
            $data[$field] = $defaultValue;
        }
    }

    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE quality_plans SET project_name=?, project_acronym=?, header_left_logo=?, header_left_title=?, header_left_subtitle=?, header_right_logo=?, header_right_title=?, header_right_subtitle=?, document_code=?, footer_note=?, quality_policy=?, assurance_approach=?, control_approach=?, improvement_approach_intro=?, status=? WHERE id=? AND deleted_at IS NULL");
            $stmt->execute([
                $data['project_name'], $data['project_acronym'],
                $data['header_left_logo'], $data['header_left_title'], $data['header_left_subtitle'],
                $data['header_right_logo'], $data['header_right_title'], $data['header_right_subtitle'],
                $data['document_code'], $data['footer_note'], $data['quality_policy'],
                $data['assurance_approach'], $data['control_approach'], $data['improvement_approach_intro'],
                $data['status'], $id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO quality_plans (project_name, project_acronym, header_left_logo, header_left_title, header_left_subtitle, header_right_logo, header_right_title, header_right_subtitle, document_code, footer_note, quality_policy, assurance_approach, control_approach, improvement_approach_intro, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['project_name'], $data['project_acronym'],
                $data['header_left_logo'], $data['header_left_title'], $data['header_left_subtitle'],
                $data['header_right_logo'], $data['header_right_title'], $data['header_right_subtitle'],
                $data['document_code'], $data['footer_note'], $data['quality_policy'],
                $data['assurance_approach'], $data['control_approach'], $data['improvement_approach_intro'],
                $data['status']
            ]);
            $id = (int)$pdo->lastInsertId();
        }

        replace_versions($pdo, $id, $post['versions'] ?? []);
        replace_baselines($pdo, $id, $post['baselines'] ?? []);
        replace_steps($pdo, $id, $post['steps'] ?? []);
        replace_activities($pdo, $id, $post['activities'] ?? []);
        replace_roles($pdo, $id, $post['roles'] ?? []);
        replace_organization($pdo, $id, $post['organization'] ?? []);
        replace_documents($pdo, $id, $post['documents'] ?? []);

        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function replace_versions(PDO $pdo, int $planId, array $rows): void
{
    $pdo->prepare("DELETE FROM quality_plan_versions WHERE quality_plan_id=?")->execute([$planId]);
    $stmt = $pdo->prepare("INSERT INTO quality_plan_versions (quality_plan_id, version_number, made_by, reviewed_by, approved_by, version_date, reason, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach (normalize_rows($rows) as $i => $row) {
        if (!row_has_any_value($row, ['version_number', 'made_by', 'reviewed_by', 'approved_by', 'version_date', 'reason'])) {
            continue;
        }
        require_row_fields($row, ['version_number' => 'Version'], 'Control de versiones', $i);
        $version = trim($row['version_number'] ?? '');
        $date = trim($row['version_date'] ?? '') ?: null;
        $stmt->execute([$planId, $version, trim($row['made_by'] ?? ''), trim($row['reviewed_by'] ?? ''), trim($row['approved_by'] ?? ''), $date, trim($row['reason'] ?? ''), $i + 1]);
    }
}

function replace_baselines(PDO $pdo, int $planId, array $rows): void
{
    $pdo->prepare("DELETE FROM quality_baselines WHERE quality_plan_id=?")->execute([$planId]);
    $stmt = $pdo->prepare("INSERT INTO quality_baselines (quality_plan_id, quality_factor, quality_objective, metric, measurement_frequency, report_frequency, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach (normalize_rows($rows) as $i => $row) {
        if (!row_has_any_value($row, ['quality_factor', 'quality_objective', 'metric', 'measurement_frequency', 'report_frequency'])) {
            continue;
        }
        require_row_fields($row, [
            'quality_factor' => 'Factor de calidad',
            'quality_objective' => 'Objetivo de calidad',
            'metric' => 'Metrica',
            'measurement_frequency' => 'Frecuencia de medicion',
            'report_frequency' => 'Frecuencia de reporte',
        ], 'Linea base de calidad', $i);
        $factor = trim($row['quality_factor'] ?? '');
        $stmt->execute([$planId, $factor, trim($row['quality_objective'] ?? ''), trim($row['metric'] ?? ''), trim($row['measurement_frequency'] ?? ''), trim($row['report_frequency'] ?? ''), $i + 1]);
    }
}

function replace_steps(PDO $pdo, int $planId, array $rows): void
{
    $pdo->prepare("DELETE FROM process_improvement_steps WHERE quality_plan_id=?")->execute([$planId]);
    $stmt = $pdo->prepare("INSERT INTO process_improvement_steps (quality_plan_id, step_number, description) VALUES (?, ?, ?)");
    foreach (normalize_rows($rows) as $i => $row) {
        if (!row_has_any_value($row, ['step_number', 'description'])) {
            continue;
        }
        require_row_fields($row, ['description' => 'Descripcion'], 'Plan de mejora de procesos', $i);
        $desc = trim($row['description'] ?? '');
        $number = isset($row['step_number']) && $row['step_number'] !== '' ? (int)$row['step_number'] : ($i + 1);
        $stmt->execute([$planId, $number, $desc]);
    }
}

function replace_activities(PDO $pdo, int $planId, array $rows): void
{
    $pdo->prepare("DELETE FROM quality_activity_matrix WHERE quality_plan_id=?")->execute([$planId]);
    $stmt = $pdo->prepare("INSERT INTO quality_activity_matrix (quality_plan_id, work_package, quality_standard, prevention_activities, control_activities, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach (normalize_rows($rows) as $i => $row) {
        if (!row_has_any_value($row, ['work_package', 'quality_standard', 'prevention_activities', 'control_activities'])) {
            continue;
        }
        require_row_fields($row, ['work_package' => 'Paquete de trabajo'], 'Matriz de actividades de calidad', $i);
        $work = trim($row['work_package'] ?? '');
        $stmt->execute([$planId, $work, trim($row['quality_standard'] ?? ''), trim($row['prevention_activities'] ?? ''), trim($row['control_activities'] ?? ''), $i + 1]);
    }
}

function replace_roles(PDO $pdo, int $planId, array $rows): void
{
    $pdo->prepare("DELETE FROM quality_roles WHERE quality_plan_id=?")->execute([$planId]);
    $stmt = $pdo->prepare("INSERT INTO quality_roles (quality_plan_id, role_name, role_objectives, role_functions, authority_level, reports_to, supervises, knowledge_requirements, skill_requirements, experience_requirements, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach (normalize_rows($rows) as $i => $row) {
        if (!row_has_any_value($row, ['role_name', 'role_objectives', 'role_functions', 'authority_level', 'reports_to', 'supervises', 'knowledge_requirements', 'skill_requirements', 'experience_requirements'])) {
            continue;
        }
        require_row_fields($row, ['role_name' => 'Nombre del rol'], 'Roles para la gestion de calidad', $i);
        $role = trim($row['role_name'] ?? '');
        $stmt->execute([
            $planId, $role, trim($row['role_objectives'] ?? ''), trim($row['role_functions'] ?? ''), trim($row['authority_level'] ?? ''),
            trim($row['reports_to'] ?? ''), trim($row['supervises'] ?? ''), trim($row['knowledge_requirements'] ?? ''),
            trim($row['skill_requirements'] ?? ''), trim($row['experience_requirements'] ?? ''), $i + 1
        ]);
    }
}

function replace_organization(PDO $pdo, int $planId, array $rows): void
{
    $pdo->prepare("DELETE FROM quality_organization_items WHERE quality_plan_id=?")->execute([$planId]);
    $stmt = $pdo->prepare("INSERT INTO quality_organization_items (quality_plan_id, item_name, parent_name, sort_order) VALUES (?, ?, ?, ?)");
    foreach (normalize_rows($rows) as $i => $row) {
        if (!row_has_any_value($row, ['item_name', 'parent_name'])) {
            continue;
        }
        require_row_fields($row, ['item_name' => 'Cargo o rol'], 'Organizacion para la calidad', $i);
        $name = trim($row['item_name'] ?? '');
        $parent = trim($row['parent_name'] ?? '') ?: null;
        $stmt->execute([$planId, $name, $parent, $i + 1]);
    }
}

function replace_documents(PDO $pdo, int $planId, array $docs): void
{
    $pdo->prepare("DELETE FROM quality_normative_documents WHERE quality_plan_id=?")->execute([$planId]);
    $stmt = $pdo->prepare("INSERT INTO quality_normative_documents (quality_plan_id, document_type, document_name, sort_order) VALUES (?, ?, ?, ?)");
    $allowed = ['procedure', 'template', 'format', 'checklist', 'other'];
    foreach ($docs as $type => $items) {
        if (!in_array($type, $allowed, true)) continue;
        foreach ((array)$items as $i => $name) {
            $name = trim((string)$name);
            if ($name === '') continue;
            $stmt->execute([$planId, $type, $name, $i + 1]);
        }
    }
}

function soft_delete_plan(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("UPDATE quality_plans SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

function update_plan_status(PDO $pdo, int $id, string $status): void
{
    if (!is_valid_status($status)) {
        throw new RuntimeException('Estado invalido.');
    }
    $stmt = $pdo->prepare("UPDATE quality_plans SET status=? WHERE id=? AND deleted_at IS NULL");
    $stmt->execute([$status, $id]);
}
