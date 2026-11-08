<?php
require_once __DIR__ . '/../models/sesion.php';
require_once __DIR__ . '/../models/pagos.php';
$usuario = usuarioActual();
session_write_close();

function responderPagos($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($usuario == null) {
    responderPagos(401, ['error' => 'Debe iniciar sesión.']);
}
$rol = strtolower($usuario['rol']);
if ($rol != 'administrador' && $rol != 'residente') {
    responderPagos(403, ['error' => 'No tiene permisos para consultar este recurso.']);
}

$modelo = new Pagos($conexion);
$usuarioId = intval($usuario['id']);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $cuotas = $modelo->listarCuotasPendientes($usuarioId, $rol == 'administrador');
    if ($cuotas === false) {
        responderPagos(500, ['error' => 'No fue posible consultar las cuotas.']);
    }
    echo json_encode($cuotas, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Allow: GET, POST');
    responderPagos(405, ['error' => 'Método no permitido.']);
}
if ($rol != 'administrador') {
    responderPagos(403, ['error' => 'Solo un administrador puede registrar pagos.']);
}

$datos = json_decode(file_get_contents('php://input'), true);
$datos = is_array($datos) ? $datos : [];
$apartamentoId = intval(isset($datos['apartamento_id']) ? $datos['apartamento_id'] : 0);
$valor = isset($datos['valor']) ? $datos['valor'] : 0;
$fecha = isset($datos['fecha_pago']) && is_string($datos['fecha_pago']) ? $datos['fecha_pago'] : '';
$medio = isset($datos['medio']) && is_string($datos['medio']) ? trim($datos['medio']) : '';
$soporte = isset($datos['soporte']) && is_string($datos['soporte']) ? trim($datos['soporte']) : '';

if (
    $apartamentoId <= 0 || !is_numeric($valor) || $valor <= 0 ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ||
    !checkdate(intval(substr($fecha, 5, 2)), intval(substr($fecha, 8, 2)), intval(substr($fecha, 0, 4))) ||
    $medio == '' || strlen($medio) > 50 || strlen($soporte) > 255
) {
    responderPagos(422, ['error' => 'Revise apartamento, fecha, valor, medio y soporte.']);
}

$resultado = $modelo->registrarPago($apartamentoId, $valor, $fecha, $medio, $soporte, $usuarioId);
if (isset($resultado['error'])) {
    responderPagos($resultado['status'], ['error' => $resultado['error']]);
}
responderPagos(201, $resultado);
