<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responder(int $estado, array $datos): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/../models/sesion.php';
$usuario = usuarioActual();
session_write_close();

if ($usuario === null) {
    responder(401, ['message' => 'Debe iniciar sesión.']);
}
if (($usuario['rol'] ?? '') !== 'Administrador') {
    responder(403, ['message' => 'Solo el administrador puede gestionar usuarios.']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../models/conexion.php';
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        $usuarios = $conexion->query(
            'SELECT u.id, u.rol_id, r.nombre AS rol, u.nombres, u.apellidos, u.documento,
                    u.correo, u.telefono, u.activo
             FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
             ORDER BY u.apellidos, u.nombres'
        )->fetch_all(MYSQLI_ASSOC);
        $roles = $conexion->query('SELECT id, nombre FROM roles ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
        responder(200, ['usuarios' => $usuarios, 'roles' => $roles]);
    }

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if (!in_array($metodo, ['POST', 'PUT', 'PATCH'], true)) {
        responder(405, ['message' => 'Método no permitido.']);
    }
    if ($metodo !== 'POST' && ($id === false || $id === null || $id < 1)) {
        responder(422, ['message' => 'El identificador del usuario no es válido.']);
    }

    $datos = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($datos)) {
        responder(400, ['message' => 'La solicitud no contiene datos válidos.']);
    }

    if ($metodo === 'PATCH') {
        if (!in_array($datos['activo'] ?? null, [0, 1, '0', '1', false, true], true)) {
            responder(422, ['message' => 'El estado del usuario no es válido.']);
        }
        $activo = (int) (bool) $datos['activo'];
        if ($activo === 0 && (int) $id === (int) $usuario['id']) {
            responder(422, ['message' => 'No puede inactivar su propia cuenta.']);
        }
        $consulta = $conexion->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
        $consulta->bind_param('ii', $activo, $id);
        $consulta->execute();
        if ($consulta->affected_rows === 0) {
            $existe = $conexion->prepare('SELECT id FROM usuarios WHERE id = ?');
            $existe->bind_param('i', $id);
            $existe->execute();
            if ($existe->get_result()->num_rows === 0) {
                responder(404, ['message' => 'El usuario no existe.']);
            }
        }
        responder(200, ['message' => $activo ? 'Usuario activado.' : 'Usuario inactivado.']);
    }

    $nombres = trim((string) ($datos['nombres'] ?? ''));
    $apellidos = trim((string) ($datos['apellidos'] ?? ''));
    $documento = trim((string) ($datos['documento'] ?? ''));
    $correo = trim((string) ($datos['correo'] ?? ''));
    $telefono = trim((string) ($datos['telefono'] ?? ''));
    $rolId = filter_var($datos['rol_id'] ?? null, FILTER_VALIDATE_INT);

    if ($nombres === '' || $apellidos === '' || $documento === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responder(422, ['message' => 'Completa los datos requeridos con un correo válido.']);
    }
    if ($rolId === false || $rolId < 1) {
        responder(422, ['message' => 'Selecciona un rol válido.']);
    }

    if ($metodo === 'POST') {
        $password = (string) ($datos['password'] ?? '');
        if (strlen($password) < 8) {
            responder(422, ['message' => 'La contraseña debe tener al menos 8 caracteres.']);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $consulta = $conexion->prepare(
            'INSERT INTO usuarios (rol_id, nombres, apellidos, documento, correo, telefono, password_hash, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $consulta->bind_param('issssss', $rolId, $nombres, $apellidos, $documento, $correo, $telefono, $hash);
        $consulta->execute();
        responder(201, ['message' => 'Usuario creado correctamente.']);
    }

    $consulta = $conexion->prepare(
        'UPDATE usuarios SET rol_id = ?, nombres = ?, apellidos = ?, documento = ?, correo = ?, telefono = ? WHERE id = ?'
    );
    $consulta->bind_param('isssssi', $rolId, $nombres, $apellidos, $documento, $correo, $telefono, $id);
    $consulta->execute();
    if ($consulta->affected_rows === 0) {
        $existe = $conexion->prepare('SELECT id FROM usuarios WHERE id = ?');
        $existe->bind_param('i', $id);
        $existe->execute();
        if ($existe->get_result()->num_rows === 0) {
            responder(404, ['message' => 'El usuario no existe.']);
        }
    }
    responder(200, ['message' => 'Usuario actualizado correctamente.']);
} catch (mysqli_sql_exception $exception) {
    if ((int) $exception->getCode() === 1062) {
        responder(409, ['message' => 'Ya existe un usuario con ese correo o documento.']);
    }
    error_log($exception->getMessage());
    responder(500, ['message' => 'No fue posible guardar los cambios del usuario.']);
}
