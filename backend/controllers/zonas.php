<?php
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

function responder(int $status, array $respuesta): void
{
    http_response_code($status);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../models/sesion.php';

$usuario = usuarioActual();
session_write_close();

if ($usuario === null) {
    responder(401, ['message' => 'Debe iniciar sesión.']);
}

if (($usuario['rol'] ?? '') !== 'Administrador') {
    responder(403, ['message' => 'Solo el administrador puede gestionar las zonas comunes.']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../models/conexion.php';
    mysqli_set_charset($conexion, 'utf8mb4');
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        $pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
        $pagina = $pagina !== false && $pagina > 0 ? $pagina : 1;
        $porPagina = 20;

        $totalResultado = $conexion->query('SELECT COUNT(*) AS total FROM zonas_comunes WHERE activo = 1');
        $total = (int) $totalResultado->fetch_assoc()['total'];
        $pagina = min($pagina, max(1, (int) ceil($total / $porPagina)));
        $desplazamiento = ($pagina - 1) * $porPagina;
        $consulta = $conexion->prepare(
            'SELECT id, nombre, aforo, hora_apertura, hora_cierre, costo, requiere_deposito
             FROM zonas_comunes
             WHERE activo = 1
             ORDER BY nombre
             LIMIT ? OFFSET ?'
        );
        $consulta->bind_param('ii', $porPagina, $desplazamiento);
        $consulta->execute();
        $zonas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);

        responder(200, [
            'items' => $zonas,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'total' => $total,
        ]);
    }

    if (!in_array($metodo, ['POST', 'PUT', 'DELETE'], true)) {
        responder(405, ['message' => 'Método no permitido.']);
    }

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($metodo !== 'POST' && ($id === false || $id === null || $id < 1)) {
        responder(422, ['message' => 'El identificador de la zona no es válido.']);
    }

    if ($metodo === 'DELETE') {
        $consulta = $conexion->prepare('UPDATE zonas_comunes SET activo = 0 WHERE id = ? AND activo = 1');
        $consulta->bind_param('i', $id);
        $consulta->execute();

        if ($consulta->affected_rows === 0) {
            responder(404, ['message' => 'La zona común no existe o ya está inactiva.']);
        }

        responder(200, ['message' => 'Zona común inactivada correctamente.']);
    }

    $datos = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($datos)) {
        responder(400, ['message' => 'La solicitud no contiene datos válidos.']);
    }

    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $aforo = filter_var($datos['aforo'] ?? null, FILTER_VALIDATE_INT);
    $apertura = (string) ($datos['hora_apertura'] ?? '');
    $cierre = (string) ($datos['hora_cierre'] ?? '');
    $costo = (string) ($datos['costo'] ?? '');
    $deposito = $datos['requiere_deposito'] ?? null;

    if (!preg_match('/^.{1,100}$/us', $nombre)) {
        responder(422, ['message' => 'El nombre es obligatorio y no puede superar 100 caracteres.']);
    }
    if ($aforo === false || $aforo < 1 || $aforo > 2147483647) {
        responder(422, ['message' => 'El aforo debe ser un número entero mayor que cero.']);
    }
    if (
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $apertura) ||
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $cierre) ||
        $apertura >= $cierre
    ) {
        responder(422, ['message' => 'El horario no es válido: la apertura debe ser anterior al cierre.']);
    }
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $costo)) {
        responder(422, ['message' => 'El costo debe ser un valor no negativo con máximo dos decimales.']);
    }
    if (!in_array($deposito, [true, false, 0, 1, '0', '1'], true)) {
        responder(422, ['message' => 'Indique si la zona requiere depósito.']);
    }
    $deposito = (int) (bool) $deposito;

    if ($metodo === 'POST') {
        $consulta = $conexion->prepare(
            'INSERT INTO zonas_comunes
                (nombre, aforo, hora_apertura, hora_cierre, costo, requiere_deposito)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $consulta->bind_param('sisssi', $nombre, $aforo, $apertura, $cierre, $costo, $deposito);
        $consulta->execute();
        responder(201, ['message' => 'Zona común creada correctamente.', 'id' => $conexion->insert_id]);
    }

    $consulta = $conexion->prepare(
        'UPDATE zonas_comunes
         SET nombre = ?, aforo = ?, hora_apertura = ?, hora_cierre = ?, costo = ?, requiere_deposito = ?
         WHERE id = ? AND activo = 1'
    );
    $consulta->bind_param('sisssii', $nombre, $aforo, $apertura, $cierre, $costo, $deposito, $id);
    $consulta->execute();

    if ($consulta->affected_rows === 0) {
        $existe = $conexion->prepare('SELECT id FROM zonas_comunes WHERE id = ? AND activo = 1');
        $existe->bind_param('i', $id);
        $existe->execute();
        if ($existe->get_result()->num_rows === 0) {
            responder(404, ['message' => 'La zona común no existe o está inactiva.']);
        }
    }

    responder(200, ['message' => 'Zona común actualizada correctamente.']);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    responder(500, ['message' => 'No fue posible guardar los cambios de la zona común.']);
}
