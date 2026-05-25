# ProjectForge PMI - MVP Plan de Gestion de Calidad

Aplicacion web en **PHP puro + MySQL/MariaDB** para crear, editar, eliminar,
listar, cambiar estado y visualizar un **Plan de Gestion de Calidad** basado en
la plantilla **EGPR230 - Version 4.0**.

No usa Laravel, Composer ni framework frontend. La idea es que sea facil de
entender, modificar e integrar despues con otros modulos de gestion de
proyectos.

## Estado actual

El MVP ya cubre:

- Login protegido para publicar la app en web.
- Registro de usuarios con estado pendiente.
- Aprobacion, cambio de rol y deshabilitacion desde panel de administrador.
- Cambio obligatorio de la contrasena inicial del administrador.
- Bloqueo temporal por demasiados intentos fallidos de login.
- Crear, editar, listar, cambiar estado y eliminar logicamente planes.
- Vista imprimible tipo documento para guardar como PDF desde el navegador.
- Encabezado configurable con textos, codigo, pie de pagina y logos opcionales.
- Carga de logos por archivo, sin pedir URL.
- Conversion automatica de logos a WebP, compresion y ajuste de tamano.
- Marca generica cuando el proyecto no tiene logo.
- Proteccion contra SQL Injection usando PDO con consultas preparadas.
- Proteccion CSRF en formularios que modifican datos.
- Validacion de archivos subidos para aceptar solo PNG, JPG o WebP.
- Cabeceras HTTP basicas de seguridad.

Documentacion incluida:

- [docs/manual_usuario.md](docs/manual_usuario.md)
- [docs/manual_codigo.md](docs/manual_codigo.md)
- [docs/modularidad.md](docs/modularidad.md)
- [docs/validacion_requerimientos.md](docs/validacion_requerimientos.md)
- [docs/explicacion_codigo.pdf](docs/explicacion_codigo.pdf)

## Credenciales iniciales

El archivo de datos iniciales crea un administrador:

```txt
Correo: admin@projectforge.local
Contrasena: Admin123!
```

Al entrar por primera vez, la app obliga a cambiar esa contrasena antes de
permitir acceso al listado de planes.

## Requisitos

- PHP 8.0 o superior.
- MySQL 5.7+ o MariaDB 10+.
- Extension `pdo_mysql` habilitada.
- Extension `gd` habilitada para convertir/comprimir logos.

## Instalacion rapida

1. Configura la conexion en [app/config.php](app/config.php).

2. Crea la base de datos y todas las tablas:

```bash
mysql -u root -p < database/plan_calidad_schema.sql
```

3. Carga datos de prueba y el admin inicial:

```bash
mysql -u root -p < database/plan_calidad_seed.sql
```

4. Levanta la app:

```bash
php -d extension=pdo_mysql -d extension=gd -S 127.0.0.1:8000 -t public
```

5. Abre:

```txt
http://127.0.0.1:8000
```

Si PHP fue instalado con `winget` y las extensiones no cargan solas:

```powershell
php -d extension_dir="C:\ruta\a\php\ext" -d extension=pdo_mysql -d extension=gd -S 127.0.0.1:8000 -t public
```

## Estructura del proyecto

```txt
projectforge-pmi/
|-- .gitignore
|-- app/
|   |-- auth.php
|   |-- bootstrap.php
|   |-- config.php
|   |-- Database.php
|   |-- db.php
|   |-- helpers.php
|   |-- logo_upload.php
|   |-- repository.php
|   |-- security.php
|   |-- QualityPlanRepository.php
|   `-- template.php
|-- database/
|   |-- plan_calidad_schema.sql
|   `-- plan_calidad_seed.sql
|-- docs/
|   |-- diagrama_bd_mermaid.md
|   |-- manual_usuario.md
|   |-- manual_codigo.md
|   |-- modularidad.md
|   |-- explicacion_codigo.md
|   |-- explicacion_codigo.pdf
|   `-- validacion_requerimientos.md
|-- public/
|   |-- login.php
|   |-- register.php
|   |-- logout.php
|   |-- users.php
|   |-- user_status.php
|   |-- index.php
|   |-- create.php
|   |-- edit.php
|   |-- form.php
|   |-- save.php
|   |-- view.php
|   |-- status.php
|   |-- delete.php
|   |-- uploads/logos/
|   `-- assets/
|       |-- css/
|       `-- js/
|-- tests/
|   |-- smoke_auth.php
|   |-- smoke_repository.php
|   `-- smoke_logo_resize.php
`-- README.md
```

## Archivos principales

| Archivo | Responsabilidad |
| --- | --- |
| `app/auth.php` | Login, registro, aprobacion, roles, estado de usuario y toolbar de sesion. |
| `app/security.php` | CSRF, sesion segura y cabeceras HTTP de seguridad. |
| `app/logo_upload.php` | Valida, convierte, comprime, redimensiona y elimina logos subidos. |
| `app/repository.php` | CRUD principal del plan y guardado transaccional de secciones hijas. |
| `app/template.php` | Estados, tipos de documentos y filas por defecto de la plantilla. |
| `public/login.php` | Pantalla de inicio de sesion. |
| `public/register.php` | Solicitud de usuario pendiente de aprobacion. |
| `public/users.php` | Panel del administrador para activar/deshabilitar usuarios. |
| `public/index.php` | Lista planes activos y permite acciones principales. |
| `public/form.php` | Formulario reutilizable para crear/editar planes. |
| `public/view.php` | Vista imprimible parecida al PDF. |
| `database/plan_calidad_schema.sql` | Crea la base de datos, tablas, indices y relaciones desde cero. |
| `database/plan_calidad_seed.sql` | Carga datos iniciales, plantillas ejemplo y el admin inicial. |

## Modularidad

La estructura esta pensada para crecer por modulos:

- Las paginas nuevas se crean en `public/`.
- La logica reutilizable se agrega en `app/`.
- Los SQL iniciales se mantienen en `database/`.
- Las pruebas smoke del nuevo flujo se agregan en `tests/`.

La guia completa esta en [docs/modularidad.md](docs/modularidad.md).

## Base de datos desde cero

Para una instalacion nueva solo se necesitan estos dos archivos, en este orden:

1. `database/plan_calidad_schema.sql`
2. `database/plan_calidad_seed.sql`

No se dejaron migraciones historicas ni esquemas duplicados en la entrega final
para evitar confusion al crear la base por primera vez.

## Seguridad incluida

- **Autenticacion:** rutas del plan protegidas con `require_login($pdo)`.
- **Roles:** `admin` y `user`.
- **Alta controlada:** los usuarios nuevos quedan `pending` hasta que un admin
  los active.
- **Password inicial:** el admin semilla queda con `must_change_password = 1`.
- **Fuerza bruta:** `login_attempts` limita intentos fallidos por correo/IP.
- **SQL Injection:** consultas PDO con `prepare()` y parametros.
- **CSRF:** token por sesion validado en POST.
- **Subidas:** solo imagenes PNG/JPG/WebP reales, convertidas a WebP interno.
- **Headers:** `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`, `Permissions-Policy` y CSP basica.
- **Borrado:** los planes se eliminan logicamente con `deleted_at`.

## Pruebas locales

Validar sintaxis PHP:

```powershell
Get-ChildItem -Path app,public,tests -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Validar autenticacion:

```bash
php -d extension=pdo_mysql tests/smoke_auth.php
```

Validar CRUD:

```bash
php -d extension=pdo_mysql tests/smoke_repository.php
```

Validar conversion de logos:

```bash
php -d extension=gd tests/smoke_logo_resize.php
```

## Produccion

Antes de publicar:

- Cambia la contrasena del admin inicial.
- Revisa periodicamente `login_attempts` si sospechas ataques de fuerza bruta.
- Usa HTTPS.
- Usa un usuario de BD con permisos limitados.
- Asegurate de que el servidor exponga solo `public/`.
- Configura backups para la base y `public/uploads/logos`.
- Ajusta `upload_max_filesize` y `post_max_size`.
- Revisa logs de PHP/servidor web.

## Limitaciones conocidas

- No hay recuperacion de contrasena por correo.
- No hay API JSON todavia.
- La exportacion a PDF del plan depende del navegador.
- La vista se parece a la plantilla, pero no es pixel-perfect.
