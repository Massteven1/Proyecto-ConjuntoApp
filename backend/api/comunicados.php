<?php
require_once __DIR__ . '/m5_bootstrap.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $m5Db->prepare(
        'SELECT c.id, c.titulo, c.contenido, c.fecha_publicacion, c.vigente_hasta,
                EXISTS(SELECT 1 FROM comunicados_leidos l WHERE l.comunicado_id = c.id AND l.usuario_id = ?) AS leido
         FROM comunicados c WHERE c.vigente_hasta >= CURDATE()
         ORDER BY c.fecha_publicacion DESC'
    );
    $query->execute([$m5User['id']]);
    echo json_encode($query->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    m5Respond(405, ['error' => 'Método no permitido.']);
}
if ($id) {
    if ($m5Admin) m5Respond(403, ['error' => 'Esta acción es exclusiva de residentes.']);
    $check = $m5Db->prepare('SELECT id FROM comunicados WHERE id = ? AND vigente_hasta >= CURDATE()');
    $check->execute([$id]);
    if (!$check->fetch()) m5Respond(404, ['error' => 'Comunicado no encontrado o no vigente.']);
    $insert = $m5Db->prepare('INSERT IGNORE INTO comunicados_leidos (comunicado_id, usuario_id) VALUES (?, ?)');
    $insert->execute([$id, $m5User['id']]);
    m5Respond(200, ['message' => 'Comunicado marcado como leído.']);
}
if (!$m5Admin) m5Respond(403, ['error' => 'Solo un administrador puede publicar comunicados.']);
$title = trim((string) ($m5Data['titulo'] ?? ''));
$content = trim((string) ($m5Data['contenido'] ?? ''));
$expiry = (string) ($m5Data['vigente_hasta'] ?? '');
$validDate = DateTime::createFromFormat('!Y-m-d', $expiry);
if ($title === '' || strlen($title) > 150 || $content === '' || !$validDate || $validDate->format('Y-m-d') !== $expiry || $expiry < date('Y-m-d')) {
    m5Respond(422, ['error' => 'Indique título, contenido y una fecha de vigencia válida.']);
}
$insert = $m5Db->prepare('INSERT INTO comunicados (titulo, contenido, vigente_hasta, autor_id) VALUES (?, ?, ?, ?)');
$insert->execute([$title, $content, $expiry, $m5User['id']]);
m5Respond(201, ['message' => 'Comunicado publicado.', 'id' => (int) $m5Db->lastInsertId()]);
