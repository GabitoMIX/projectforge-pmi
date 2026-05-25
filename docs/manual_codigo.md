# Manual del codigo - Plan de Gestion de Calidad

Este manual explica como esta armado el MVP, que hace cada archivo y donde se
debe modificar cuando se quiera extender el sistema.

## Resumen de arquitectura

La aplicacion usa PHP puro con una separacion sencilla:

- `public/` contiene las pantallas que abre el navegador.
- `app/` contiene la logica reutilizable.
- `database/` contiene solo los SQL necesarios para crear la base desde cero.
- `docs/` contiene documentacion tecnica.
- `tests/` contiene pruebas smoke.

Para agregar nuevas paginas o modulos, revisar tambien
`docs/modularidad.md`.

Flujo normal:

1. El visitante entra a `public/index.php`.
2. `require_login($pdo)` valida que exista una sesion activa.
3. Si no hay sesion, redirige a `public/login.php`.
4. El usuario activo crea o edita desde `public/form.php`.
5. `public/save.php` valida CSRF, procesa logos y llama al repositorio.
6. `app/repository.php` guarda el plan y sus tablas hijas en transaccion.
7. `public/view.php` muestra el documento imprimible.

## Carga comun: app/bootstrap.php

Todas las paginas publicas incluyen `app/bootstrap.php`.

Ese archivo:

- Carga `app/security.php`.
- Envia cabeceras de seguridad.
- Inicia una sesion segura.
- Crea la conexion de base de datos.
- Carga las funciones de subida de logos.
- Carga el repositorio de planes.
- Carga `app/auth.php`.

Si se agrega una nueva pagina en `public/`, debe empezar con:

```php
require_once __DIR__ . '/../app/bootstrap.php';
```

Si la pagina es privada, despues del bootstrap debe llamar:

```php
require_login($pdo);
```

Si la pagina es solo para administradores:

```php
require_admin($pdo);
```

## Autenticacion: app/auth.php

Este archivo maneja usuarios, roles y estados.

Roles:

- `admin`: puede entrar, gestionar planes y administrar usuarios.
- `user`: puede entrar y gestionar planes cuando esta activo.

Estados:

- `pending`: usuario registrado pero no aprobado.
- `active`: usuario habilitado.
- `disabled`: usuario bloqueado.

Funciones principales:

- `register_user($pdo, $data)`: crea un usuario `pending` con password hash.
- `authenticate_user($pdo, $email, $password)`: valida credenciales, estado y
  crea sesion.
- `current_user($pdo)`: devuelve el usuario de la sesion.
- `require_login($pdo)`: protege paginas privadas.
- `require_admin($pdo)`: protege paginas administrativas.
- `update_user_admin_fields()`: cambia rol/estado desde el panel admin.
- `change_user_password()`: valida password actual, fuerza complejidad y cambia
  la contrasena.
- `assert_login_not_rate_limited()`: bloquea temporalmente demasiados intentos
  fallidos por correo o IP.
- `auth_toolbar($pdo)`: muestra usuario, enlace a usuarios y cierre de sesion.

Reglas de seguridad:

- Las contrasenas se guardan con `password_hash()`.
- El login valida con `password_verify()`.
- Al iniciar sesion se usa `session_regenerate_id(true)`.
- No se permite que el ultimo admin activo se deshabilite o pierda rol admin.
- El admin inicial queda con `must_change_password = 1`.
- El login registra intentos en `login_attempts` y bloquea despues de 5 fallos
  en 15 minutos.
- Las contrasenas nuevas deben tener minimo 10 caracteres, mayuscula, minuscula
  y numero.

## Seguridad transversal: app/security.php

Funciones importantes:

- `send_security_headers()`: envia headers como `X-Frame-Options`,
  `X-Content-Type-Options`, `Referrer-Policy` y CSP.
- `start_secure_session()`: inicia sesion con cookie `HttpOnly` y `SameSite=Lax`.
- `csrf_token()`: crea o devuelve el token CSRF de la sesion.
- `csrf_input()`: imprime el `<input hidden>` para formularios POST.
- `require_post_request()`: bloquea endpoints de escritura si no llegan por POST.
- `verify_csrf_token()`: valida que el POST venga del formulario real.

Regla practica para endpoints de escritura:

```php
require_login($pdo);
require_post_request();
verify_csrf_token($_POST['_csrf'] ?? null);
```

## Logos: app/logo_upload.php

Evita que el usuario escriba URLs manualmente.

Funciones:

- `process_logo_uploads()`: revisa logo izquierdo/derecho, guarda nuevos
  archivos y elimina logos marcados para quitar.
- `normalize_logo_public_path()`: acepta solo rutas internas con formato
  `uploads/logos/{hash}.webp`.
- `save_logo_upload()`: valida, redimensiona, convierte a WebP y guarda.
- `validate_logo_upload()`: valida error de subida, peso, MIME real e imagen.
- `resize_logo_image()`: limita la imagen a maximo `480x180`.
- `delete_uploaded_logo()`: elimina solo archivos dentro de `public/uploads/logos`.

Constantes editables:

```php
const LOGO_UPLOAD_MAX_BYTES = 2097152;
const LOGO_OUTPUT_MAX_WIDTH = 480;
const LOGO_OUTPUT_MAX_HEIGHT = 180;
const LOGO_OUTPUT_QUALITY = 82;
```

Los archivos generados en `public/uploads/logos` no se suben al repositorio por
defecto. Se conserva `.gitkeep` y `.htaccess`; las imagenes reales deben
respaldarse aparte en produccion.

## Base de datos: app/Database.php y app/db.php

`app/Database.php` crea una conexion PDO unica.

Configuracion clave:

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
- `PDO::ATTR_EMULATE_PREPARES => false`

`app/db.php` inicializa `$pdo` y muestra un error amigable si MySQL falla.

## Helpers: app/helpers.php

Funciones pequenas:

- `e()`: escapa HTML y evita XSS al imprimir texto.
- `text()`: escapa HTML y conserva saltos de linea.
- `redirect()`: redirecciona y termina ejecucion.
- `status_label()` y `status_class()`: muestran estado del plan.
- `normalize_rows()`: reindexa filas repetibles.
- `row_has_any_value()`: detecta filas vacias.
- `require_row_fields()`: valida campos obligatorios por fila.

Todo dato de usuario mostrado en HTML debe pasar por `e()` o `text()`.

## Plantilla: app/template.php

Centraliza valores del documento EGPR230:

- Estados del plan.
- Tipos de documentos normativos.
- Encabezado generico.
- Filas por defecto de versiones, metricas, pasos, actividades, roles y
  organigrama.

## Repositorio de planes: app/repository.php

Funciones de lectura:

- `get_all_plans()`
- `find_plan()`
- `fetch_children()`
- `get_full_plan()`

Funcion principal:

- `create_or_update_plan()`

Guardado:

1. Lee `id`.
2. Limpia textos.
3. Normaliza rutas de logos.
4. Valida campos obligatorios.
5. Aplica defaults.
6. Abre transaccion.
7. Inserta/actualiza `quality_plans`.
8. Reemplaza tablas hijas con `replace_*()`.
9. Confirma o revierte.

## Pantallas publicas

### Autenticacion

- `public/login.php`: inicio de sesion.
- `public/register.php`: solicitud de usuario pendiente.
- `public/logout.php`: cierre de sesion por POST + CSRF.
- `public/change_password.php`: cambio de contrasena normal u obligatorio.
- `public/users.php`: panel admin de usuarios.
- `public/user_status.php`: actualiza rol/estado de usuario.

### Planes

- `public/index.php`: lista planes activos.
- `public/create.php`: prepara plan nuevo.
- `public/edit.php`: carga plan existente.
- `public/form.php`: formulario principal.
- `public/save.php`: guarda plan.
- `public/view.php`: vista imprimible.
- `public/status.php`: cambia estado del plan.
- `public/delete.php`: borrado logico.

## Modelo de datos

Tabla de usuarios:

- `users`
- `login_attempts`

Tabla principal del documento:

- `quality_plans`

Tablas hijas:

- `quality_plan_versions`
- `quality_baselines`
- `process_improvement_steps`
- `quality_activity_matrix`
- `quality_roles`
- `quality_organization_items`
- `quality_normative_documents`

## Como agregar un campo nuevo al plan

1. Agregar la columna en `database/plan_calidad_schema.sql`.
2. Agregar el campo en `$data` dentro de `create_or_update_plan()`.
3. Agregarlo en el `INSERT` y `UPDATE`.
4. Agregar el input en `public/form.php`.
5. Mostrarlo en `public/view.php`.
6. Agregarlo a pruebas si afecta comportamiento critico.

La entrega final mantiene solo scripts para crear la base desde cero. Si ya hay
una instalacion publicada y se necesita actualizarla sin perder datos, crea una
migracion nueva y documentala con fecha/version antes de subirla.

## Como agregar una seccion repetible

1. Crear tabla hija con `quality_plan_id`.
2. Agregar relacion en diagramas.
3. Leerla en `get_full_plan()`.
4. Crear una funcion `replace_nombre_seccion()`.
5. Llamarla en la transaccion de `create_or_update_plan()`.
6. Agregar UI en `public/form.php`.
7. Mostrarla en `public/view.php`.

## Como modificar autenticacion

Para cambiar roles, edita `USER_ROLES` en `app/auth.php` y actualiza el `ENUM`
de `users.role`.

Para cambiar estados, edita `USER_STATUSES` y el `ENUM` de `users.status`.

Para agregar permisos por modulo, crea helpers como:

```php
function can_manage_quality_plans(PDO $pdo): bool
{
    $user = current_user($pdo);
    return $user !== null && $user['status'] === 'active';
}
```

Despues llama ese helper desde las paginas protegidas.

## Pruebas

`tests/smoke_auth.php` valida:

- Registro pendiente.
- Bloqueo de login para usuario pendiente.
- Aprobacion por admin.
- Login de usuario activo.
- Cambio obligatorio de contrasena.
- Deshabilitacion.
- Bloqueo temporal por intentos fallidos.

`tests/smoke_repository.php` valida:

- Crear plan.
- Leer plan completo.
- Guardar encabezado.
- Guardar relaciones.
- Cambiar estado.
- Borrado logico.

`tests/smoke_logo_resize.php` valida:

- GD activo.
- Conversion WebP.
- Dimensiones maximas del logo.

## Checklist antes de subir a web

- Cambiar contrasena del admin inicial.
- Activar HTTPS.
- Usar usuario de BD con permisos limitados.
- Exponer solo `public/`.
- Configurar `upload_max_filesize` y `post_max_size`.
- Hacer backups de base de datos y `public/uploads/logos`.
- Ejecutar lint y smoke tests.
