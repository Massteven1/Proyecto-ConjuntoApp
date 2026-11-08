# Proyecto-ConjuntoApp

Aplicación web para administrar un conjunto residencial de Pereira con 4 torres y 120 apartamentos. El proyecto está dividido en:

- **Frontend:** Angular, dentro de `frontend/`.
- **Backend:** PHP, dentro de `backend/`.

## Puesta en marcha rápida

Sigue estos pasos después de clonar o descargar el repositorio.

### 1. Descargar el proyecto

Si todavía no tienes una copia local, clona el repositorio y entra en su carpeta:

```bash
git clone <URL_DEL_REPOSITORIO>
cd Proyecto-ConjuntoApp
```

Si ya tienes el proyecto, solo abre una terminal en la carpeta raíz, donde están `frontend` y `backend`.

### 2. Instalar las dependencias del frontend

La carpeta `node_modules` no se sube al repositorio porque contiene dependencias generadas localmente y puede ocupar mucho espacio. Por eso, cada integrante debe crearla en su computador:

```bash
cd frontend
npm install
```

Este comando lee `package.json` y `package-lock.json`, descarga las dependencias y crea `frontend/node_modules`. No es necesario instalar Angular nuevamente de forma global.

> Si `npm` no es reconocido, verifica que Node.js esté instalado y reinicia la terminal. Angular CLI necesita Node.js y npm para instalar dependencias y ejecutar el proyecto.

### 3. Iniciar el frontend

Desde la carpeta `frontend/`, ejecuta:

```bash
npm start
```

Cuando aparezca el mensaje de compilación exitosa, abre [http://localhost:4200/](http://localhost:4200/) en el navegador. La aplicación se recarga automáticamente cuando se modifican los archivos.

Para detener el servidor, presiona `Ctrl + C`.

### 4. Iniciar el backend PHP

Abre una **segunda terminal** en la carpeta raíz del proyecto y ejecuta:

```bash
php -S localhost:8000 -t backend
```

El backend quedará disponible en [http://localhost:8000/](http://localhost:8000/). Mantén esta terminal abierta mientras uses el frontend.

> El endpoint de autenticación está en `backend/api/auth.php`. Angular usa `frontend/proxy.conf.json` para enviar `/api` al servidor PHP. La conexión local está configurada para MariaDB en la base `conjunto_app`.

### Gestión de zonas comunes

Al iniciar sesión con un usuario Administrador, el inicio ofrece los enlaces para administrar zonas y aprobar reservas. Desde zonas se pueden crear, consultar, editar e inactivar espacios; desde reservas se revisan las solicitudes pendientes. Los residentes consultan la disponibilidad por fecha, solicitan reservas y cancelan con 24 horas de anticipación. Portería puede consultar las reservas aprobadas del día. El endpoint `backend/controllers/reservas.php` valida cruces, horario, apartamento asociado y cuotas vencidas. La base de datos ya incluye las tablas y campos necesarios; no hace falta ejecutar una migración.

### Base de datos y generación de cuotas

Importa `sql/conjunto_app.sql` en MySQL para crear las tablas y `sp_generar_cuotas`. Antes de iniciar PHP, configura las variables de entorno `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` con los datos locales de la base `conjunto_app`. El API de cuotas se encuentra en `http://localhost:8000/api/cuotasAPI.php`.

La colección de Postman `docs/postman/cuotas.postman_collection.json` incluye el caso de generación; ejecuta la misma solicitud dos veces para verificar que el segundo intento no duplique cuotas del mismo apartamento y periodo.

## Trabajo diario

Cada vez que descargues cambios nuevos del repositorio:

```bash
git pull
```

Si cambió `frontend/package.json` o `frontend/package-lock.json`, vuelve a instalar las dependencias:

```bash
cd frontend
npm install
```

Si esos archivos no cambiaron, basta con ejecutar `npm start` en `frontend/`. No agregues `node_modules` al commit: está excluido mediante `.gitignore`.

## Comandos útiles del frontend

Todos estos comandos se ejecutan desde `frontend/`:

| Comando           | Uso                                                           |
| ----------------- | ------------------------------------------------------------- |
| `npm start`     | Inicia el servidor de desarrollo en el puerto 4200.           |
| `npm run build` | Genera la compilación del frontend en `frontend/dist/`.    |
| `npm test`      | Ejecuta las pruebas configuradas para Angular.                |
| `npm run watch` | Compila continuamente usando la configuración de desarrollo. |

## Solución de problemas

### `npm install` falla

1. Comprueba que estás dentro de `frontend/`.
2. Verifica las versiones instaladas:

   ```bash
   node --version
   npm --version
   ```
3. Si acabas de cambiar de rama o se interrumpió una instalación anterior, ejecuta de nuevo `npm install`.

### El puerto 4200 está ocupado

Puedes iniciar Angular en otro puerto:

```bash
npm start -- --port 4201
```

En ese caso, abre [http://localhost:4201/](http://localhost:4201/).

### El puerto 8000 está ocupado

Inicia PHP en otro puerto:

```bash
php -S localhost:8001 -t backend
```

Recuerda usar esa nueva dirección (`http://localhost:8001/`) en la configuración del frontend cuando corresponda.

### El frontend no puede comunicarse con PHP

Confirma que:

- Las dos terminales siguen ejecutando sus servidores.
- Angular está en `http://localhost:4200/`.
- PHP está en `http://localhost:8000/` (o en el puerto alternativo elegido).
- Las URLs usadas por los servicios de Angular coinciden con la dirección del backend.
- Angular reenvía `/api` al backend mediante `frontend/proxy.conf.json`; al cambiar el puerto PHP, actualiza el destino de ese archivo.

## Checklist antes de empezar a trabajar

- [ ] Repositorio clonado o actualizado.
- [ ] `node --version` y `npm --version` funcionan.
- [ ] Se ejecutó `npm install` dentro de `frontend/`.
- [ ] `npm start` abre la aplicación en el navegador.
- [ ] El servidor PHP se inició desde la raíz del proyecto.
- [ ] `node_modules` no aparece como cambio para subir a Git.
