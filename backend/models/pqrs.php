<?php
require_once __DIR__ . '/conexion.php';

class PqrsModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // Consulto las PQRS que puede ver cada rol y preparo los datos de la lista.
    public function listar($usuarioId, $rol, $estado = '', $categoriaId = 0)
    {
        $usuarioId = intval($usuarioId);
        $condiciones = [];
        if ($rol !== 'administrador') {
            $condiciones[] = "p.usuario_id = $usuarioId";
        }
        if ($estado !== '') {
            $estado = mysqli_real_escape_string($this->conexion, $estado);
            $condiciones[] = "p.estado = '$estado'";
        }
        if (intval($categoriaId) > 0) {
            $condiciones[] = 'p.categoria_id = ' . intval($categoriaId);
        }
        $where = count($condiciones) > 0 ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT p.id, p.radicado, p.tipo, p.categoria_id, c.nombre AS categoria,
                    p.usuario_id, CONCAT(u.nombres, ' ', u.apellidos) AS usuario,
                    p.apartamento_id, a.numero AS apartamento, t.nombre AS torre,
                    p.asunto, p.descripcion, p.adjunto, p.estado, p.fecha_limite,
                    p.calificacion, h.comentario AS respuesta, h.fecha AS fecha_respuesta,
                    CASE
                        WHEN p.estado <> 'Cerrada' AND p.fecha_limite < CURDATE() THEN 1
                        ELSE 0
                    END AS vencida
                FROM pqrs p
                LEFT JOIN categorias_pqrs c ON c.id = p.categoria_id
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN apartamentos a ON a.id = p.apartamento_id
                LEFT JOIN torres t ON t.id = a.torre_id
                LEFT JOIN (
                    SELECT ps1.pqrs_id, ps1.comentario, ps1.fecha
                    FROM pqrs_seguimiento ps1
                    INNER JOIN (
                        SELECT pqrs_id, MAX(id) AS ultimo_id
                        FROM pqrs_seguimiento
                        WHERE estado_nuevo = 'Respondida'
                        GROUP BY pqrs_id
                    ) ultimo ON ultimo.pqrs_id = ps1.pqrs_id AND ultimo.ultimo_id = ps1.id
                ) h ON h.pqrs_id = p.id
                $where
                ORDER BY p.id DESC";

        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $items = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fila['vencida'] = (bool) $fila['vencida'];
            $fila['calificacion'] = $fila['calificacion'] !== null ? intval($fila['calificacion']) : null;
            $items[] = $fila;
        }

        return $items;
    }

    // Consulto el historial solo si la PQRS pertenece al residente o si soy administrador.
    public function historial($pqrsId, $usuarioId, $rol)
    {
        $pqrsId = intval($pqrsId);
        $usuarioId = intval($usuarioId);
        $filtroUsuario = $rol === 'administrador' ? '' : "AND usuario_id = $usuarioId";
        $acceso = mysqli_query(
            $this->conexion,
            "SELECT id FROM pqrs WHERE id = $pqrsId $filtroUsuario LIMIT 1"
        );

        if (!$acceso) {
            return false;
        }
        if (!mysqli_fetch_assoc($acceso)) {
            return 'not_found';
        }

        $sql = "SELECT ps.estado_anterior, ps.estado_nuevo, ps.comentario, ps.fecha,
                    CONCAT(u.nombres, ' ', u.apellidos) AS usuario
                FROM pqrs_seguimiento ps
                INNER JOIN usuarios u ON u.id = ps.usuario_id
                WHERE ps.pqrs_id = $pqrsId
                ORDER BY ps.fecha ASC, ps.id ASC";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $items = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $items[] = $fila;
        }
        return $items;
    }

    // Busco el adjunto respetando los permisos de la PQRS.
    public function adjunto($pqrsId, $usuarioId, $rol)
    {
        $pqrsId = intval($pqrsId);
        $usuarioId = intval($usuarioId);
        $filtroUsuario = $rol === 'administrador' ? '' : "AND usuario_id = $usuarioId";
        $resultado = mysqli_query(
            $this->conexion,
            "SELECT adjunto FROM pqrs WHERE id = $pqrsId $filtroUsuario LIMIT 1"
        );

        if (!$resultado) {
            return false;
        }
        $fila = mysqli_fetch_assoc($resultado);
        return $fila ? $fila['adjunto'] : 'not_found';
    }

    // Calculo el resumen por estado, categoría y tiempo de respuesta.
    public function resumen($usuarioId = null, $estado = '', $categoriaId = 0)
    {
        $condiciones = [];
        if ($usuarioId !== null) {
            $condiciones[] = 'p.usuario_id = ' . intval($usuarioId);
        }
        if ($estado !== '') {
            $estado = mysqli_real_escape_string($this->conexion, $estado);
            $condiciones[] = "p.estado = '$estado'";
        }
        if (intval($categoriaId) > 0) {
            $condiciones[] = 'p.categoria_id = ' . intval($categoriaId);
        }
        $filtro = count($condiciones) > 0 ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT
                    SUM(CASE WHEN p.estado = 'Radicada' THEN 1 ELSE 0 END) AS radicadas,
                    SUM(CASE WHEN p.estado = 'En trámite' THEN 1 ELSE 0 END) AS en_tramite,
                    SUM(CASE WHEN p.estado = 'Respondida' THEN 1 ELSE 0 END) AS respondidas,
                    SUM(CASE WHEN p.estado = 'Cerrada' THEN 1 ELSE 0 END) AS cerradas,
                    COUNT(p.id) AS total,
                    AVG(CASE WHEN respuesta.fecha IS NOT NULL THEN TIMESTAMPDIFF(HOUR, radicada.fecha, respuesta.fecha) / 24 END) AS promedio_dias_respuesta
                FROM pqrs p
                LEFT JOIN (
                    SELECT pqrs_id,
                        COALESCE(MIN(CASE WHEN estado_nuevo = 'Radicada' THEN fecha END), MIN(fecha)) AS fecha
                    FROM pqrs_seguimiento
                    GROUP BY pqrs_id
                ) radicada ON radicada.pqrs_id = p.id
                LEFT JOIN (
                    SELECT pqrs_id, MIN(fecha) AS fecha
                    FROM pqrs_seguimiento
                    WHERE estado_nuevo = 'Respondida'
                    GROUP BY pqrs_id
                ) respuesta ON respuesta.pqrs_id = p.id
                $filtro";

        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $resumen = mysqli_fetch_assoc($resultado) ?: [];

        $sqlCategorias = "SELECT c.id, c.nombre, COUNT(p.id) AS total
            FROM categorias_pqrs c
            LEFT JOIN pqrs p ON p.categoria_id = c.id
            " . ($filtro !== '' ? 'WHERE ' . substr($filtro, 6) : '') . "
                GROUP BY c.id, c.nombre
                ORDER BY total DESC, c.nombre ASC";

        $resultadoCategorias = mysqli_query($this->conexion, $sqlCategorias);
        if (!$resultadoCategorias) {
            return false;
        }

        $categorias = [];
        while ($fila = mysqli_fetch_assoc($resultadoCategorias)) {
            $categorias[] = [
                'id' => intval($fila['id']),
                'nombre' => $fila['nombre'],
                'total' => intval($fila['total']),
            ];
        }

        return [
            'totales' => [
                'radicadas' => intval($resumen['radicadas'] ?? 0),
                'en_tramite' => intval($resumen['en_tramite'] ?? 0),
                'respondidas' => intval($resumen['respondidas'] ?? 0),
                'cerradas' => intval($resumen['cerradas'] ?? 0),
                'total' => intval($resumen['total'] ?? 0),
                'promedio_dias_respuesta' => $resumen['promedio_dias_respuesta'] !== null
                    ? round((float) $resumen['promedio_dias_respuesta'], 1)
                    : null,
            ],
            'categorias' => $categorias,
        ];
    }

    // Cargo las categorías vigentes desde la base para no repetirlas en la pantalla.
    public function listarCategorias()
    {
        $resultado = mysqli_query($this->conexion, 'SELECT id, nombre FROM categorias_pqrs ORDER BY nombre');
        if (!$resultado) {
            return false;
        }

        $items = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $items[] = ['id' => intval($fila['id']), 'nombre' => $fila['nombre']];
        }
        return $items;
    }

    // Muestro únicamente los apartamentos activos asociados al residente.
    public function listarApartamentosResidente($usuarioId)
    {
        $usuarioId = intval($usuarioId);
        $sql = "SELECT a.id, a.numero, t.nombre AS torre
                FROM residencias r
                INNER JOIN apartamentos a ON a.id = r.apartamento_id
                INNER JOIN torres t ON t.id = a.torre_id
                WHERE r.usuario_id = $usuarioId AND r.fecha_inicio <= CURDATE()
                  AND (r.fecha_fin IS NULL OR r.fecha_fin >= CURDATE())
                ORDER BY t.nombre, a.numero";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $items = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $items[] = [
                'id' => intval($fila['id']),
                'numero' => $fila['numero'],
                'torre' => $fila['torre'],
            ];
        }
        return $items;
    }

    // Registro la PQRS, calculo el plazo y guardo su primer evento de seguimiento.
    public function crear($datos, $usuarioId)
    {
        $tipo = isset($datos['tipo']) && is_string($datos['tipo']) ? trim($datos['tipo']) : '';
        $categoriaId = isset($datos['categoria_id']) ? intval($datos['categoria_id']) : 0;
        $apartamentoId = isset($datos['apartamento_id']) ? intval($datos['apartamento_id']) : 0;
        $asunto = isset($datos['asunto']) && is_string($datos['asunto']) ? trim($datos['asunto']) : '';
        $descripcion = isset($datos['descripcion']) && is_string($datos['descripcion']) ? trim($datos['descripcion']) : '';
        $adjunto = isset($datos['adjunto']) && is_string($datos['adjunto']) ? trim($datos['adjunto']) : null;

        $tiposPermitidos = ['Petición', 'Queja', 'Reclamo', 'Sugerencia'];
        if (
            !in_array($tipo, $tiposPermitidos, true) || $categoriaId <= 0 || $apartamentoId <= 0 ||
            $asunto === '' || mb_strlen($asunto, 'UTF-8') > 150 || $descripcion === ''
        ) {
            return 'invalid';
        }

        $categoria = mysqli_query($this->conexion, "SELECT id FROM categorias_pqrs WHERE id = $categoriaId LIMIT 1");
        if (!$categoria) {
            return false;
        }
        if (!mysqli_fetch_assoc($categoria)) {
            return 'invalid';
        }

        $usuarioId = intval($usuarioId);
        $asociacion = mysqli_query(
            $this->conexion,
            "SELECT id FROM residencias
             WHERE usuario_id = $usuarioId AND apartamento_id = $apartamentoId
               AND fecha_inicio <= CURDATE() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
             LIMIT 1"
        );
        if (!$asociacion) {
            return false;
        }
        if (!mysqli_fetch_assoc($asociacion)) {
            return 'apartment_not_allowed';
        }

        $tipo = mysqli_real_escape_string($this->conexion, $tipo);
        $asunto = mysqli_real_escape_string($this->conexion, $asunto);
        $descripcion = mysqli_real_escape_string($this->conexion, $descripcion);
        $adjunto = $adjunto !== null ? mysqli_real_escape_string($this->conexion, $adjunto) : null;

        $fechaLimite = new DateTimeImmutable('today');
        $diasHabiles = 0;
        while ($diasHabiles < 15) {
            $fechaLimite = $fechaLimite->modify('+1 day');
            if ((int) $fechaLimite->format('N') < 6) {
                $diasHabiles++;
            }
        }
        $fechaLimiteSql = $fechaLimite->format('Y-m-d');
        $radicadoTemporal = 'TMP-' . bin2hex(random_bytes(12));

        mysqli_begin_transaction($this->conexion);
        $sql = "INSERT INTO pqrs (radicado, tipo, categoria_id, usuario_id, apartamento_id, asunto, descripcion, adjunto, estado, fecha_limite)
                VALUES ('$radicadoTemporal', '$tipo', $categoriaId, $usuarioId, $apartamentoId, '$asunto', '$descripcion', " . ($adjunto === null ? 'NULL' : "'$adjunto'") . ", 'Radicada', '$fechaLimiteSql')";

        if (!mysqli_query($this->conexion, $sql)) {
            mysqli_rollback($this->conexion);
            return false;
        }

        $id = mysqli_insert_id($this->conexion);
        $radicado = sprintf('PQRS-%s-%04d', date('Y'), $id);
        $update = "UPDATE pqrs SET radicado = '$radicado' WHERE id = $id";
        $insert = "INSERT INTO pqrs_seguimiento (pqrs_id, usuario_id, estado_anterior, estado_nuevo, comentario, fecha)
                    VALUES ($id, $usuarioId, NULL, 'Radicada', 'PQRS radicada por el usuario.', NOW())";

        if (!mysqli_query($this->conexion, $update) || !mysqli_query($this->conexion, $insert)) {
            mysqli_rollback($this->conexion);
            return false;
        }

        mysqli_commit($this->conexion);
        return $id;
    }

    // Actualizo el estado y guardo el comentario dentro del mismo cambio.
    public function actualizarEstado($pqrsId, $nuevoEstado, $respuesta, $usuarioId)
    {
        $pqrsId = intval($pqrsId);
        $usuarioId = intval($usuarioId);
        $nuevoEstado = trim((string) $nuevoEstado);
        $respuesta = trim((string) $respuesta);

        $permitidos = ['Radicada', 'En trámite', 'Respondida', 'Cerrada'];
        if ($pqrsId <= 0 || !in_array($nuevoEstado, $permitidos, true)) {
            return 'invalid';
        }

        $sql = "SELECT id, estado FROM pqrs WHERE id = $pqrsId LIMIT 1";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $fila = mysqli_fetch_assoc($resultado);
        if (!$fila) {
            return 'not_found';
        }

        $estadoAnterior = $fila['estado'];
        $comentario = $respuesta !== '' ? mysqli_real_escape_string($this->conexion, $respuesta) : 'Se actualizó el estado de la PQRS.';
        $nuevoEstado = mysqli_real_escape_string($this->conexion, $nuevoEstado);

        if ($nuevoEstado === 'Respondida' && $respuesta === '') {
            return 'response_required';
        }

        mysqli_begin_transaction($this->conexion);
        $update = "UPDATE pqrs SET estado = '$nuevoEstado' WHERE id = $pqrsId";
        if (!mysqli_query($this->conexion, $update)) {
            mysqli_rollback($this->conexion);
            return false;
        }

        $historial = "INSERT INTO pqrs_seguimiento (pqrs_id, usuario_id, estado_anterior, estado_nuevo, comentario, fecha)
                      VALUES ($pqrsId, $usuarioId, '$estadoAnterior', '$nuevoEstado', '$comentario', NOW())";

        if (!mysqli_query($this->conexion, $historial)) {
            mysqli_rollback($this->conexion);
            return false;
        }

        mysqli_commit($this->conexion);
        return true;
    }

    // Guardo una sola calificación del residente dueño de la PQRS.
    public function calificar($pqrsId, $usuarioId, $calificacion)
    {
        $pqrsId = intval($pqrsId);
        $usuarioId = intval($usuarioId);
        $calificacion = intval($calificacion);

        if ($pqrsId <= 0 || $calificacion < 1 || $calificacion > 5) {
            return 'invalid';
        }

        $sql = "UPDATE pqrs
                SET calificacion = $calificacion
            WHERE id = $pqrsId AND usuario_id = $usuarioId AND estado IN ('Respondida', 'Cerrada')
              AND calificacion IS NULL";

        if (!mysqli_query($this->conexion, $sql)) {
            return false;
        }

        return mysqli_affected_rows($this->conexion) > 0;
    }
}
