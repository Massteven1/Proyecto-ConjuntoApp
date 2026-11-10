<?php
require_once __DIR__ . '/../models/sesion.php';
require_once __DIR__ . '/../models/pqrs.php';
$usuario = usuarioActual();
session_write_close();

function responderPqrs($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Elimino un adjunto temporal si no se pudo completar la radicación.
function eliminarAdjuntoPqrs($adjunto)
{
    if ($adjunto === null) {
        return;
    }
    $ruta = dirname(__DIR__, 2) . '/storage/pqrs/' . basename($adjunto);
    if (is_file($ruta)) {
        unlink($ruta);
    }
}

if ($usuario == null) {
    responderPqrs(401, ['error' => 'Debe iniciar sesión.']);
}

// Limito el módulo a los roles que participan en el flujo de PQRS.
$rol = strtolower((string) ($usuario['rol'] ?? ''));
if (!in_array($rol, ['administrador', 'residente'], true)) {
    responderPqrs(403, ['error' => 'No tiene permisos para este módulo.']);
}

$modelo = new PqrsModel($conexion);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $accion = isset($_GET['action']) ? strtolower((string) $_GET['action']) : '';
    if ($accion === 'adjunto') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $adjunto = $modelo->adjunto($id, $usuario['id'], $rol);
        if ($adjunto === 'not_found' || $adjunto === null || $id <= 0) {
            responderPqrs(404, ['error' => 'El adjunto no existe o no tienes permiso para consultarlo.']);
        }
        if ($adjunto === false) {
            responderPqrs(500, ['error' => 'No fue posible consultar el adjunto.']);
        }

        $directorio = realpath(dirname(__DIR__, 2) . '/storage/pqrs');
        $ruta = $directorio ? realpath($directorio . '/' . basename($adjunto)) : false;
        if (!$directorio || !$ruta || strpos($ruta, $directorio . DIRECTORY_SEPARATOR) !== 0 || !is_file($ruta)) {
            responderPqrs(404, ['error' => 'El archivo adjunto no está disponible.']);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
        $mimesPermitidos = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mime, $mimesPermitidos, true)) {
            responderPqrs(415, ['error' => 'El tipo de archivo no está permitido.']);
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($ruta);
        exit;
    }

    if ($accion === 'historial') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            responderPqrs(422, ['error' => 'Indica una PQRS válida.']);
        }

        $historial = $modelo->historial($id, $usuario['id'], $rol);
        if ($historial === 'not_found') {
            responderPqrs(404, ['error' => 'La PQRS no existe o no tienes permiso para consultarla.']);
        }
        if ($historial === false) {
            responderPqrs(500, ['error' => 'No fue posible consultar el historial.']);
        }
        responderPqrs(200, ['items' => $historial]);
    }

    // Entrego la lista y el resumen aplicando los filtros solicitados.
    $estadosPermitidos = ['Radicada', 'En trámite', 'Respondida', 'Cerrada'];
    $estado = isset($_GET['estado']) ? (string) $_GET['estado'] : '';
    if ($estado !== '' && !in_array($estado, $estadosPermitidos, true)) {
        responderPqrs(422, ['error' => 'El filtro de estado no es válido.']);
    }
    $categoriaId = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : 0;
    $items = $modelo->listar($usuario['id'], $rol, $estado, $categoriaId);
    $resumen = $modelo->resumen($rol === 'administrador' ? null : $usuario['id'], $estado, $categoriaId);
    $categorias = $modelo->listarCategorias();
    $apartamentos = $rol === 'residente' ? $modelo->listarApartamentosResidente($usuario['id']) : [];
    if ($items === false || $resumen === false || $categorias === false || $apartamentos === false) {
        responderPqrs(500, ['error' => 'No fue posible consultar las PQRS.']);
    }

    responderPqrs(200, [
        'items' => $items,
        'resumen' => $resumen,
        'categorias' => $categorias,
        'apartamentos' => $apartamentos,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Allow: GET, POST');
    responderPqrs(405, ['error' => 'Método no permitido.']);
}

$accion = isset($_GET['action']) ? strtolower((string) $_GET['action']) : '';
$payload = $_POST;
if (empty($_POST) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $payload = json_decode(file_get_contents('php://input'), true);
}
$payload = is_array($payload) ? $payload : [];

if ($accion === 'estado' || $accion === 'respuesta') {
    if ($rol !== 'administrador') {
        responderPqrs(403, ['error' => 'Solo el administrador puede cambiar el estado y responder la PQRS.']);
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($payload['id']) ? intval($payload['id']) : 0);
    $estado = isset($payload['estado']) && is_string($payload['estado']) ? trim($payload['estado']) : '';
    $respuesta = isset($payload['respuesta']) && is_string($payload['respuesta']) ? trim($payload['respuesta']) : '';

    if ($id <= 0 || $estado === '') {
        responderPqrs(422, ['error' => 'Falta la PQRS o el estado a actualizar.']);
    }

    $resultado = $modelo->actualizarEstado($id, $estado, $respuesta, $usuario['id']);
    if ($resultado === 'invalid') {
        responderPqrs(422, ['error' => 'El estado suministrado no es válido.']);
    }
    if ($resultado === 'not_found') {
        responderPqrs(404, ['error' => 'La PQRS no existe.']);
    }
    if ($resultado === 'response_required') {
        responderPqrs(422, ['error' => 'Escribe la respuesta antes de marcar la PQRS como respondida.']);
    }
    if ($resultado === false) {
        responderPqrs(500, ['error' => 'No fue posible actualizar el estado.']);
    }

    responderPqrs(200, ['message' => 'Estado actualizado correctamente.']);
}

if ($accion === 'calificar') {
    if ($rol !== 'residente') {
        responderPqrs(403, ['error' => 'Solo un residente puede calificar la respuesta.']);
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($payload['id']) ? intval($payload['id']) : 0);
    $calificacion = isset($payload['calificacion']) ? intval($payload['calificacion']) : 0;

    if ($id <= 0 || $calificacion < 1 || $calificacion > 5) {
        responderPqrs(422, ['error' => 'La calificación debe estar entre 1 y 5.']);
    }

    $resultado = $modelo->calificar($id, $usuario['id'], $calificacion);
    if ($resultado === 'invalid') {
        responderPqrs(422, ['error' => 'La calificación no es válida.']);
    }
    if ($resultado === false) {
        responderPqrs(400, ['error' => 'No se pudo calificar esta PQRS.']);
    }

    responderPqrs(200, ['message' => 'Gracias por calificar la respuesta.']);
}

if ($rol !== 'residente') {
    responderPqrs(403, ['error' => 'Solo los residentes pueden radicar PQRS.']);
}

$tipo = isset($payload['tipo']) && is_string($payload['tipo']) ? trim($payload['tipo']) : '';
$categoriaId = isset($payload['categoria_id']) ? intval($payload['categoria_id']) : 0;
$apartamentoId = isset($payload['apartamento_id']) ? intval($payload['apartamento_id']) : 0;
$asunto = isset($payload['asunto']) && is_string($payload['asunto']) ? trim($payload['asunto']) : '';
$descripcion = isset($payload['descripcion']) && is_string($payload['descripcion']) ? trim($payload['descripcion']) : '';
$adjunto = null;

// Valido los campos antes de mover el archivo para no dejar adjuntos huérfanos.
if ($tipo === '' || $categoriaId <= 0 || $apartamentoId <= 0 || $asunto === '' || $descripcion === '') {
    responderPqrs(422, ['error' => 'Faltan datos obligatorios para registrar la PQRS.']);
}

// Acepto solo documentos e imágenes permitidos y guardo un nombre no predecible.
if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] !== UPLOAD_ERR_NO_FILE) {
    $archivo = $_FILES['adjunto'];
    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024) {
        responderPqrs(422, ['error' => 'El adjunto no se pudo cargar o supera los 5 MB.']);
    }

    $permitidos = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    if (!isset($permitidos[$mime])) {
        responderPqrs(422, ['error' => 'El adjunto debe ser un PDF, JPG o PNG.']);
    }

    $directorio = dirname(__DIR__, 2) . '/storage/pqrs';
    if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
        responderPqrs(500, ['error' => 'No fue posible preparar el almacenamiento de adjuntos.']);
    }

    $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombreArchivo)) {
        responderPqrs(500, ['error' => 'No fue posible guardar el adjunto.']);
    }
    $adjunto = '/uploads/pqrs/' . $nombreArchivo;
}

$resultado = $modelo->crear([
    'tipo' => $tipo,
    'categoria_id' => $categoriaId,
    'apartamento_id' => $apartamentoId,
    'asunto' => $asunto,
    'descripcion' => $descripcion,
    'adjunto' => $adjunto,
], $usuario['id']);

if ($resultado === 'invalid') {
    eliminarAdjuntoPqrs($adjunto);
    responderPqrs(422, ['error' => 'La PQRS no tiene datos válidos.']);
}
if ($resultado === 'apartment_not_allowed') {
    eliminarAdjuntoPqrs($adjunto);
    responderPqrs(403, ['error' => 'No tienes una residencia activa en el apartamento seleccionado.']);
}
if ($resultado === false) {
    eliminarAdjuntoPqrs($adjunto);
    responderPqrs(500, ['error' => 'No fue posible crear la PQRS.']);
}

responderPqrs(201, ['message' => 'PQRS creada correctamente.', 'id' => $resultado]);
