<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responderInmuebles(int $estado, array $datos): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/../models/sesion.php';
$usuario = usuarioActual();
session_write_close();
if ($usuario === null) {
    responderInmuebles(401, ['message' => 'Debe iniciar sesión.']);
}
if (($usuario['rol'] ?? '') !== 'Administrador') {
    responderInmuebles(403, ['message' => 'Solo el administrador puede gestionar torres y apartamentos.']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../models/conexion.php';
    $metodo = $_SERVER['REQUEST_METHOD'];
    $tipo = (string) ($_GET['tipo'] ?? '');
    if (!in_array($tipo, ['torre', 'apartamento'], true)) {
        responderInmuebles(422, ['message' => 'Indique si desea gestionar una torre o un apartamento.']);
    }

    if ($metodo === 'GET') {
        $torres = $conexion->query(
            'SELECT t.id, t.nombre, t.pisos, COUNT(a.id) AS apartamentos
             FROM torres t LEFT JOIN apartamentos a ON a.torre_id = t.id
             GROUP BY t.id, t.nombre, t.pisos ORDER BY t.nombre'
        )->fetch_all(MYSQLI_ASSOC);
        $apartamentos = $conexion->query(
            'SELECT a.id, a.torre_id, t.nombre AS torre, a.numero, a.piso, a.area_m2, a.coeficiente
             FROM apartamentos a INNER JOIN torres t ON t.id = a.torre_id
             ORDER BY t.nombre, a.numero'
        )->fetch_all(MYSQLI_ASSOC);
        responderInmuebles(200, ['torres' => $torres, 'apartamentos' => $apartamentos]);
    }

    if (!in_array($metodo, ['POST', 'PUT', 'DELETE'], true)) {
        responderInmuebles(405, ['message' => 'Método no permitido.']);
    }
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($metodo !== 'POST' && ($id === false || $id === null || $id < 1)) {
        responderInmuebles(422, ['message' => 'El identificador no es válido.']);
    }

    if ($metodo === 'DELETE') {
        $tabla = $tipo === 'torre' ? 'torres' : 'apartamentos';
        $consulta = $conexion->prepare("DELETE FROM $tabla WHERE id = ?");
        $consulta->bind_param('i', $id);
        $consulta->execute();
        if ($consulta->affected_rows === 0) {
            responderInmuebles(404, ['message' => 'El registro no existe.']);
        }
        responderInmuebles(200, ['message' => $tipo === 'torre' ? 'Torre eliminada.' : 'Apartamento eliminado.']);
    }

    $datos = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($datos)) {
        responderInmuebles(400, ['message' => 'La solicitud no contiene datos válidos.']);
    }

    if ($tipo === 'torre') {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $pisos = filter_var($datos['pisos'] ?? null, FILTER_VALIDATE_INT);
        if ($nombre === '' || strlen($nombre) > 50 || $pisos === false || $pisos < 1) {
            responderInmuebles(422, ['message' => 'Escribe el nombre de la torre y una cantidad válida de pisos.']);
        }
        if ($metodo === 'POST') {
            if ((int) $conexion->query('SELECT COUNT(*) AS total FROM torres')->fetch_assoc()['total'] >= 4) {
                responderInmuebles(409, ['message' => 'El conjunto ya tiene el máximo de cuatro torres.']);
            }
            $consulta = $conexion->prepare('INSERT INTO torres (nombre, pisos) VALUES (?, ?)');
            $consulta->bind_param('si', $nombre, $pisos);
            $consulta->execute();
            responderInmuebles(201, ['message' => 'Torre creada.']);
        }
        $consulta = $conexion->prepare('SELECT MAX(piso) AS piso_maximo FROM apartamentos WHERE torre_id = ?');
        $consulta->bind_param('i', $id);
        $consulta->execute();
        $pisoMaximo = $consulta->get_result()->fetch_assoc()['piso_maximo'];
        if ($pisoMaximo !== null && $pisos < (int) $pisoMaximo) {
            responderInmuebles(422, ['message' => 'La torre no puede tener menos pisos que sus apartamentos registrados.']);
        }
        $consulta = $conexion->prepare('UPDATE torres SET nombre = ?, pisos = ? WHERE id = ?');
        $consulta->bind_param('sii', $nombre, $pisos, $id);
        $consulta->execute();
        if ($consulta->affected_rows === 0) {
            $existe = $conexion->prepare('SELECT id FROM torres WHERE id = ?');
            $existe->bind_param('i', $id);
            $existe->execute();
            if ($existe->get_result()->num_rows === 0) responderInmuebles(404, ['message' => 'La torre no existe.']);
        }
        responderInmuebles(200, ['message' => 'Torre actualizada.']);
    }

    $torreId = filter_var($datos['torre_id'] ?? null, FILTER_VALIDATE_INT);
    $numero = trim((string) ($datos['numero'] ?? ''));
    $piso = filter_var($datos['piso'] ?? null, FILTER_VALIDATE_INT);
    $area = filter_var($datos['area_m2'] ?? null, FILTER_VALIDATE_FLOAT);
    $coeficiente = filter_var($datos['coeficiente'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($torreId === false || $torreId < 1 || $numero === '' || strlen($numero) > 20 ||
        $piso === false || $piso < 1 || $area === false || $area <= 0 ||
        $coeficiente === false || $coeficiente <= 0 || $coeficiente > 1) {
        responderInmuebles(422, ['message' => 'Completa los datos del apartamento con valores válidos.']);
    }

    $consulta = $conexion->prepare('SELECT pisos FROM torres WHERE id = ?');
    $consulta->bind_param('i', $torreId);
    $consulta->execute();
    $torre = $consulta->get_result()->fetch_assoc();
    if ($torre === null) responderInmuebles(422, ['message' => 'La torre seleccionada no existe.']);
    if ($piso > (int) $torre['pisos']) responderInmuebles(422, ['message' => 'El piso supera la cantidad de pisos de la torre.']);

    $consulta = $conexion->prepare('SELECT COUNT(*) AS total FROM apartamentos WHERE torre_id = ? AND id <> ?');
    $apartamentoActualId = $metodo === 'PUT' ? (int) $id : 0;
    $consulta->bind_param('ii', $torreId, $apartamentoActualId);
    $consulta->execute();
    if ((int) $consulta->get_result()->fetch_assoc()['total'] >= 10) {
        responderInmuebles(409, ['message' => 'La torre ya tiene el máximo de diez apartamentos.']);
    }

    if ($metodo === 'POST') {
        $consulta = $conexion->prepare(
            'INSERT INTO apartamentos (torre_id, numero, piso, area_m2, coeficiente) VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->bind_param('isidd', $torreId, $numero, $piso, $area, $coeficiente);
        $consulta->execute();
        responderInmuebles(201, ['message' => 'Apartamento creado.']);
    }

    $consulta = $conexion->prepare(
        'UPDATE apartamentos SET torre_id = ?, numero = ?, piso = ?, area_m2 = ?, coeficiente = ? WHERE id = ?'
    );
    $consulta->bind_param('isiddi', $torreId, $numero, $piso, $area, $coeficiente, $id);
    $consulta->execute();
    if ($consulta->affected_rows === 0) {
        $existe = $conexion->prepare('SELECT id FROM apartamentos WHERE id = ?');
        $existe->bind_param('i', $id);
        $existe->execute();
        if ($existe->get_result()->num_rows === 0) responderInmuebles(404, ['message' => 'El apartamento no existe.']);
    }
    responderInmuebles(200, ['message' => 'Apartamento actualizado.']);
} catch (mysqli_sql_exception $exception) {
    if ((int) $exception->getCode() === 1062) {
        responderInmuebles(409, ['message' => 'Ya existe un apartamento con ese número en la torre.']);
    }
    if ((int) $exception->getCode() === 1451) {
        responderInmuebles(409, ['message' => 'No se puede eliminar: hay registros que todavía usan este dato.']);
    }
    error_log($exception->getMessage());
    responderInmuebles(500, ['message' => 'No fue posible guardar los cambios de torres y apartamentos.']);
}
