<?php
header('Content-Type: application/json; charset=utf-8');
// 1. Permitir explícitamente a tu frontend de Angular
header("Access-Control-Allow-Origin: http://localhost:4200");

// 2. Permitir los métodos HTTP que usa Angular
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// 3. Permitir las cabeceras estándar que envía Angular (como Content-Type)
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 4. Responder inmediatamente a la petición de verificación previa (Preflight / OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
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

try {
    require_once '../models/cuotas.php';
    $modelo = new Cuotas();
    echo json_encode(
        $modelo->generarCuotas($periodo, (float) $presupuestoBase, $fechaVencimiento),
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible generar las cuotas.', 'detalle' => $error->getMessage()]);
}
