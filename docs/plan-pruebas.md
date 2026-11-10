# Plan de pruebas · ConjuntoApp

Documento de QA de la entrega (responsable: Alberth). Se usa sobre la base `conjunto_app` con el seed de `sql/conjunto_app.sql` y las cuentas de prueba del README.

## 1. Preparación

1. Importar `sql/conjunto_app.sql` en una base limpia.
2. Levantar el backend con `php -S localhost:8000 -t backend`.
3. Levantar el frontend con `npm start` dentro de `frontend/`.
4. Ejecutar la colección `docs/postman/m4-porteria-visitantes.postman_collection.json` con Postman o `npx newman run <archivo>`.

## 2. Matriz rol × pantalla

Cada requisito se prueba con los cuatro roles. `✔` es el rol que usa la pantalla, `—` es un rol que no la usa y solo se comprueba que no rompe la navegación.

| Pantalla | Administrador | Residente | Portero | Sin sesión |
| --- | --- | --- | --- | --- |
| Login | ✔ | ✔ | ✔ | ✔ |
| Inicio | ✔ | ✔ | ✔ | redirige a login |
| Usuarios (RF-02) | ✔ | — | — | redirige a login |
| Torres y apartamentos (RF-03) | ✔ | — | — | redirige a login |
| Residentes por apartamento (RF-04) | ✔ | — | — | redirige a login |
| Perfil y cambio de contraseña (RF-05) | ✔ | ✔ | ✔ | redirige a login |
| Zonas comunes (RF-06) | ✔ | — | — | redirige a login |
| Reservas (RF-07 a RF-09) | ✔ | ✔ | ✔ | redirige a login |
| PQRS (RF-10 a RF-14) | ✔ | ✔ | — | redirige a login |
| Visitantes: preautorizar (RF-15) | — | ✔ | — | redirige a login |
| Portería: buscar, ingreso y salida (RF-16) | — | — | ✔ | redirige a login |
| Portería: parqueaderos de visitantes (RF-17) | — | — | ✔ | redirige a login |
| Portería: bitácora con filtros (RF-18) | — | — | ✔ | redirige a login |
| Cuotas, pagos y estado de cuenta (RF-19 a RF-21) | ✔ | ✔ | — | redirige a login |
| Comunicados (RF-22) | ✔ | ✔ | — | redirige a login |
| Tablero (RF-23) | ✔ | — | — | redirige a login |

## 3. Casos de prueba de M3 (PQRS)

| Caso | Pasos | Resultado esperado |
| --- | --- | --- |
| M3-CP-01 | Residente radica una PQRS válida | Se guarda con radicado único `PQRS-AAAA-NNNN`, estado `Radicada` y fecha límite a 15 días hábiles |
| M3-CP-02 | Residente intenta radicar con categoría inexistente o apartamento no asociado | La API rechaza la solicitud y no crea registros |
| M3-CP-03 | Residente consulta sus PQRS y filtra por estado/categoría | Solo ve sus registros y el resumen coincide con los filtros |
| M3-CP-04 | Administrador marca una PQRS como `Respondida` sin escribir respuesta | La API responde `422` y el estado no cambia |
| M3-CP-05 | Administrador responde y cambia el estado | Se registra la respuesta y queda el cambio en `pqrs_seguimiento` |
| M3-CP-06 | Residente consulta el seguimiento de su PQRS | Ve los cambios en orden cronológico; otro residente recibe `404` |
| M3-CP-07 | Residente califica una respuesta con 1 a 5 | La calificación queda guardada una sola vez y solo para su PQRS respondida |
| M3-CP-08 | Administrador filtra por estado y categoría | El reporte muestra conteos por estado/categoría y promedio de días hasta la primera respuesta |
| M3-CP-09 | Residente adjunta PDF/JPG/PNG válido y luego intenta un archivo no permitido o mayor a 5 MB | El válido se puede consultar con sesión y propiedad; el inválido se rechaza |
| M3-CP-10 | Se consulta una PQRS vencida que no está cerrada | Aparece marcada en rojo en la pantalla |

La colección `docs/postman/rf-10-14/pqrs.postman_collection.json` recorre el flujo principal; para la prueba de adjunto se debe seleccionar manualmente un archivo en la solicitud de radicación.

## 4. Casos de prueba de M4 (portería y visitantes)

| Caso | Pasos | Resultado esperado |
| --- | --- | --- |
| M4-CP-01 | Residente preautoriza un visitante con vehículo y placa | La autorización aparece en el listado del residente con fecha, placa y apartamento |
| M4-CP-02 | Portero busca el documento del visitante autorizado | Muestra nombre, apartamento, torre, vehículo y estado `Fuera` |
| M4-CP-03 | Portero registra el ingreso seleccionando un parqueadero libre | El ingreso queda con fecha y hora, el parqueadero pasa a `Ocupado` |
| M4-CP-04 | Portero intenta registrar el mismo visitante otra vez | Error: el visitante ya está dentro |
| M4-CP-05 | Portero intenta asignar un parqueadero ocupado | Error: el parqueadero ya está ocupado |
| M4-CP-06 | Portero registra la salida | El ingreso queda `Fuera`, con fecha de salida, y el parqueadero vuelve a `Libre` |
| M4-CP-07 | Administrador o portero crea, edita y elimina un parqueadero libre | Las tres operaciones responden correctamente y el listado se actualiza |
| M4-CP-08 | Se intenta eliminar un parqueadero ocupado | Error: no se puede eliminar un parqueadero ocupado |
| M4-CP-09 | Bitácora sin filtros | Lista los últimos 100 ingresos con visitante, torre, apartamento, parqueadero, portero y estado |
| M4-CP-10 | Bitácora filtrada por fecha, torre y apartamento | Solo devuelve las filas que cumplen los filtros |
| M4-CP-11 | Un visitante sin salida registrada en la bitácora | Aparece con estado `Dentro` |

## 5. Registro de bugs

| Bug | Requisito | Rol | Severidad | Estado | Notas |
| --- | --- | --- | --- | --- | --- |
| — | — | — | — | — | Sin bugs abiertos al cierre de M4 |

Severidades: `Crítica` bloquea la demo, `Alta` rompe un requisito, `Media` molesta pero permite continuar, `Baja` cosmética.

Un bug crítico se cierra antes del 10/11. Todo PR entra a `DEVELOPMENT` revisado por otra persona, según la definición de terminado del documento.
