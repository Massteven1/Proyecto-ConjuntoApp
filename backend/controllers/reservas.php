<?php
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

function responderReservas(int $status, array $respuesta): never
{
    http_response_code($status);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cancelarTransaccion(mysqli $conexion, int $status, string $mensaje): never
{
    $conexion->rollback();
    responderReservas($status, ['message' => $mensaje]);
}

function validarFechaReserva(string $fecha): bool
{
    $fechaValida = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $fechaValida !== false && $fechaValida->format('Y-m-d') === $fecha;
}

function apartamentoEnMora(mysqli $conexion, int $apartamentoId): bool
{
    $consulta = $conexion->prepare(
        'SELECT COUNT(*) AS cuotas_en_mora
         FROM cuotas
         WHERE apartamento_id = ? AND saldo > 0 AND fecha_vencimiento < CURDATE()'
    );
    $consulta->bind_param('i', $apartamentoId);
    $consulta->execute();
    return (int) $consulta->get_result()->fetch_assoc()['cuotas_en_mora'] > 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../models/sesion.php';

$usuario = usuarioActual();
session_write_close();

if ($usuario === null) {
    responderReservas(401, ['message' => 'Debe iniciar sesión.']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$transaccionAbierta = false;

try {
    require_once __DIR__ . '/../models/conexion.php';
    mysqli_set_charset($conexion, 'utf8mb4');
    $metodo = $_SERVER['REQUEST_METHOD'];
    $rol = $usuario['rol'] ?? '';
    $usuarioId = (int) ($usuario['id'] ?? 0);

    if ($metodo === 'GET') {
        $accion = $_GET['accion'] ?? 'disponibilidad';

        if ($accion === 'disponibilidad') {
            if (!in_array($rol, ['Residente', 'Administrador'], true)) {
                responderReservas(403, ['message' => 'No tiene permiso para consultar la disponibilidad.']);
            }

            $fecha = (string) ($_GET['fecha'] ?? date('Y-m-d'));
            if (!validarFechaReserva($fecha)) {
                responderReservas(422, ['message' => 'La fecha no es válida.']);
            }

            $resultadoZonas = $conexion->query(
                'SELECT id, nombre, aforo, hora_apertura, hora_cierre, costo, requiere_deposito
                 FROM zonas_comunes WHERE activo = 1 ORDER BY nombre'
            );
            $zonas = $resultadoZonas->fetch_all(MYSQLI_ASSOC);

            $apartamentos = [];
            if ($rol === 'Residente') {
                $consultaApartamentos = $conexion->prepare(
                    'SELECT DISTINCT a.id, t.nombre AS torre, a.numero AS apartamento
                     FROM residencias r
                     INNER JOIN apartamentos a ON a.id = r.apartamento_id
                     INNER JOIN torres t ON t.id = a.torre_id
                     WHERE r.usuario_id = ?
                       AND r.fecha_inicio <= CURDATE()
                       AND (r.fecha_fin IS NULL OR r.fecha_fin >= CURDATE())
                     ORDER BY t.nombre, a.numero'
                );
                $consultaApartamentos->bind_param('i', $usuarioId);
                $consultaApartamentos->execute();
                $apartamentos = $consultaApartamentos->get_result()->fetch_all(MYSQLI_ASSOC);
            }

            $consultaReservas = $conexion->prepare(
                "SELECT r.id, r.zona_id, z.nombre AS zona_nombre, r.fecha,
                        r.hora_inicio, r.hora_fin, r.estado
                 FROM reservas r
                 INNER JOIN zonas_comunes z ON z.id = r.zona_id
                 WHERE r.fecha = ? AND r.estado IN ('Pendiente', 'Aprobada')
                 ORDER BY z.nombre, r.hora_inicio"
            );
            $consultaReservas->bind_param('s', $fecha);
            $consultaReservas->execute();
            $reservas = $consultaReservas->get_result()->fetch_all(MYSQLI_ASSOC);

            responderReservas(200, [
                'zonas' => $zonas,
                'apartamentos' => $apartamentos,
                'reservas' => $reservas,
            ]);
        }

        if ($accion === 'mis-reservas') {
            if ($rol !== 'Residente') {
                responderReservas(403, ['message' => 'Solo los residentes pueden consultar sus reservas.']);
            }
            $consulta = $conexion->prepare(
                "SELECT r.id, z.nombre AS zona_nombre, r.fecha, r.hora_inicio, r.hora_fin,
                        r.estado, a.numero AS apartamento, t.nombre AS torre
                 FROM reservas r
                 INNER JOIN zonas_comunes z ON z.id = r.zona_id
                 INNER JOIN apartamentos a ON a.id = r.apartamento_id
                 INNER JOIN torres t ON t.id = a.torre_id
                 WHERE r.usuario_id = ? AND r.fecha >= CURDATE()
                   AND r.estado IN ('Pendiente', 'Aprobada')
                 ORDER BY r.fecha, r.hora_inicio"
            );
            $consulta->bind_param('i', $usuarioId);
            $consulta->execute();
            responderReservas(200, ['items' => $consulta->get_result()->fetch_all(MYSQLI_ASSOC)]);
        }

        if ($accion === 'pendientes') {
            if ($rol !== 'Administrador') {
                responderReservas(403, ['message' => 'Solo el administrador puede gestionar reservas.']);
            }
            $consulta = $conexion->prepare(
                "SELECT r.id, z.nombre AS zona_nombre, r.fecha, r.hora_inicio, r.hora_fin,
                        r.observaciones, a.numero AS apartamento, t.nombre AS torre,
                        CONCAT(u.nombres, ' ', u.apellidos) AS residente
                 FROM reservas r
                 INNER JOIN zonas_comunes z ON z.id = r.zona_id
                 INNER JOIN apartamentos a ON a.id = r.apartamento_id
                 INNER JOIN torres t ON t.id = a.torre_id
                 INNER JOIN usuarios u ON u.id = r.usuario_id
                 WHERE r.estado = 'Pendiente'
                 ORDER BY r.fecha, r.hora_inicio"
            );
            $consulta->execute();
            responderReservas(200, ['items' => $consulta->get_result()->fetch_all(MYSQLI_ASSOC)]);
        }

        if ($accion === 'hoy') {
            if ($rol !== 'Portero') {
                responderReservas(403, ['message' => 'Solo portería puede consultar las reservas del día.']);
            }
            $resultado = $conexion->query(
                "SELECT z.nombre AS zona_nombre, r.hora_inicio, r.hora_fin,
                        r.observaciones, a.numero AS apartamento, t.nombre AS torre,
                        CONCAT(u.nombres, ' ', u.apellidos) AS residente
                 FROM reservas r
                 INNER JOIN zonas_comunes z ON z.id = r.zona_id
                 INNER JOIN apartamentos a ON a.id = r.apartamento_id
                 INNER JOIN torres t ON t.id = a.torre_id
                 INNER JOIN usuarios u ON u.id = r.usuario_id
                 WHERE r.fecha = CURDATE() AND r.estado = 'Aprobada'
                 ORDER BY r.hora_inicio, z.nombre"
            );
            responderReservas(200, ['items' => $resultado->fetch_all(MYSQLI_ASSOC)]);
        }

        responderReservas(400, ['message' => 'La consulta de reservas no es válida.']);
    }

    if ($metodo === 'POST') {
        if ($rol !== 'Residente') {
            responderReservas(403, ['message' => 'Solo los residentes pueden crear reservas.']);
        }

        $datos = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($datos)) {
            responderReservas(400, ['message' => 'La solicitud no contiene datos válidos.']);
        }

        $zonaId = filter_var($datos['zona_id'] ?? null, FILTER_VALIDATE_INT);
        $apartamentoId = filter_var($datos['apartamento_id'] ?? null, FILTER_VALIDATE_INT);
        $fecha = (string) ($datos['fecha'] ?? '');
        $horaInicio = (string) ($datos['hora_inicio'] ?? '');
        $horaFin = (string) ($datos['hora_fin'] ?? '');
        $observaciones = trim((string) ($datos['observaciones'] ?? ''));

        if ($zonaId === false || $zonaId < 1 || $apartamentoId === false || $apartamentoId < 1) {
            responderReservas(422, ['message' => 'Seleccione una zona y un apartamento válidos.']);
        }
        if (
            !validarFechaReserva($fecha) ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaInicio) ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaFin) ||
            $horaInicio >= $horaFin
        ) {
            responderReservas(422, ['message' => 'La fecha y el horario de la reserva no son válidos.']);
        }
        if (mb_strlen($observaciones) > 1000) {
            responderReservas(422, ['message' => 'Las observaciones no pueden superar 1000 caracteres.']);
        }

        $consultaApartamento = $conexion->prepare(
            'SELECT id FROM residencias
             WHERE usuario_id = ? AND apartamento_id = ?
               AND fecha_inicio <= CURDATE()
               AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
             LIMIT 1'
        );
        $consultaApartamento->bind_param('ii', $usuarioId, $apartamentoId);
        $consultaApartamento->execute();
        if ($consultaApartamento->get_result()->num_rows === 0) {
            responderReservas(403, ['message' => 'No tiene una residencia activa en ese apartamento.']);
        }

        if (apartamentoEnMora($conexion, $apartamentoId)) {
            responderReservas(422, ['message' => 'El apartamento tiene más de dos cuotas vencidas pendientes.']);
        }

        $conexion->begin_transaction();
        $transaccionAbierta = true;

        $consultaZona = $conexion->prepare(
            'SELECT hora_apertura, hora_cierre
             FROM zonas_comunes WHERE id = ? AND activo = 1 FOR UPDATE'
        );
        $consultaZona->bind_param('i', $zonaId);
        $consultaZona->execute();
        $zona = $consultaZona->get_result()->fetch_assoc();

        if ($zona === null) {
            cancelarTransaccion($conexion, 404, 'La zona común no existe o está inactiva.');
        }
        if ($horaInicio . ':00' < $zona['hora_apertura'] || $horaFin . ':00' > $zona['hora_cierre']) {
            cancelarTransaccion($conexion, 422, 'La reserva debe estar dentro del horario de la zona.');
        }

        $consultaFecha = $conexion->prepare('SELECT ? < CURDATE() AS fecha_pasada');
        $consultaFecha->bind_param('s', $fecha);
        $consultaFecha->execute();
        if ((int) $consultaFecha->get_result()->fetch_assoc()['fecha_pasada'] === 1) {
            cancelarTransaccion($conexion, 422, 'No se pueden crear reservas para fechas pasadas.');
        }

        $consultaCruce = $conexion->prepare(
            "SELECT id FROM reservas
             WHERE zona_id = ? AND fecha = ?
               AND estado IN ('Pendiente', 'Aprobada')
               AND hora_inicio < ? AND hora_fin > ?
             LIMIT 1"
        );
        $inicioSql = $horaInicio . ':00';
        $finSql = $horaFin . ':00';
        $consultaCruce->bind_param('isss', $zonaId, $fecha, $finSql, $inicioSql);
        $consultaCruce->execute();
        if ($consultaCruce->get_result()->num_rows > 0) {
            cancelarTransaccion($conexion, 409, 'Ese horario ya está reservado para la zona seleccionada.');
        }

        $insertar = $conexion->prepare(
            "INSERT INTO reservas
                (zona_id, apartamento_id, usuario_id, fecha, hora_inicio, hora_fin, estado, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, 'Pendiente', ?)"
        );
        $insertar->bind_param(
            'iiissss',
            $zonaId,
            $apartamentoId,
            $usuarioId,
            $fecha,
            $inicioSql,
            $finSql,
            $observaciones
        );
        $insertar->execute();
        $reservaId = $conexion->insert_id;
        $conexion->commit();
        $transaccionAbierta = false;

        responderReservas(201, ['message' => 'La reserva quedó pendiente de aprobación.', 'id' => $reservaId]);
    }

    if ($metodo === 'PUT') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $datos = json_decode(file_get_contents('php://input') ?: '', true);
        $accion = is_array($datos) ? (string) ($datos['accion'] ?? '') : '';

        if ($id === false || $id < 1 || !in_array($accion, ['aprobar', 'rechazar', 'cancelar'], true)) {
            responderReservas(422, ['message' => 'La acción o el identificador de la reserva no son válidos.']);
        }

        if ($accion === 'cancelar' && $rol !== 'Residente') {
            responderReservas(403, ['message' => 'Solo el residente puede cancelar su reserva.']);
        }
        if (in_array($accion, ['aprobar', 'rechazar'], true) && $rol !== 'Administrador') {
            responderReservas(403, ['message' => 'Solo el administrador puede aprobar o rechazar reservas.']);
        }

        $conexion->begin_transaction();
        $transaccionAbierta = true;
        $consultaReserva = $conexion->prepare(
            'SELECT id, zona_id, apartamento_id, usuario_id, fecha, hora_inicio, hora_fin, estado
             FROM reservas WHERE id = ? FOR UPDATE'
        );
        $consultaReserva->bind_param('i', $id);
        $consultaReserva->execute();
        $reserva = $consultaReserva->get_result()->fetch_assoc();

        if ($reserva === null) {
            cancelarTransaccion($conexion, 404, 'La reserva no existe.');
        }

        if ($accion === 'cancelar') {
            if ((int) $reserva['usuario_id'] !== $usuarioId) {
                cancelarTransaccion($conexion, 403, 'No puede cancelar una reserva de otro residente.');
            }
            $consultaAnticipacion = $conexion->prepare(
                'SELECT TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(?, ?)) >= 1440 AS puede_cancelar'
            );
            $consultaAnticipacion->bind_param('ss', $reserva['fecha'], $reserva['hora_inicio']);
            $consultaAnticipacion->execute();
            $puedeCancelar = (int) $consultaAnticipacion->get_result()->fetch_assoc()['puede_cancelar'] === 1;
            if (!in_array($reserva['estado'], ['Pendiente', 'Aprobada'], true) || !$puedeCancelar) {
                cancelarTransaccion(
                    $conexion,
                    422,
                    'La reserva solo se puede cancelar con al menos 24 horas de anticipación.'
                );
            }
            $nuevoEstado = 'Cancelada';
        } else {
            if ($reserva['estado'] !== 'Pendiente') {
                cancelarTransaccion($conexion, 409, 'La reserva ya no está pendiente de aprobación.');
            }
            if ($accion === 'aprobar') {
                $consultaZona = $conexion->prepare(
                    'SELECT hora_apertura, hora_cierre
                     FROM zonas_comunes WHERE id = ? AND activo = 1 FOR UPDATE'
                );
                $consultaZona->bind_param('i', $reserva['zona_id']);
                $consultaZona->execute();
                $zona = $consultaZona->get_result()->fetch_assoc();
                if ($zona === null) {
                    cancelarTransaccion($conexion, 409, 'La zona de esta reserva ya no está disponible.');
                }
                if (
                    $reserva['hora_inicio'] < $zona['hora_apertura'] ||
                    $reserva['hora_fin'] > $zona['hora_cierre']
                ) {
                    cancelarTransaccion(
                        $conexion,
                        409,
                        'No se puede aprobar: el horario ya no está dentro del horario de la zona.'
                    );
                }

                $cruce = $conexion->prepare(
                    "SELECT id FROM reservas
                     WHERE zona_id = ? AND fecha = ? AND id <> ?
                       AND estado IN ('Pendiente', 'Aprobada')
                       AND hora_inicio < ? AND hora_fin > ?
                     LIMIT 1"
                );
                $cruce->bind_param(
                    'isiss',
                    $reserva['zona_id'],
                    $reserva['fecha'],
                    $id,
                    $reserva['hora_fin'],
                    $reserva['hora_inicio']
                );
                $cruce->execute();
                if ($cruce->get_result()->num_rows > 0) {
                    cancelarTransaccion(
                        $conexion,
                        409,
                        'No se puede aprobar: el horario se cruza con otra reserva activa.'
                    );
                }
                if (apartamentoEnMora($conexion, (int) $reserva['apartamento_id'])) {
                    cancelarTransaccion(
                        $conexion,
                        422,
                        'No se puede aprobar: el apartamento tiene más de dos cuotas vencidas pendientes.'
                    );
                }
                $nuevoEstado = 'Aprobada';
            } else {
                $nuevoEstado = 'Rechazada';
            }
        }

        $actualizar = $conexion->prepare('UPDATE reservas SET estado = ? WHERE id = ?');
        $actualizar->bind_param('si', $nuevoEstado, $id);
        $actualizar->execute();
        $conexion->commit();
        $transaccionAbierta = false;

        responderReservas(200, ['message' => 'La reserva se actualizó correctamente.']);
    }

    responderReservas(405, ['message' => 'Método no permitido.']);
} catch (mysqli_sql_exception $exception) {
    if ($transaccionAbierta) {
        $conexion->rollback();
    }
    error_log($exception->getMessage());
    responderReservas(500, ['message' => 'No fue posible procesar la reserva.']);
}
