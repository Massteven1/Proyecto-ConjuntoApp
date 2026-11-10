<?php

require_once __DIR__ . '/conexion.php';

class Cuotas
{
    private mysqli $conexion;

    public function __construct()
    {
        $this->conexion = (new Conexion())->conectar();
    }

    public function generarCuotas(string $periodo, float $presupuestoBase, string $fechaVencimiento): array|false
    {
        $procedimiento = $this->conexion->prepare('CALL sp_generar_cuotas(?, ?, ?)');
        if (!$procedimiento) {
            return false;
        }
        $procedimiento->bind_param('sds', $periodo, $presupuestoBase, $fechaVencimiento);
        if (!$procedimiento->execute()) {
            $procedimiento->close();
            return false;
        }
        $resultadoProcedimiento = $procedimiento->get_result();
        if (!$resultadoProcedimiento) {
            $procedimiento->close();
            return false;
        }
        $resultado = $resultadoProcedimiento->fetch_assoc();
        $resultadoProcedimiento->free();

        while ($procedimiento->more_results()) {
            $procedimiento->next_result();
            $resultadoAdicional = $procedimiento->get_result();
            if ($resultadoAdicional instanceof mysqli_result) {
                $resultadoAdicional->free();
            }
        }
        $procedimiento->close();

        $consulta = $this->conexion->prepare(
            "SELECT c.id, c.apartamento_id, t.nombre AS torre, a.numero AS apartamento,
                    c.periodo, c.valor, c.fecha_vencimiento, c.saldo, c.estado,
                    COALESCE(
                        (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                            'id', u.id,
                            'nombre', CONCAT(u.nombres, ' ', u.apellidos),
                            'tipo', r.tipo
                        ))
                         FROM residencias r
                         INNER JOIN usuarios u ON u.id = r.usuario_id
                         WHERE r.apartamento_id = a.id
                           AND r.fecha_inicio <= LAST_DAY(CONCAT(c.periodo, '-01'))
                           AND (r.fecha_fin IS NULL OR r.fecha_fin >= CONCAT(c.periodo, '-01'))
                           AND u.activo = 1),
                        JSON_ARRAY()
                    ) AS residentes
             FROM cuotas c
             INNER JOIN apartamentos a ON a.id = c.apartamento_id
             INNER JOIN torres t ON t.id = a.torre_id
             WHERE c.periodo = ?
             ORDER BY t.nombre, a.numero"
        );
        if (!$consulta) {
            return false;
        }
        $consulta->bind_param('s', $periodo);
        if (!$consulta->execute()) {
            $consulta->close();
            return false;
        }
        $resultadoCuotas = $consulta->get_result();
        $cuotas = $resultadoCuotas->fetch_all(MYSQLI_ASSOC);
        $resultadoCuotas->free();
        $consulta->close();

        foreach ($cuotas as &$cuota) {
            $cuota['residentes'] = json_decode($cuota['residentes'], true, 512, JSON_THROW_ON_ERROR);
        }
        unset($cuota);

        return [
            'periodo' => $periodo,
            'generadas' => (int) ($resultado['generadas'] ?? 0),
            'cuotas' => $cuotas,
        ];
    }
}
