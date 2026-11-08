<?php
require_once __DIR__ . '/../models/sesion.php';
require_once __DIR__ . '/../models/estadoCuenta.php';
$usuario = usuarioActual();
session_write_close();

function responderEstadoCuenta($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    header('Allow: GET');
    responderEstadoCuenta(405, ['error' => 'Método no permitido.']);
}
if ($usuario == null) {
    responderEstadoCuenta(401, ['error' => 'Debe iniciar sesión.']);
}
$rol = strtolower($usuario['rol']);
if ($rol != 'administrador' && $rol != 'residente') {
    responderEstadoCuenta(403, ['error' => 'No tiene permisos para consultar este recurso.']);
}

$modelo = new EstadoCuenta($conexion);
$cuentas = $modelo->listar(intval($usuario['id']), $rol == 'administrador');
if ($cuentas === false) {
    responderEstadoCuenta(500, ['error' => 'No fue posible consultar el estado de cuenta.']);
}
echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);
