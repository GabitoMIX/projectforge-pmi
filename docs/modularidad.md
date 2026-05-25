# Guia de modularidad y extension

Esta guia explica como crecer el proyecto sin volverlo dificil de mantener.

## Separacion actual

La aplicacion queda dividida asi:

- `public/`: archivos que abre el navegador. Cada archivo representa una
  pantalla o accion HTTP.
- `app/`: logica reutilizable que no debe duplicarse en las pantallas.
- `database/`: scripts SQL para crear la base desde cero.
- `docs/`: manuales tecnicos y de usuario.
- `tests/`: pruebas smoke para validar que lo importante siga funcionando.

La regla practica es: `public/` coordina la peticion, `app/` decide/valida, y
`database/` solo guarda estructura/datos iniciales.

## Como agregar una pagina privada

1. Crear el archivo en `public/`, por ejemplo `public/reportes.php`.
2. Iniciar con el bootstrap comun:

```php
require_once __DIR__ . '/../app/bootstrap.php';
require_login($pdo);
```

3. Si la pagina es administrativa, usar:

```php
require_admin($pdo);
```

4. Escapar toda salida HTML con `e()` o `text()`.
5. Si la pagina modifica datos por POST, agregar:

```php
require_post_request();
verify_csrf_token($_POST['_csrf'] ?? null);
```

6. En cada formulario POST incluir:

```php
<?= csrf_input() ?>
```

## Como agregar un modulo nuevo

Para un nuevo documento o software complementario, usar este patron:

```txt
app/
|-- nuevo_modulo_template.php
|-- nuevo_modulo_repository.php
public/
|-- nuevo_modulo_index.php
|-- nuevo_modulo_create.php
|-- nuevo_modulo_edit.php
|-- nuevo_modulo_save.php
|-- nuevo_modulo_view.php
tests/
`-- smoke_nuevo_modulo.php
```

El archivo `*_template.php` debe guardar valores por defecto, estados y listas
del formulario. El archivo `*_repository.php` debe tener las consultas SQL y las
transacciones. Las pantallas en `public/` solo deben recibir datos, llamar al
repositorio y mostrar HTML.

## Contratos que deben mantenerse

- No conectar a base de datos directamente desde varios lugares: usar
  `app/bootstrap.php` y `$pdo`.
- No concatenar datos de usuario dentro del SQL: usar `prepare()` y parametros.
- Si un SQL necesita nombres de tabla o columnas dinamicas, usar listas
  permitidas como en `fetch_children()`.
- No imprimir datos de usuario sin `e()` o `text()`.
- No aceptar rutas de archivos desde el usuario sin normalizarlas.
- No crear endpoints POST sin `require_post_request()` y CSRF.
- No mezclar HTML grande dentro de repositorios o helpers.

## Reutilizacion disponible

- `app/auth.php`: login, usuarios, roles, aprobacion, contrasenas y permisos.
- `app/security.php`: sesiones seguras, CSRF y cabeceras HTTP.
- `app/helpers.php`: escape HTML, redireccion, validacion de filas.
- `app/Database.php`: conexion PDO unica.
- `app/logo_upload.php`: subida segura de logos o imagenes similares.
- `app/template.php`: valores propios del Plan de Gestion de Calidad.
- `app/repository.php`: persistencia del Plan de Gestion de Calidad.
- `app/QualityPlanRepository.php`: fachada orientada a objetos para APIs o MVC.

## Checklist para integrar otro sistema

- Crear tablas nuevas en `database/plan_calidad_schema.sql` o en un nuevo SQL
  claramente nombrado si el modulo es independiente.
- Crear datos iniciales en `database/plan_calidad_seed.sql` solo si son
  necesarios para arrancar.
- Crear repositorio propio en `app/`.
- Crear pantallas propias en `public/`.
- Proteger rutas con `require_login()` o `require_admin()`.
- Agregar una prueba smoke del flujo principal.
- Actualizar `README.md` y el manual de codigo.

## Estado de modularidad actual

El proyecto ya permite agregar paginas nuevas sin tocar el login, la conexion ni
la seguridad transversal. Para crecer hacia varios modulos grandes, el siguiente
paso natural seria mover cada documento a una carpeta propia, por ejemplo
`app/modules/quality_plan/`, pero para esta entrega se mantuvo una estructura
mas simple porque es facil de leer y no exige autoloaders ni Composer.
