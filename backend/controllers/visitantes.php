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
require_once __DIR__ . '/../models/visitante.php';
require_once __DIR__ . '/../models/autorizacion.php';

$visitante = new Visitante($conexion);
$autorizacion = new Autorizacion($conexion);
$control = $_GET['control'] ?? '';
$usuario_id = intval($usuario['id']);

switch ($control) {
    case 'listar':
        $vec = ["OK", $autorizacion->listar($usuario_id)];
        break;

    case 'apartamentos':
        $vec = ["OK", $autorizacion->apartamentos($usuario_id)];
        break;

    case 'preautorizar':
        $params = json_decode(file_get_contents('php://input'));

        $documento = mysqli_real_escape_string($conexion, $params->documento ?? '');
        $nombre = mysqli_real_escape_string($conexion, $params->nombre ?? '');
        $telefono = mysqli_real_escape_string($conexion, $params->telefono ?? '');
        $fecha_visita = mysqli_real_escape_string($conexion, $params->fecha_visita ?? '');
        $placa = mysqli_real_escape_string($conexion, $params->placa ?? '');
        $apartamento_id = intval($params->apartamento_id ?? 0);
        $con_vehiculo = intval($params->con_vehiculo ?? 0);

        if ($documento === '' || $nombre === '' || $apartamento_id === 0 || $fecha_visita === '') {
            $vec = ["ERROR", "Complete los datos del visitante"];
            break;
        }

        $encontrado = $visitante->buscarPorDocumento($documento);

        if ($encontrado) {
            $visitante_id = intval($encontrado['id']);
        } else {
            $res = $visitante->insertar($documento, $nombre, $telefono);

            if ($res[0] === 'ERROR') {
                $vec = $res;
                break;
            }

            $visitante_id = intval($res[1]);
        }

        $vec = $autorizacion->insertar($visitante_id, $apartamento_id, $usuario_id, $fecha_visita, $con_vehiculo, $placa);
        break;

    default:
        http_response_code(400);
        $vec = ["ERROR", "Control no valido"];
        break;
}

echo json_encode($vec);
?>
