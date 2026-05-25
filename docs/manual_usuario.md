# Manual de usuario - ProjectForge PMI

Este manual explica como usar la aplicacion para gestionar planes de gestion de
calidad.

## Acceso inicial

Ingresa a la URL de la aplicacion. En local normalmente es:

```txt
http://127.0.0.1:8000
```

Si no tienes sesion, la app te llevara a **Iniciar sesion**.

Credenciales iniciales del administrador:

```txt
Correo: admin@projectforge.local
Contrasena: Admin123!
```

En produccion se debe cambiar esta contrasena antes de permitir usuarios reales.
La aplicacion pedira cambiarla automaticamente al primer ingreso.

## Solicitar usuario

1. En la pantalla de login, pulsa **Solicitar registro**.
2. Escribe nombre completo, correo y contrasena.
3. Pulsa **Enviar solicitud**.
4. La cuenta quedara en estado **Pendiente**.
5. Espera a que un administrador la habilite.

Mientras la cuenta este pendiente, no podras entrar al sistema.

## Cambiar contrasena

Puedes cambiar tu contrasena desde la barra superior con **Cambiar contrasena**.

Reglas:

- Minimo 10 caracteres.
- Debe tener mayuscula.
- Debe tener minuscula.
- Debe tener numero.
- Debe ser diferente a la actual.

Si la cuenta usa la contrasena inicial del administrador, el sistema obligara a
cambiarla antes de mostrar los planes.

## Aprobar usuarios como administrador

1. Inicia sesion como administrador.
2. En la barra superior, pulsa **Usuarios**.
3. Busca el usuario pendiente.
4. Selecciona rol:
   - `Administrador`: puede aprobar usuarios y gestionar planes.
   - `Usuario`: puede gestionar planes cuando esta activo.
5. Selecciona estado:
   - `Pendiente`: aun no puede entrar.
   - `Activo`: puede iniciar sesion.
   - `Deshabilitado`: no puede entrar.
6. Pulsa **Guardar**.

La app no deja deshabilitar o quitar el rol del ultimo administrador activo.

## Crear un plan de gestion de calidad

1. Inicia sesion con una cuenta activa.
2. En el listado, pulsa **Crear plan**.
3. Llena nombre del proyecto, siglas y estado.
4. Configura el encabezado si lo necesitas.
5. Completa las secciones del documento:
   - Control de versiones.
   - Politica de calidad.
   - Linea base de calidad.
   - Plan de mejora de procesos.
   - Matriz de actividades.
   - Roles.
   - Organizacion.
   - Documentos normativos.
   - Procesos de gestion de calidad.
6. Pulsa **Guardar plan**.

## Cargar logos del encabezado

En la seccion **Encabezado del documento** puedes cargar logo izquierdo y logo
derecho.

Reglas:

- Se aceptan PNG, JPG o WebP.
- El sistema convierte automaticamente a WebP.
- El sistema ajusta el tamano para que no rompa la plantilla.
- Si no cargas logo, se muestra una marca generica.
- No necesitas pegar URL.

## Ver o guardar PDF del plan

1. En el listado, pulsa **Ver**.
2. Revisa la vista del documento.
3. Pulsa **Imprimir / guardar PDF**.
4. En el dialogo del navegador, elige guardar como PDF.

## Editar un plan

1. En el listado, pulsa **Editar**.
2. Cambia los campos necesarios.
3. Pulsa **Guardar plan**.
4. La app redirige a la vista previa.

## Cambiar estado del plan

Desde el listado puedes cambiar el estado:

- Borrador.
- En proceso.
- Finalizado.

Selecciona el estado y pulsa **Cambiar**.

## Eliminar un plan

Pulsa **Eliminar** en el listado. La app hace un borrado logico: el plan deja de
verse, pero la informacion queda marcada con `deleted_at` en base de datos.

## Cerrar sesion

Pulsa **Cerrar sesion** en la barra superior.

## Recomendaciones para uso real

- Usa correos reales para identificar usuarios.
- Activa solo usuarios conocidos.
- Deshabilita cuentas que ya no deban entrar.
- Revisa intentos fallidos si sospechas ataques de fuerza bruta.
- Haz backups periodicos.
- Cambia la contrasena del admin inicial.
- Publica la app con HTTPS.
