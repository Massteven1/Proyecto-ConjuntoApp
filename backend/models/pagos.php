<?php

require_once __DIR__ . '/conexion.php';

class Pagos
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function listarCuotasPendientes($usuarioId, $esAdministrador)
    {
        $condicion = '';
        if (!$esAdministrador) {
            $usuarioId = intval($usuarioId);
            $condicion = " AND EXISTS (
                SELECT 1 FROM residencias r
                WHERE r.apartamento_id = c.apartamento_id AND r.usuario_id = $usuarioId
                AND r.fecha_inicio <= CURDATE()
                AND (r.fecha_fin IS NULL OR r.fecha_fin >= CURDATE())
            )";
        }

        $sql = "SELECT c.id AS cuota_id, c.apartamento_id, t.nombre AS torre,
                a.numero AS apartamento, c.periodo, c.saldo, c.fecha_vencimiento
                FROM cuotas c
                INNER JOIN apartamentos a ON a.id = c.apartamento_id
                INNER JOIN torres t ON t.id = a.torre_id
                WHERE c.saldo > 0 $condicion
                ORDER BY c.apartamento_id, c.fecha_vencimiento, c.id";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }

        $cuotas = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            if (!isset($cuotas[$fila['apartamento_id']])) {
                $cuotas[$fila['apartamento_id']] = $fila;
            }
        }
        return array_values($cuotas);
    }

    public function registrarPago($apartamentoId, $valor, $fecha, $medio, $soporte, $usuarioId)
    {
        $apartamentoId = intval($apartamentoId);
        $usuarioId = intval($usuarioId);
        if (!mysqli_begin_transaction($this->conexion)) {
            return ['status' => 500, 'error' => 'No fue posible iniciar el registro del pago.'];
        }

        $sql = "SELECT id, saldo FROM cuotas
                WHERE apartamento_id = $apartamentoId AND saldo > 0
                ORDER BY fecha_vencimiento, id LIMIT 1 FOR UPDATE";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            mysqli_rollback($this->conexion);
            return ['status' => 500, 'error' => 'No fue posible consultar la cuota pendiente.'];
        }
        $cuota = mysqli_fetch_assoc($resultado);
        if (!$cuota) {
            mysqli_rollback($this->conexion);
            return ['status' => 422, 'error' => 'El apartamento no tiene cuotas pendientes.'];
        }
        if ((float) $valor > (float) $cuota['saldo']) {
            mysqli_rollback($this->conexion);
            return ['status' => 422, 'error' => 'El pago no puede superar el saldo de la cuota más antigua.'];
        }

        $cuotaId = intval($cuota['id']);
        $soporteSql = $soporte == '' ? null : $soporte;
        $stmt = mysqli_prepare(
            $this->conexion,
            'INSERT INTO pagos (cuota_id, valor, fecha_pago, medio, soporte, registrado_por)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            mysqli_rollback($this->conexion);
            return ['status' => 500, 'error' => 'No fue posible preparar el registro del pago.'];
        }
        mysqli_stmt_bind_param($stmt, 'idsssi', $cuotaId, $valor, $fecha, $medio, $soporteSql, $usuarioId);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_rollback($this->conexion);
            return ['status' => 500, 'error' => 'No fue posible registrar el pago.'];
        }

        $pagoId = mysqli_insert_id($this->conexion);
        mysqli_commit($this->conexion);
        return [
            'message' => 'Pago aplicado a la cuota más antigua.',
            'pago_id' => $pagoId,
            'cuota_id' => $cuotaId,
        ];
    }
}
