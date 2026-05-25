# Validacion final del MVP - Plan de Gestion de Calidad

## Veredicto rapido

El proyecto cubre el alcance principal del MVP: crear, editar, eliminar,
listar, cambiar estado y visualizar un Plan de Gestion de Calidad con las
secciones de la plantilla EGPR230.

La vista HTML sigue la estructura del documento de gestion de calidad y permite
imprimir/guardar como PDF desde el navegador. El encabezado acepta logos por
carga de archivo, los convierte a WebP y los ajusta automaticamente.

## Matriz de cumplimiento

| Requerimiento | Estado | Implementacion actual |
| --- | --- | --- |
| Crear plan | Cumple | `public/create.php`, `public/form.php`, `app/repository.php` |
| Editar plan | Cumple | `public/edit.php`, `public/save.php` |
| Eliminar plan | Cumple | `public/delete.php`, borrado logico con `deleted_at` |
| Listar planes | Cumple | `public/index.php` |
| Cambiar estado | Cumple | `public/status.php`, estados centralizados en `app/template.php` |
| Control de versiones | Cumple | Tabla `quality_plan_versions` |
| Datos del proyecto | Cumple | Campos `project_name`, `project_acronym` en `quality_plans` |
| Encabezado configurable | Cumple | Campos `header_*`, `document_code` y `footer_note` |
| Logos sin URL manual | Cumple | `public/form.php`, `app/logo_upload.php`, `public/uploads/logos/` |
| Politica de calidad | Cumple | Campo `quality_policy` |
| Linea base de calidad | Cumple | Tabla `quality_baselines` |
| Plan de mejora de procesos | Cumple | Tabla `process_improvement_steps` |
| Matriz de actividades de calidad | Cumple | Tabla `quality_activity_matrix` |
| Roles de calidad | Cumple | Tabla `quality_roles` |
| Organizacion para la calidad | Cumple | Tabla `quality_organization_items` con `parent_name` |
| Documentos normativos | Cumple | Tabla `quality_normative_documents` |
| Procesos de gestion de calidad | Cumple | Campos `assurance_approach`, `control_approach`, `improvement_approach_intro` |
| Vista parecida al PDF | Cumple parcialmente | `public/view.php` y `public/assets/css/document.css` |
| Exportar PDF | Parcial/opcional | Se hace con imprimir/guardar PDF del navegador |
| Inicio de sesion | Cumple | `public/login.php`, `app/auth.php`, tabla `users` |
| Registro de usuarios | Cumple | `public/register.php`, usuarios en estado `pending` |
| Aprobacion por administrador | Cumple | `public/users.php`, `public/user_status.php` |
| Cambio obligatorio de password inicial | Cumple | `must_change_password`, `public/change_password.php` |
| Bloqueo de fuerza bruta | Cumple | Tabla `login_attempts`, limite por correo/IP |
| Proteccion contra SQL Injection | Cumple | PDO con prepared statements |
| Proteccion CSRF | Cumple | `app/security.php` y tokens en formularios POST |
| Seguridad de subida de archivos | Cumple | Validacion MIME, peso, ruta interna y conversion a WebP |

## Que sirve y conviene conservar

- La base de datos esta normalizada: una tabla principal para textos unicos y
  tablas hijas para secciones repetibles.
- El CRUD basico esta completo para el alcance academico/MVP.
- La vista previa reproduce la estructura de paginas, tablas y secciones de la
  plantilla.
- El repositorio usa transacciones al guardar, asi el plan y sus secciones
  quedan consistentes.
- `app/template.php` centraliza valores de la plantilla para agregar despues
  otros documentos de gestion de proyectos.
- `app/logo_upload.php` evita URLs manuales y guarda solo logos generados por
  la app.
- `app/security.php` concentra las reglas basicas de seguridad.
- `app/auth.php` separa login, registro, roles y aprobacion de usuarios.

## Decisiones finales de entrega

- El estado final del plan usa `finalized` en codigo y SQL.
- El texto introductorio de mejora usa `improvement_approach_intro`.
- El organigrama del MVP usa `parent_name`, suficiente para mostrar jerarquia
  simple sin complicar el formulario.
- `QualityPlanRepository.php` queda como fachada orientada a objetos sobre el
  repositorio funcional.
- Todas las paginas publicas cargan `app/bootstrap.php`.
- El encabezado usa carga de archivo para logos, no URLs manuales.
- Todas las rutas del plan exigen sesion activa.
- Los usuarios nuevos quedan pendientes hasta aprobacion del administrador.
- La base de datos final se crea solo con `plan_calidad_schema.sql` y
  `plan_calidad_seed.sql`.

## Pendientes recomendados

- Si el entregable exige verse identico al PDF, hacer revision visual fina de
  medidas, fuentes y saltos de pagina.
- Si se necesita exportacion automatica, agregar Dompdf, mPDF o un servicio de
  render HTML a PDF.
- Si se va a integrar con otros softwares, exponer endpoints JSON o separar una
  capa API.
- Agregar recuperacion de contrasena por correo si se publica para usuarios
  externos.
- Mantener y ampliar pruebas automatizadas cuando se agreguen mas plantillas.
