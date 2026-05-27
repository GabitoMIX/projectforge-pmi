# ProjectForge PMI - Docker + Dokploy + Cosmos

Esta rama está pensada para desplegar ProjectForge PMI en servidor usando Docker Compose,
Dokploy como host de contenedores y Cosmos Cloud como reverse proxy.

## Arquitectura

```txt
Cloudflare
   ↓
Cloudflare Access
   ↓
Cosmos Cloud
   ↓
Dokploy
   ↓
Docker Compose:
   - app: Apache + PHP 8.2
   - db: MariaDB
   - phpmyadmin: panel web de base de datos
```

## Servicios

```txt
app          Apache + PHP 8.2 + pdo_mysql + gd
db           MariaDB 11.4
phpmyadmin   Panel web para revisar la base de datos
```

## Puertos publicados en el servidor Dokploy

```txt
Aplicación: http://IP_DEL_SERVIDOR_DOKPLOY:18080
phpMyAdmin: http://IP_DEL_SERVIDOR_DOKPLOY:18081
```

La base de datos MariaDB no se publica directamente.

## Variables de entorno en Dokploy

```env
APP_BASE_URL=https://projectforge.argeworks.com
PMA_ABSOLUTE_URI=https://db-projectforge.argeworks.com/

DB_DATABASE=plan_calidad_mvp
DB_USERNAME=projectforge
DB_PASSWORD=CAMBIAR_ESTA_CLAVE
DB_ROOT_PASSWORD=CAMBIAR_ESTA_CLAVE_ROOT
```

## Rutas en Cosmos

```txt
projectforge.argeworks.com
→ http://192.168.13.204:18080
```

```txt
db-projectforge.argeworks.com
→ http://192.168.13.204:18081
```

Ajusta la IP si Dokploy no está en `192.168.13.204`.

## Usuario inicial

```txt
Correo: admin@projectforge.local
Contraseña: Admin123!
```

## phpMyAdmin

```txt
Servidor: db
Usuario: projectforge
Contraseña: valor de DB_PASSWORD
Base de datos: plan_calidad_mvp
```

## Probar localmente

```bash
cp .env.example .env
docker compose up -d --build
```

Luego abrir:

```txt
http://localhost:18080
http://localhost:18081
```

## Logs

```bash
docker compose logs -f app
docker compose logs -f db
docker compose logs -f phpmyadmin
```

## Reiniciar desde cero

```bash
docker compose down -v
docker compose up -d --build
```

> `down -v` elimina los volúmenes, por tanto borra la base de datos.
