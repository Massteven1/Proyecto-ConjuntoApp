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

        $sql = "SELECT e.*, COALESCE(p.valor_pagado_total, 0) AS valor_pagado_total,
                       GROUP_CONCAT(
                           DISTINCT CONCAT(u.nombres, ' ', u.apellidos, ' (', r.tipo, ')')
                           ORDER BY u.apellidos, u.nombres SEPARATOR ', '
                       ) AS residentes
                FROM v_estado_cuenta e
                LEFT JOIN (
                    SELECT c.apartamento_id, SUM(pg.valor) AS valor_pagado_total
                    FROM cuotas c
                    LEFT JOIN pagos pg ON pg.cuota_id = c.id
                    GROUP BY c.apartamento_id
                ) p ON p.apartamento_id = e.apartamento_id
                LEFT JOIN residencias r
                    ON r.apartamento_id = e.apartamento_id
                    AND r.fecha_inicio <= CURDATE()
                    AND (r.fecha_fin IS NULL OR r.fecha_fin >= CURDATE())
                LEFT JOIN usuarios u ON u.id = r.usuario_id AND u.activo = 1
                $condicion
                GROUP BY e.apartamento_id, e.torre, e.apartamento,
                         e.cuotas_pendientes, e.cuotas_en_mora, e.saldo_total,
                         p.valor_pagado_total
                ORDER BY e.torre, e.apartamento";
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
