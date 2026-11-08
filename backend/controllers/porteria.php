<?php
require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/sesion.php';

$usuario = usuarioActual();
session_write_close();

if ($usuario == null) {
    http_response_code(401);
    echo json_encode(["ERROR", "Debe iniciar sesion"]);
    exit;
}

require_once __DIR__ . '/../models/conexion.php';
require_once __DIR__ . '/../models/ingreso.php';

$ingreso = new Ingreso($conexion);
$control = $_GET['control'] ?? '';
$portero_id = intval($usuario['id']);
$vec = [];

switch ($control) {
    case 'buscar':
        $documento = $_GET['documento'] ?? '';
        $documento = mysqli_real_escape_string($conexion, $documento);

        if ($documento === '') {
            $vec = ["ERROR", "Escriba el documento del visitante"];
            break;
        }

        $visitante = $ingreso->buscarVisitante($documento);

        if ($visitante == null) {
            $vec = ["ERROR", "No se encontro un visitante con ese documento"];
            break;
        }

        $abierto = $ingreso->buscarAbierto($visitante['id']);

        if ($abierto == null) {
            $visitante['dentro'] = 0;
            $visitante['fecha_ingreso'] = null;
        } else {
            $visitante['dentro'] = 1;
            $visitante['fecha_ingreso'] = $abierto['fecha_ingreso'];
        }

        $vec = ["OK", $visitante];
        break;

    case 'ingreso':
        $params = json_decode(file_get_contents('php://input'));

        $documento = '';
        if (isset($params->documento)) {
            $documento = $params->documento;
        }
        $documento = mysqli_real_escape_string($conexion, $documento);

        $visitante = $ingreso->buscarVisitante($documento);

        if ($visitante == null) {
            $vec = ["ERROR", "No se encontro un visitante con ese documento"];
            break;
        }

        if ($visitante['apartamento_id'] == null) {
            $vec = ["ERROR", "El visitante no tiene una autorizacion registrada"];
            break;
        }

        $abierto = $ingreso->buscarAbierto($visitante['id']);

        if ($abierto != null) {
            $vec = ["ERROR", "El visitante ya esta dentro"];
            break;
        }

        $vec = $ingreso->insertar($visitante['id'], $visitante['apartamento_id'], $portero_id, $visitante['placa']);
        break;

    case 'salida':
        $params = json_decode(file_get_contents('php://input'));

        $documento = '';
        if (isset($params->documento)) {
            $documento = $params->documento;
        }
        $documento = mysqli_real_escape_string($conexion, $documento);

        $visitante = $ingreso->buscarVisitante($documento);

        if ($visitante == null) {
            $vec = ["ERROR", "No se encontro un visitante con ese documento"];
            break;
        }

        $filas = $ingreso->registrarSalida($visitante['id']);

        if ($filas > 0) {
            $vec = ["OK", "Salida registrada"];
        } else {
            $vec = ["ERROR", "El visitante no esta dentro"];
        }
        break;

    case 'dentro':
        $vec = ["OK", $ingreso->listarDentro()];
        break;

    default:
        http_response_code(400);
        $vec = ["ERROR", "Control no valido"];
        break;
}

echo json_encode($vec);
?>
