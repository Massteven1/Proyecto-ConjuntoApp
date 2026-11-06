<?php
require_once __DIR__ . '/m5_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    m5Respond(405, ['error' => 'Método no permitido.']);
}
$sql = 'SELECT e.* FROM v_estado_cuenta e';
$params = [];
if (!$m5Admin) {
    $sql .= ' WHERE e.apartamento_id IN (SELECT apartamento_id FROM residencias
             WHERE usuario_id = ? AND fecha_inicio <= CURDATE()
             AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()))';
    $params[] = $m5User['id'];
}
$sql .= ' ORDER BY e.torre, e.apartamento';
$query = $m5Db->prepare($sql);
$query->execute($params);
echo json_encode($query->fetchAll(), JSON_UNESCAPED_UNICODE);
