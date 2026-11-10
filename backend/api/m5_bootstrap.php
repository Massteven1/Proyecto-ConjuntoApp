<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_save_path(__DIR__ . '/../sessions');
session_start();

function m5Respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user']['id'], $_SESSION['user']['rol'])) {
    m5Respond(401, ['error' => 'Debe iniciar sesión.']);
}

try {
    $m5Db = new PDO(
        'mysql:host=127.0.0.1;dbname=conjunto_app;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $error) {
    error_log($error->getMessage());
    m5Respond(500, ['error' => 'No fue posible conectar con la base de datos.']);
}

$m5User = $_SESSION['user'];
$m5Admin = strtolower((string) $m5User['rol']) === 'administrador';
$m5Data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($m5Data)) {
    $m5Data = [];
}
