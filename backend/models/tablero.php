<?php
require_once __DIR__ . '/conexion.php';

class Tablero
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function consultarResumen()
    {
        $datos = [];

        $sql = "SELECT IFNULL(100 * SUM(c.saldo) / NULLIF(SUM(c.valor), 0), 0) AS porcentaje_cartera
                FROM cuotas c
                INNER JOIN apartamentos a ON a.id = c.apartamento_id";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }
        $fila = mysqli_fetch_assoc($resultado);
        $datos['porcentaje_cartera'] = round((float) $fila['porcentaje_cartera'], 2);

        $sql = "SELECT COUNT(p.id) AS pqrs_abiertas
                FROM pqrs p
                INNER JOIN categorias_pqrs c ON c.id = p.categoria_id
                WHERE p.estado IN ('Radicada', 'En trámite')";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }
        $fila = mysqli_fetch_assoc($resultado);
        $datos['pqrs_abiertas'] = intval($fila['pqrs_abiertas']);

        $sql = "SELECT COUNT(r.id) AS reservas_semana
                FROM reservas r
                INNER JOIN zonas_comunes z ON z.id = r.zona_id
                WHERE r.fecha >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                  AND r.fecha < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }
        $fila = mysqli_fetch_assoc($resultado);
        $datos['reservas_semana'] = intval($fila['reservas_semana']);

        $sql = "SELECT COUNT(i.id) AS visitantes_hoy
                FROM ingresos i
                INNER JOIN visitantes v ON v.id = i.visitante_id
                INNER JOIN apartamentos a ON a.id = i.apartamento_id
                WHERE DATE(i.fecha_ingreso) = CURDATE()";
        $resultado = mysqli_query($this->conexion, $sql);
        if (!$resultado) {
            return false;
        }
        $fila = mysqli_fetch_assoc($resultado);
        $datos['visitantes_hoy'] = intval($fila['visitantes_hoy']);

        return $datos;
    }
}
