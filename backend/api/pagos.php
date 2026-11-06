<?php
require_once __DIR__ . '/m5_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT c.id, c.apartamento_id, t.nombre AS torre, a.numero AS apartamento,
                   c.periodo, c.saldo, c.fecha_vencimiento
            FROM cuotas c JOIN apartamentos a ON a.id = c.apartamento_id
            JOIN torres t ON t.id = a.torre_id
            WHERE c.saldo > 0";
    $params = [];
    if (!$m5Admin) {
        $sql .= ' AND c.apartamento_id IN (SELECT apartamento_id FROM residencias
                  WHERE usuario_id = ? AND fecha_inicio <= CURDATE()
                  AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()))';
        $params[] = $m5User['id'];
    }
    $sql .= ' ORDER BY c.apartamento_id, c.fecha_vencimiento, c.id';
    $query = $m5Db->prepare($sql);
    $query->execute($params);
    $firstByApartment = [];
    foreach ($query->fetchAll() as $row) {
        $firstByApartment[$row['apartamento_id']] ??= $row;
    }
    echo json_encode(array_values($firstByApartment), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$m5Admin) {
    m5Respond($m5Admin ? 405 : 403, ['error' => $m5Admin ? 'Método no permitido.' : 'Solo un administrador puede registrar pagos.']);
}
$apartment = filter_var($m5Data['apartamento_id'] ?? null, FILTER_VALIDATE_INT);
$value = $m5Data['valor'] ?? null;
$date = $m5Data['fecha_pago'] ?? '';
$method = trim((string) ($m5Data['medio'] ?? ''));
$support = trim((string) ($m5Data['soporte'] ?? ''));
$validDate = is_string($date) ? DateTime::createFromFormat('!Y-m-d', $date) : false;
if (!$apartment || !is_numeric($value) || (float) $value <= 0 || !$validDate || $validDate->format('Y-m-d') !== $date || $method === '' || strlen($method) > 50 || strlen($support) > 255) {
    m5Respond(422, ['error' => 'Revise apartamento_id, fecha_pago, valor, medio y soporte.']);
}
try {
    $m5Db->beginTransaction();
    $query = $m5Db->prepare('SELECT id, saldo FROM cuotas WHERE apartamento_id = ? AND saldo > 0 ORDER BY fecha_vencimiento, id LIMIT 1 FOR UPDATE');
    $query->execute([$apartment]);
    $cuota = $query->fetch();
    if (!$cuota || (float) $value > (float) $cuota['saldo']) {
        $m5Db->rollBack();
        m5Respond(422, ['error' => 'No hay cuota pendiente o el pago supera el saldo de la cuota más antigua.']);
    }
    $insert = $m5Db->prepare('INSERT INTO pagos (cuota_id, valor, fecha_pago, medio, soporte, registrado_por) VALUES (?, ?, ?, ?, ?, ?)');
    $insert->execute([$cuota['id'], $value, $date, $method, $support !== '' ? $support : null, $m5User['id']]);
    $id = (int) $m5Db->lastInsertId();
    $m5Db->commit();
    m5Respond(201, ['message' => 'Pago aplicado a la cuota más antigua.', 'pago_id' => $id, 'cuota_id' => (int) $cuota['id']]);
} catch (Throwable $error) {
    if ($m5Db->inTransaction()) $m5Db->rollBack();
    error_log($error->getMessage());
    m5Respond(500, ['error' => 'No fue posible registrar el pago.']);
}
