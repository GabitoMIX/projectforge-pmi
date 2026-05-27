# Despliegue de ProjectForge PMI con Dokploy + Cosmos Cloud

## Objetivo

Ejecutar ProjectForge PMI en Docker usando Dokploy como host de contenedores
y Cosmos Cloud como reverse proxy principal.

## Componentes

- **Cloudflare**: DNS, SSL externo y Cloudflare Access.
- **Cosmos Cloud**: reverse proxy/gateway.
- **Dokploy**: host de contenedores.
- **Docker Compose**: definición de los servicios.
- **Apache + PHP**: ejecución de la aplicación.
- **MariaDB**: base de datos.
- **phpMyAdmin**: administración visual de la base de datos.

## Estructura del stack

```txt
app          -> Apache + PHP 8.2 + pdo_mysql + gd
db           -> MariaDB 11.4
phpmyadmin   -> panel web para revisar la base de datos
```

## Puertos

```txt
app        -> 18080:80
phpmyadmin -> 18081:80
db         -> solo red interna de Docker
```

No se publica el puerto 3306 de MariaDB.

## Variables de entorno sugeridas

```env
APP_BASE_URL=https://projectforge.argeworks.com
PMA_ABSOLUTE_URI=https://db-projectforge.argeworks.com/

DB_DATABASE=plan_calidad_mvp
DB_USERNAME=projectforge
DB_PASSWORD=CAMBIAR_ESTA_CLAVE
DB_ROOT_PASSWORD=CAMBIAR_ESTA_CLAVE_ROOT
```

## Configuración en Dokploy

1. Crear un proyecto en Dokploy.
2. Crear una aplicación tipo Docker Compose.
3. Conectar el repositorio de GitHub.
4. Seleccionar la rama `docker-cosmos`.
5. Seleccionar el archivo `docker-compose.yml`.
6. Agregar las variables de entorno.
7. Ejecutar Deploy.
8. Verificar que los contenedores `app`, `db` y `phpmyadmin` estén activos.

## Configuración en Cosmos

Crear dos rutas:

```txt
projectforge.argeworks.com
Target: http://192.168.13.204:18080
```

```txt
db-projectforge.argeworks.com
Target: http://192.168.13.204:18081
```

Si Dokploy está en otra IP, reemplazar `192.168.13.204`.

## Protección con Cloudflare Access

Se recomienda proteger:

```txt
projectforge.argeworks.com
db-projectforge.argeworks.com
```

Especialmente `db-projectforge.argeworks.com`, porque da acceso a phpMyAdmin.

## Acceso a phpMyAdmin

```txt
Servidor: db
Usuario: projectforge
Contraseña: valor de DB_PASSWORD
Base de datos: plan_calidad_mvp
```

## Acceso a la aplicación

```txt
Correo: admin@projectforge.local
Contraseña: Admin123!
```

## Inicialización de la base de datos

Los archivos:

```txt
database/plan_calidad_schema.sql
database/plan_calidad_seed.sql
```

se ejecutan automáticamente la primera vez que se crea el volumen de MariaDB.

Si el volumen ya existe, los scripts no se vuelven a ejecutar automáticamente.

Para reiniciar la base de datos desde cero:

```bash
docker compose down -v
docker compose up -d --build
```

## Explicación corta para la entrega

ProjectForge PMI fue preparado para ejecutarse como un entorno tipo XAMPP dockerizado.
La implementación utiliza Docker Compose con tres servicios: una aplicación Apache/PHP,
una base de datos MariaDB y phpMyAdmin. Dokploy administra los contenedores y Cosmos Cloud
se encarga de publicar los dominios mediante reverse proxy. Cloudflare Access puede proteger
el acceso externo a la aplicación y al panel de base de datos.
