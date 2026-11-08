<?php
require_once __DIR__ . '/../models/sesion.php';
require_once __DIR__ . '/../models/comunicado.php';
$usuario = usuarioActual();
session_write_close();

function responderComunicados($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($usuario == null) {
    responderComunicados(401, ['error' => 'Debe iniciar sesión.']);
}
$rol = strtolower($usuario['rol']);
if ($rol != 'administrador' && $rol != 'residente') {
    responderComunicados(403, ['error' => 'No tiene permisos para consultar este recurso.']);
}

$modelo = new Comunicado($conexion);
$usuarioId = intval($usuario['id']);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $comunicados = $modelo->listarVigentes($usuarioId);
    if ($comunicados === false) {
        responderComunicados(500, ['error' => 'No fue posible consultar los comunicados.']);
    }
    echo json_encode($comunicados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Allow: GET, POST');
    responderComunicados(405, ['error' => 'Método no permitido.']);
}

if (isset($_GET['id'])) {
    if ($rol != 'residente') {
        responderComunicados(403, ['error' => 'La marca de lectura es exclusiva de residentes.']);
    }
    $resultado = $modelo->marcarLeido(intval($_GET['id']), $usuarioId);
    if ($resultado == 'no_encontrado') {
        responderComunicados(404, ['error' => 'Comunicado no encontrado o no vigente.']);
    }
    if ($resultado === false) {
        responderComunicados(500, ['error' => 'No fue posible marcar el comunicado como leído.']);
    }
    responderComunicados(200, ['message' => 'Comunicado marcado como leído.']);
}

if ($rol != 'administrador') {
    responderComunicados(403, ['error' => 'Solo un administrador puede publicar comunicados.']);
}
$datos = json_decode(file_get_contents('php://input'), true);
$datos = is_array($datos) ? $datos : [];
$titulo = isset($datos['titulo']) && is_string($datos['titulo']) ? trim($datos['titulo']) : '';
$contenido = isset($datos['contenido']) && is_string($datos['contenido']) ? trim($datos['contenido']) : '';
$vigenteHasta = isset($datos['vigente_hasta']) && is_string($datos['vigente_hasta']) ? $datos['vigente_hasta'] : '';
if (
    $titulo == '' || strlen($titulo) > 150 || $contenido == '' ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $vigenteHasta) ||
    !checkdate(intval(substr($vigenteHasta, 5, 2)), intval(substr($vigenteHasta, 8, 2)), intval(substr($vigenteHasta, 0, 4))) ||
    $vigenteHasta < date('Y-m-d')
) {
    responderComunicados(422, ['error' => 'Indique título, contenido y una fecha de vigencia válida.']);
}

$id = $modelo->publicar($titulo, $contenido, $vigenteHasta, $usuarioId);
if ($id === false) {
    responderComunicados(500, ['error' => 'No fue posible publicar el comunicado.']);
}
responderComunicados(201, ['message' => 'Comunicado publicado.', 'id' => $id]);
