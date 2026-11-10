<?php
require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../models/sesion.php';

$usuario = usuarioActual();
if ($usuario === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Debe iniciar sesión.']);
    exit();
}
if (strtolower($usuario['rol']) !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'Solo un administrador puede generar cuotas.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST, OPTIONS');
    echo json_encode(['error' => 'Método no permitido. Use POST.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$periodo = is_array($data) ? ($data['periodo'] ?? null) : null;
$presupuestoBase = is_array($data) ? ($data['presupuesto_base'] ?? null) : null;
$fechaVencimiento = is_array($data) ? ($data['fecha_vencimiento'] ?? null) : null;
$fechaValidada = is_string($fechaVencimiento)
    ? DateTime::createFromFormat('!Y-m-d', $fechaVencimiento)
    : false;

if (
    !is_string($periodo) ||
    !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo) ||
    !is_numeric($presupuestoBase) ||
    (float) $presupuestoBase <= 0 ||
    !$fechaValidada ||
    $fechaValidada->format('Y-m-d') !== $fechaVencimiento
) {
    http_response_code(400);
    echo json_encode(['error' => 'Envíe periodo (AAAA-MM), presupuesto_base mayor que cero y fecha_vencimiento (AAAA-MM-DD).']);
    exit();
}

require_once __DIR__ . '/../models/cuotas.php';
$modelo = new Cuotas();
$resultado = $modelo->generarCuotas($periodo, (float) $presupuestoBase, $fechaVencimiento);
if ($resultado === false) {
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible generar las cuotas.']);
    exit();
}
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
