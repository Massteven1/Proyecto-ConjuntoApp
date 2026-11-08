<?php
require_once __DIR__ . '/conexion.php';

class Comunicado
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function listarVigentes($usuarioId)
    {
        $usuarioId = intval($usuarioId);
        $sql = "SELECT c.id, c.titulo, c.contenido, c.fecha_publicacion, c.vigente_hasta,
                IF(l.usuario_id IS NULL, 0, 1) AS leido
                FROM comunicados c
                LEFT JOIN comunicados_leidos l
                    ON l.comunicado_id = c.id AND l.usuario_id = $usuarioId
                WHERE c.fecha_publicacion <= NOW() AND c.vigente_hasta >= CURDATE()
                ORDER BY c.fecha_publicacion DESC, c.id DESC";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $comunicados = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fila['leido'] = (bool) $fila['leido'];
            $comunicados[] = $fila;
        }
        return $comunicados;
    }

    public function marcarLeido($comunicadoId, $usuarioId)
    {
        $comunicadoId = intval($comunicadoId);
        $usuarioId = intval($usuarioId);
        if ($comunicadoId <= 0) {
            return 'no_encontrado';
        }

        $sql = "SELECT id FROM comunicados
                WHERE id = $comunicadoId AND fecha_publicacion <= NOW()
                AND vigente_hasta >= CURDATE()";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }
        if (!mysqli_fetch_assoc($resultado)) {
            return 'no_encontrado';
        }

        $sql = "INSERT IGNORE INTO comunicados_leidos (comunicado_id, usuario_id)
                VALUES ($comunicadoId, $usuarioId)";
        return mysqli_query($this->conexion, $sql);
    }

    public function publicar($titulo, $contenido, $vigenteHasta, $usuarioId)
    {
        $titulo = mysqli_real_escape_string($this->conexion, $titulo);
        $contenido = mysqli_real_escape_string($this->conexion, $contenido);
        $vigenteHasta = mysqli_real_escape_string($this->conexion, $vigenteHasta);
        $usuarioId = intval($usuarioId);
        $sql = "INSERT INTO comunicados (titulo, contenido, vigente_hasta, autor_id)
                VALUES ('$titulo', '$contenido', '$vigenteHasta', $usuarioId)";
        if (!mysqli_query($this->conexion, $sql)) {
            return false;
        }
        return mysqli_insert_id($this->conexion);
    }
}
