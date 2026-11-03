<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_save_path(__DIR__ . '/../sessions');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestData(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw === false ? '' : $raw, true);
    if (!is_array($data)) {
        respond(400, ['message' => 'La solicitud no contiene datos válidos.']);
    }
    return $data;
}

$action = $_GET['action'] ?? '';

if ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    session_start();
    if (!isset($_SESSION['user'])) {
        respond(401, ['message' => 'No hay una sesión iniciada.']);
    }
    respond(200, ['message' => 'Sesión activa.', 'user' => $_SESSION['user']]);
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    respond(200, ['message' => 'Sesión cerrada.']);
}

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=conjunto_app;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    respond(500, ['message' => 'No fue posible conectar con la base de datos.']);
}

if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = requestData();
    $nombres = trim((string) ($data['nombres'] ?? ''));
    $apellidos = trim((string) ($data['apellidos'] ?? ''));
    $documento = trim((string) ($data['documento'] ?? ''));
    $correo = trim((string) ($data['correo'] ?? ''));
    $telefono = trim((string) ($data['telefono'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($nombres === '' || $apellidos === '' || $documento === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        respond(422, ['message' => 'Completa los datos requeridos con un correo válido.']);
    }
    if (strlen($password) < 8) {
        respond(422, ['message' => 'La contraseña debe tener al menos 8 caracteres.']);
    }

    $roleQuery = $pdo->prepare("SELECT id FROM roles WHERE LOWER(nombre) = 'residente' LIMIT 1");
    $roleQuery->execute();
    $roleId = $roleQuery->fetchColumn();
    if ($roleId === false) {
        respond(500, ['message' => 'Falta el rol Residente en la tabla de roles.']);
    }

    try {
        $insert = $pdo->prepare(
            'INSERT INTO usuarios (rol_id, nombres, apellidos, documento, correo, telefono, password_hash, activo)
             VALUES (:rol_id, :nombres, :apellidos, :documento, :correo, :telefono, :password_hash, 1)'
        );
        $insert->execute([
            'rol_id' => $roleId,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'documento' => $documento,
            'correo' => $correo,
            'telefono' => $telefono !== '' ? $telefono : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            respond(409, ['message' => 'Ya existe una cuenta con ese correo o documento.']);
        }
        error_log($exception->getMessage());
        respond(500, ['message' => 'No fue posible crear la cuenta.']);
    }

    respond(201, ['message' => 'Cuenta creada. Ya puedes iniciar sesión.']);
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = requestData();
    $correo = trim((string) ($data['correo'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $query = $pdo->prepare(
        'SELECT u.id, u.nombres, u.apellidos, u.correo, u.password_hash, u.activo, r.nombre AS rol
         FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE u.correo = :correo LIMIT 1'
    );
    $query->execute(['correo' => $correo]);
    $user = $query->fetch();

    if (!$user || !(bool) $user['activo'] || !password_verify($password, $user['password_hash'])) {
        respond(401, ['message' => 'La contraseña y/o el correo no son correctos.']);
    }

    session_start();
    session_regenerate_id(true);
    unset($user['password_hash'], $user['activo']);
    $_SESSION['user'] = $user;
    respond(200, ['message' => 'Inicio de sesión correcto.', 'user' => $user]);
}

respond(404, ['message' => 'Acción no encontrada.']);
