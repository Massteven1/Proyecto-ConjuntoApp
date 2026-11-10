# ConjuntoApp

## Como levantar el proyecto

**Terminal 1 — backend**, desde la raíz del proyecto:

```
php -S localhost:8000 -t backend
```

**Terminal 2 — frontend**:

```
cd frontend
npm start
```

Para administrar la base de datos (nombre `conjunto_app`) usar
phpMyAdmin: http://localhost/phpmyadmin (si esta vacia, importar `sql/conjunto_app.sql`).

## Credenciales para probar en el login

La contraseña de **todos** los usuarios de prueba es: `prueba123`

| Integrante (rama) | Parte que prueba | Credenciales (correo de login) |
| --- | --- | --- |
| **alberth** | Portería y visitantes (RF-15 a RF-18) | Portero: `portero1@conjunto.com` · Residente (preautorizar visitante): `prueba@gmail.com` |
| **cabrera** | Cuotas, pagos y estado de cuenta (RF-19 a RF-21), comunicados (RF-22) y tablero (RF-23) | Administrador: `admin1@conjunto.com` · Residente: `prueba@gmail.com` |
| **catalina** | Zonas comunes (RF-06) y reservas (RF-07 a RF-09) | Administrador: `admin1@conjunto.com` · Residente: `prueba@gmail.com` · Portero: `portero1@conjunto.com` |
| **mauro** | Base de datos, login/registro, usuarios (RF-02), inmuebles (RF-03), residencias (RF-04) y perfil (RF-05) | Administrador: `admin1@conjunto.com` · Residente: `prueba@gmail.com` · Portero: `portero1@conjunto.com` |

Otras cuentas disponibles (misma contraseña `prueba123`):

| Rol | Correos |
| --- | --- |
| Administrador | `admin2@conjunto.com` |
| Portero | `portero2@conjunto.com` |
| Residentes | `prueba@gmail.com`, `sofia@correo.com`, `jorge@correo.com`, `valentina@correo.com`, `andres@correo.com`, `camila@correo.com`, `felipe@correo.com`, `daniela@correo.com`, `santiago@correo.com`, `isabella@correo.com`, `mateo@correo.com` |
