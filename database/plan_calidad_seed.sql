-- Datos iniciales para una instalacion nueva.
-- Incluye el administrador inicial y un plan ejemplo editable.

USE plan_calidad_mvp;

INSERT INTO users (full_name, email, password_hash, role, status, must_change_password, approved_at)
VALUES (
    'Administrador ProjectForge',
    'admin@projectforge.local',
    '$2y$12$hzTVy8nhwYmVabH7vFwAeu0w6Qf4Xgtd8kxcM8iNr7peOnGmDzBRK',
    'admin',
    'active',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO quality_plans (
    project_name,
    project_acronym,
    header_left_logo,
    header_left_title,
    header_left_subtitle,
    header_right_logo,
    header_right_title,
    header_right_subtitle,
    document_code,
    footer_note,
    quality_policy,
    assurance_approach,
    control_approach,
    improvement_approach_intro,
    status
) VALUES (
    'PROGRAMA DE CAPACITACIÓN 2007',
    'CASA',
    '',
    'DHARMA CONSULTING',
    'Especialistas en Project Management',
    '',
    'PMI',
    'Project Management Institute',
    'EGPR230 - Version 4.0',
    'The PMI Registered Education Provider logotipo es una marca registrada del Project Management Institute, Inc.',
    'Este proyecto debe cumplir con los requisitos de calidad desde el punto de vista de la organización, es decir acabar dentro del tiempo y el presupuesto planificados, y también debe cumplir con los requisitos de calidad del cliente, obteniendo un buen nivel de satisfacción por parte de los participantes.',
    'El aseguramiento de calidad se hará monitoreando continuamente la performance del trabajo, los resultados del control de calidad, y sobre todo las métricas.\nDe esta manera se descubrirá tempranamente cualquier necesidad de auditoría de procesos, o de mejora de procesos.\nLos resultados se formalizarán como solicitudes de cambio y/o acciones correctivas/preventivas.\nAsimismo se verificará que dichas solicitudes de cambio, y/o acciones correctivas/preventivas se hayan ejecutado y hayan sido efectivas.',
    'El control de calidad se ejecutará revisando los entregables para ver si están conformes o no.\nLos resultados de estas mediciones se consolidarán y se enviarán al proceso de aseguramiento de calidad.\nAsimismo en este proceso se hará la medición de las métricas y se informarán al proceso de aseguramiento de calidad.\nLos entregables que han sido reprocesados se volverán a revisar para verificar si ya se han vuelto conformes.\nPara los defectos detectados se tratará de detectar las causas raíces de los defectos para eliminar las fuentes del error.',
    'Cada vez que se requiera mejorar un proceso se seguirá lo siguiente:',
    'in_progress'
);

SET @plan_id = LAST_INSERT_ID();

INSERT INTO quality_plan_versions (quality_plan_id, version_number, made_by, reviewed_by, approved_by, version_date, reason, sort_order) VALUES
(@plan_id, '1.0', 'CH', 'AV', 'AV', '2007-06-20', 'Versión original', 1);

INSERT INTO quality_baselines (quality_plan_id, quality_factor, quality_objective, metric, measurement_frequency, report_frequency, sort_order) VALUES
(@plan_id, 'Performance del Proyecto', 'CPI >= 0.95', 'CPI = Cost Performance Index Acumulado', 'Frecuencia semanal. Medición lunes en la mañana.', 'Frecuencia semanal. Reporte lunes en la tarde.', 1),
(@plan_id, 'Performance del Proyecto', 'SPI >= 0.95', 'SPI = Schedule Performance Index Acumulado', 'Frecuencia semanal. Medición lunes en la mañana.', 'Frecuencia semanal. Reporte lunes en la tarde.', 2),
(@plan_id, 'Satisfacción de los Participantes a los Cursos', 'Nivel de Satisfacción >= 4.0', 'Promedio entre 1 a 5 de factores sobre material, instructor y exposición.', 'Una encuesta por cada sesión. Medición al día siguiente de la encuesta.', 'Una vez por cada sesión. Reporte al día siguiente de la medición.', 3);

INSERT INTO process_improvement_steps (quality_plan_id, step_number, description) VALUES
(@plan_id, 1, 'Delimitar el proceso'),
(@plan_id, 2, 'Determinar la oportunidad de mejora'),
(@plan_id, 3, 'Tomar información sobre el proceso'),
(@plan_id, 4, 'Analizar la información levantada'),
(@plan_id, 5, 'Definir las acciones correctivas para mejorar el proceso'),
(@plan_id, 6, 'Aplicar las acciones correctivas'),
(@plan_id, 7, 'Verificar si las acciones correctivas han sido efectivas'),
(@plan_id, 8, 'Estandarizar las mejoras logradas para hacerlas parte del proceso');

INSERT INTO quality_activity_matrix (quality_plan_id, work_package, quality_standard, prevention_activities, control_activities, sort_order) VALUES
(@plan_id, '1.1.1 Project Charter', 'Metodología de Gestión de Proyectos', '', 'Aprobación por Sponsor', 1),
(@plan_id, '1.1.2 Scope Statement', 'Metodología de Gestión de Proyectos', '', 'Aprobación por Sponsor', 2),
(@plan_id, '1.2 Plan de Proyecto', 'Metodología de Gestión de Proyectos', '', 'Aprobación por Sponsor', 3),
(@plan_id, '2.1 Contrato con proveedor', 'Estándar de Contrato', 'Revisión de estándar', 'Revisión/Aprobación por Sponsor', 4),
(@plan_id, '3.1 Materiales del curso', 'Curso estándar', '', 'Revisión por Project Manager', 5);

INSERT INTO quality_roles (quality_plan_id, role_name, role_objectives, role_functions, authority_level, reports_to, supervises, knowledge_requirements, skill_requirements, experience_requirements, sort_order) VALUES
(@plan_id, 'SPONSOR', 'Responsable ejecutivo y final por la calidad del proyecto', 'Revisar, aprobar y tomar acciones correctivas para mejorar la calidad', 'Aplicar recursos de la organización para el proyecto y renegociar contratos', 'Directorio', 'Project Manager', 'Project Management y gestión en general', 'Liderazgo, comunicación, negociación, motivación y solución de conflictos', 'Más de 20 años de experiencia en el ramo', 1),
(@plan_id, 'PROJECT MANAGER', 'Gestionar operativamente la calidad', 'Revisar estándares, revisar entregables, aceptar entregables o disponer su reproceso, deliberar y aplicar acciones correctivas', 'Exigir cumplimiento de entregables al equipo de proyecto', 'Sponsor', 'Equipo de Proyecto', 'Gestión de proyectos', 'Liderazgo, comunicación, negociación, motivación y solución de conflictos', '3 años de experiencia en el cargo', 2),
(@plan_id, 'MIEMBROS DEL EQUIPO DE PROYECTO', 'Elaborar los entregables con la calidad requerida y según estándares', 'Elaborar los entregables', 'Aplicar los recursos asignados', 'Project Manager', '', 'Gestión de proyectos y especialidades según entregables asignados', 'Específicas según los entregables', 'Específicas según los entregables', 3);

INSERT INTO quality_organization_items (quality_plan_id, item_name, parent_name, sort_order) VALUES
(@plan_id, 'SPONSOR', NULL, 1),
(@plan_id, 'Comité de Control de Cambios', 'SPONSOR', 2),
(@plan_id, 'PROJECT MANAGER', 'SPONSOR', 3),
(@plan_id, 'EQUIPO DE PROYECTO', 'PROJECT MANAGER', 4);

INSERT INTO quality_normative_documents (quality_plan_id, document_type, document_name, sort_order) VALUES
(@plan_id, 'procedure', 'Para Mejora de Procesos', 1),
(@plan_id, 'procedure', 'Para Auditorías de Procesos', 2),
(@plan_id, 'procedure', 'Para Reuniones de Aseguramiento de Calidad', 3),
(@plan_id, 'procedure', 'Para Resolución de Problemas', 4),
(@plan_id, 'template', 'Métricas', 1),
(@plan_id, 'template', 'Plan de Gestión de Calidad', 2),
(@plan_id, 'format', 'Métricas', 1),
(@plan_id, 'format', 'Línea Base de Calidad', 2),
(@plan_id, 'format', 'Plan de Gestión de Calidad', 3),
(@plan_id, 'checklist', 'De Métricas', 1),
(@plan_id, 'checklist', 'De Auditorías', 2),
(@plan_id, 'checklist', 'De Acciones Correctivas', 3);
