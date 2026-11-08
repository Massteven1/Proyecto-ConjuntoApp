<?php
require_once __DIR__ . '/conexion.php';

class EstadoCuenta
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function listar($usuarioId, $esAdministrador)
    {
        $condicion = '';
        if (!$esAdministrador) {
            $usuarioId = intval($usuarioId);
            $condicion = " WHERE EXISTS (
                SELECT 1 FROM residencias r
                WHERE r.apartamento_id = e.apartamento_id AND r.usuario_id = $usuarioId
                AND r.fecha_inicio <= CURDATE()
                AND (r.fecha_fin IS NULL OR r.fecha_fin >= CURDATE())
            )";
        }

        $sql = "SELECT e.* FROM v_estado_cuenta e
                $condicion ORDER BY e.torre, e.apartamento";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $cuentas = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $cuentas[] = $fila;
        }
        return $cuentas;
    }
}
