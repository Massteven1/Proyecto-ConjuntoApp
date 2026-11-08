<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responderResidencias(int $estado, array $datos): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fechaResidenciaValida(string $fecha): bool
{
    $valor = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $valor !== false && $valor->format('Y-m-d') === $fecha;
}

require_once __DIR__ . '/../models/sesion.php';
$usuario = usuarioActual();
session_write_close();
if ($usuario === null) {
    responderResidencias(401, ['message' => 'Debe iniciar sesión.']);
}
if (($usuario['rol'] ?? '') !== 'Administrador') {
    responderResidencias(403, ['message' => 'Solo el administrador puede asociar residentes a apartamentos.']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../models/conexion.php';
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        $residencias = $conexion->query(
            'SELECT r.id, r.usuario_id, r.apartamento_id, r.tipo, r.fecha_inicio, r.fecha_fin,
                    u.nombres, u.apellidos, a.numero AS apartamento, t.nombre AS torre
             FROM residencias r
             INNER JOIN usuarios u ON u.id = r.usuario_id
             INNER JOIN apartamentos a ON a.id = r.apartamento_id
             INNER JOIN torres t ON t.id = a.torre_id
             ORDER BY r.fecha_fin IS NULL DESC, r.fecha_inicio DESC'
        )->fetch_all(MYSQLI_ASSOC);
        $residentes = $conexion->query(
            "SELECT u.id, u.nombres, u.apellidos FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.activo = 1 AND LOWER(r.nombre) = 'residente'
             ORDER BY u.apellidos, u.nombres"
        )->fetch_all(MYSQLI_ASSOC);
        $apartamentos = $conexion->query(
            'SELECT a.id, a.numero, t.nombre AS torre FROM apartamentos a
             INNER JOIN torres t ON t.id = a.torre_id ORDER BY t.nombre, a.numero'
        )->fetch_all(MYSQLI_ASSOC);
        responderResidencias(200, ['residencias' => $residencias, 'residentes' => $residentes, 'apartamentos' => $apartamentos]);
    }

    if (!in_array($metodo, ['POST', 'PUT'], true)) {
        responderResidencias(405, ['message' => 'Método no permitido.']);
    }
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($metodo === 'PUT' && ($id === false || $id === null || $id < 1)) {
        responderResidencias(422, ['message' => 'El identificador de la asociación no es válido.']);
    }
    $datos = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($datos)) {
        responderResidencias(400, ['message' => 'La solicitud no contiene datos válidos.']);
    }

    $usuarioId = filter_var($datos['usuario_id'] ?? null, FILTER_VALIDATE_INT);
    $apartamentoId = filter_var($datos['apartamento_id'] ?? null, FILTER_VALIDATE_INT);
    $tipo = (string) ($datos['tipo'] ?? '');
    $fechaInicio = (string) ($datos['fecha_inicio'] ?? '');
    $fechaFin = trim((string) ($datos['fecha_fin'] ?? ''));
    if ($usuarioId === false || $usuarioId < 1 || $apartamentoId === false || $apartamentoId < 1 ||
        !in_array($tipo, ['propietario', 'arrendatario'], true) || !fechaResidenciaValida($fechaInicio) ||
        ($fechaFin !== '' && (!fechaResidenciaValida($fechaFin) || $fechaFin < $fechaInicio))) {
        responderResidencias(422, ['message' => 'Completa la asociación con un residente, tipo y fechas válidas.']);
    }

    $consulta = $conexion->prepare(
        "SELECT u.id FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.id = ? AND u.activo = 1 AND LOWER(r.nombre) = 'residente'"
    );
    $consulta->bind_param('i', $usuarioId);
    $consulta->execute();
    if ($consulta->get_result()->num_rows === 0) {
        responderResidencias(422, ['message' => 'El usuario seleccionado no es un residente activo.']);
    }
    $consulta = $conexion->prepare('SELECT id FROM apartamentos WHERE id = ?');
    $consulta->bind_param('i', $apartamentoId);
    $consulta->execute();
    if ($consulta->get_result()->num_rows === 0) {
        responderResidencias(422, ['message' => 'El apartamento seleccionado no existe.']);
    }

    $fechaFinConsulta = $fechaFin !== '' ? $fechaFin : null;
    $excluirId = $metodo === 'PUT' ? (int) $id : 0;
    $consulta = $conexion->prepare(
        "SELECT id FROM residencias
         WHERE apartamento_id = ? AND id <> ?
           AND fecha_inicio <= COALESCE(?, '9999-12-31')
           AND COALESCE(fecha_fin, '9999-12-31') >= ?
         LIMIT 1"
    );
    $consulta->bind_param('iiss', $apartamentoId, $excluirId, $fechaFinConsulta, $fechaInicio);
    $consulta->execute();
    if ($consulta->get_result()->num_rows > 0) {
        responderResidencias(409, ['message' => 'El apartamento ya tiene un responsable en ese periodo.']);
    }

    if ($metodo === 'POST') {
        $consulta = $conexion->prepare(
            'INSERT INTO residencias (usuario_id, apartamento_id, tipo, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->bind_param('iisss', $usuarioId, $apartamentoId, $tipo, $fechaInicio, $fechaFinConsulta);
        $consulta->execute();
        responderResidencias(201, ['message' => 'Residente asociado al apartamento.']);
    }

    $consulta = $conexion->prepare(
        'UPDATE residencias SET usuario_id = ?, apartamento_id = ?, tipo = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ?'
    );
    $consulta->bind_param('iisssi', $usuarioId, $apartamentoId, $tipo, $fechaInicio, $fechaFinConsulta, $id);
    $consulta->execute();
    if ($consulta->affected_rows === 0) {
        $existe = $conexion->prepare('SELECT id FROM residencias WHERE id = ?');
        $existe->bind_param('i', $id);
        $existe->execute();
        if ($existe->get_result()->num_rows === 0) responderResidencias(404, ['message' => 'La asociación no existe.']);
    }
    responderResidencias(200, ['message' => 'Asociación actualizada.']);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    responderResidencias(500, ['message' => 'No fue posible guardar la asociación.']);
}
