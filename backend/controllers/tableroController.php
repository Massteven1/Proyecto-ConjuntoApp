<?php
require_once __DIR__ . '/../models/sesion.php';
require_once __DIR__ . '/../models/tablero.php';
$usuario = usuarioActual();
session_write_close();

function responderTablero($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    header('Allow: GET');
    responderTablero(405, ['error' => 'Método no permitido.']);
}
if ($usuario == null) {
    responderTablero(401, ['error' => 'Debe iniciar sesión.']);
}
if (strtolower($usuario['rol']) != 'administrador') {
    responderTablero(403, ['error' => 'Solo un administrador puede consultar el tablero.']);
}

$modelo = new Tablero($conexion);
$resumen = $modelo->consultarResumen();
if ($resumen === false) {
    responderTablero(500, ['error' => 'No fue posible consultar el tablero.']);
}
echo json_encode($resumen, JSON_UNESCAPED_UNICODE);
