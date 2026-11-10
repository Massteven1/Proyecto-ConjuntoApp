<?php
require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_save_path(__DIR__ . '/../sessions');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);

function responderAuth($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function datosSolicitudAuth()
{
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) {
        responderAuth(400, ['message' => 'La solicitud no contiene datos válidos.']);
    }
    return $datos;
}

$accion = isset($_GET['action']) ? $_GET['action'] : '';

if ($accion == 'me' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    session_start();
    if (!isset($_SESSION['user'])) {
        responderAuth(401, ['message' => 'No hay una sesión iniciada.']);
    }
    responderAuth(200, ['message' => 'Sesión activa.', 'user' => $_SESSION['user']]);
}

if ($accion == 'logout' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    responderAuth(200, ['message' => 'Sesión cerrada.']);
}

require_once __DIR__ . '/../models/conexion.php';

if ($accion == 'profile' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    session_start();
    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if ($userId < 1) {
        responderAuth(401, ['message' => 'Debe iniciar sesión.']);
    }
    $query = mysqli_prepare(
        $conexion,
        'SELECT u.id, u.nombres, u.apellidos, u.documento, u.correo, u.telefono, r.nombre AS rol
         FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.id = ? AND u.activo = 1 LIMIT 1'
    );
    if (!$query) {
        error_log(mysqli_error($conexion));
        responderAuth(500, ['message' => 'No fue posible consultar el perfil.']);
    }
    mysqli_stmt_bind_param($query, 'i', $userId);
    if (!mysqli_stmt_execute($query)) {
        error_log(mysqli_stmt_error($query));
        responderAuth(500, ['message' => 'No fue posible consultar el perfil.']);
    }
    $profile = mysqli_fetch_assoc(mysqli_stmt_get_result($query));
    if (!$profile) {
        responderAuth(401, ['message' => 'La cuenta no está activa.']);
    }
    responderAuth(200, ['message' => 'Perfil cargado.', 'user' => $profile]);
}

if ($accion == 'change-password' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    session_start();
    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if ($userId < 1) {
        responderAuth(401, ['message' => 'Debe iniciar sesión.']);
    }
    $data = datosSolicitudAuth();
    $currentPassword = (string) ($data['current_password'] ?? '');
    $newPassword = (string) ($data['new_password'] ?? '');
    if (strlen($newPassword) < 8) {
        responderAuth(422, ['message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
    }
    $query = mysqli_prepare($conexion, 'SELECT password_hash FROM usuarios WHERE id = ? AND activo = 1 LIMIT 1');
    if (!$query) {
        error_log(mysqli_error($conexion));
        responderAuth(500, ['message' => 'No fue posible verificar la contraseña.']);
    }
    mysqli_stmt_bind_param($query, 'i', $userId);
    if (!mysqli_stmt_execute($query)) {
        error_log(mysqli_stmt_error($query));
        responderAuth(500, ['message' => 'No fue posible verificar la contraseña.']);
    }
    mysqli_stmt_bind_result($query, $passwordHash);
    if (!mysqli_stmt_fetch($query) || !password_verify($currentPassword, (string) $passwordHash)) {
        responderAuth(422, ['message' => 'La contraseña actual no es correcta.']);
    }
    $update = mysqli_prepare($conexion, 'UPDATE usuarios SET password_hash = ? WHERE id = ?');
    if (!$update) {
        error_log(mysqli_error($conexion));
        responderAuth(500, ['message' => 'No fue posible cambiar la contraseña.']);
    }
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($update, 'si', $newPasswordHash, $userId);
    if (!mysqli_stmt_execute($update)) {
        error_log(mysqli_stmt_error($update));
        responderAuth(500, ['message' => 'No fue posible cambiar la contraseña.']);
    }
    session_regenerate_id(true);
    responderAuth(200, ['message' => 'La contraseña se cambió correctamente.']);
}

if ($accion == 'register' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $datos = datosSolicitudAuth();
    $nombres = isset($datos['nombres']) && is_string($datos['nombres']) ? trim($datos['nombres']) : '';
    $apellidos = isset($datos['apellidos']) && is_string($datos['apellidos']) ? trim($datos['apellidos']) : '';
    $documento = isset($datos['documento']) && is_string($datos['documento']) ? trim($datos['documento']) : '';
    $correo = isset($datos['correo']) && is_string($datos['correo']) ? trim($datos['correo']) : '';
    $telefono = isset($datos['telefono']) && is_string($datos['telefono']) ? trim($datos['telefono']) : '';
    $password = isset($datos['password']) && is_string($datos['password']) ? $datos['password'] : '';


    if ($nombres == '' || $apellidos == '' || $documento == '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responderAuth(422, ['message' => 'Completa los datos requeridos con un correo válido.']);
    }
    if (strlen($password) < 8) {
        responderAuth(422, ['message' => 'La contraseña debe tener al menos 8 caracteres.']);
    }

    $consultaRol = mysqli_query($conexion, "SELECT id FROM roles WHERE LOWER(nombre) = 'residente' LIMIT 1");
    $rol = $consultaRol ? mysqli_fetch_assoc($consultaRol) : false;
    if (!$rol) {
        responderAuth(500, ['message' => 'Falta el rol Residente en la tabla de roles.']);
    }

    $rolId = intval($rol['id']);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conexion, 'INSERT INTO usuarios (rol_id, nombres, apellidos, documento, correo, telefono, password_hash, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
    if (!$stmt) {
        responderAuth(500, ['message' => 'No fue posible preparar el registro.']);
    }
    mysqli_stmt_bind_param($stmt, 'issssss', $rolId, $nombres, $apellidos, $documento, $correo, $telefono, $hash);
    if (!mysqli_stmt_execute($stmt)) {
        if (mysqli_errno($conexion) == 1062) {
            responderAuth(409, ['message' => 'Ya existe una cuenta con ese correo o documento.']);
        }
        error_log(mysqli_error($conexion));
        responderAuth(500, ['message' => 'No fue posible crear la cuenta.']);
    }
    responderAuth(201, ['message' => 'Cuenta creada. Ya puedes iniciar sesión.']);
}

if ($accion == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $datos = datosSolicitudAuth();
    $correo = isset($datos['correo']) && is_string($datos['correo']) ? trim($datos['correo']) : '';
    $password = isset($datos['password']) && is_string($datos['password']) ? $datos['password'] : '';
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT u.id, u.nombres, u.apellidos, u.correo, u.password_hash, u.activo, r.nombre
         FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.correo = ? LIMIT 1'
    );
    if (!$stmt) {
        responderAuth(500, ['message' => 'No fue posible preparar el inicio de sesión.']);
    }
    mysqli_stmt_bind_param($stmt, 's', $correo);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $nombre, $apellido, $correoUsuario, $hash, $activo, $rol);
    if (!mysqli_stmt_fetch($stmt) || !$activo || !password_verify($password, $hash)) {
        responderAuth(401, ['message' => 'La contraseña y/o el correo no son correctos.']);
    }

    session_start();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $id,
        'nombres' => $nombre,
        'apellidos' => $apellido,
        'correo' => $correoUsuario,
        'rol' => $rol,
    ];
    responderAuth(200, ['message' => 'Inicio de sesión correcto.', 'user' => $_SESSION['user']]);
}

responderAuth(404, ['message' => 'Acción no encontrada.']);
