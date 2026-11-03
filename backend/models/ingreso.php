<?php
    class Ingreso{
        private $conexion;

        function __construct($conexion){
            $this->conexion = $conexion;
        }

        // Busca el visitante por documento y trae su ultima autorizacion
        function buscarVisitante($documento){
            $sql = "SELECT v.id, v.documento, v.nombre, v.telefono, au.fecha_visita, au.con_vehiculo, au.placa, a.id AS apartamento_id, a.numero AS apartamento, t.nombre AS torre
                    FROM visitantes v
                    LEFT JOIN autorizaciones au ON au.visitante_id = v.id
                    LEFT JOIN apartamentos a ON au.apartamento_id = a.id
                    LEFT JOIN torres t ON a.torre_id = t.id
                    WHERE v.documento = '$documento'
                    ORDER BY au.fecha_visita DESC, au.id DESC
                    LIMIT 1";

            $resultado = mysqli_query($this->conexion, $sql);

            if (!$resultado) {
                return null;
            }

            return mysqli_fetch_assoc($resultado);
        }

        // Busca si el visitante tiene un ingreso sin salida
        function buscarAbierto($visitante_id){
            $visitante_id = intval($visitante_id);
            $sql = "SELECT id, fecha_ingreso FROM ingresos WHERE visitante_id = $visitante_id AND fecha_salida IS NULL ORDER BY id DESC LIMIT 1";

            $resultado = mysqli_query($this->conexion, $sql);

            if (!$resultado) {
                return null;
            }

            return mysqli_fetch_assoc($resultado);
        }

        // Registra la entrada del visitante
        function insertar($visitante_id, $apartamento_id, $portero_id, $placa){
            $visitante_id = intval($visitante_id);
            $apartamento_id = intval($apartamento_id);
            $portero_id = intval($portero_id);

            if ($placa == null) {
                $placa = "";
            }

            $placa = trim($placa);

            if ($placa == "") {
                $valorPlaca = "NULL";
            } else {
                $valorPlaca = "'" . mysqli_real_escape_string($this->conexion, $placa) . "'";
            }

            $sql = "INSERT INTO ingresos (visitante_id, apartamento_id, portero_id, placa) VALUES ($visitante_id, $apartamento_id, $portero_id, $valorPlaca)";

            $resultado = mysqli_query($this->conexion, $sql);

            if ($resultado) {
                $res = ["OK", "Ingreso registrado"];
            } else {
                $res = ["ERROR", mysqli_error($this->conexion)];
            }

            return $res;
        }

        // Registra la salida del visitante y devuelve cuantas filas se actualizaron
        function registrarSalida($visitante_id){
            $visitante_id = intval($visitante_id);
            $sql = "UPDATE ingresos SET fecha_salida = NOW() WHERE visitante_id = $visitante_id AND fecha_salida IS NULL";

            mysqli_query($this->conexion, $sql);

            return mysqli_affected_rows($this->conexion);
        }

        // Lista los visitantes que estan dentro del conjunto
        function listarDentro(){
            $sql = "SELECT i.id, v.nombre AS visitante, v.documento, a.numero AS apartamento, t.nombre AS torre, i.placa, i.fecha_ingreso, CONCAT(u.nombres, ' ', u.apellidos) AS portero
                    FROM ingresos i
                    INNER JOIN visitantes v ON i.visitante_id = v.id
                    INNER JOIN apartamentos a ON i.apartamento_id = a.id
                    INNER JOIN torres t ON a.torre_id = t.id
                    INNER JOIN usuarios u ON i.portero_id = u.id
                    WHERE i.fecha_salida IS NULL
                    ORDER BY i.fecha_ingreso DESC";

            $resultado = mysqli_query($this->conexion, $sql);
            $lista = [];

            if ($resultado) {
                while ($fila = mysqli_fetch_assoc($resultado)) {
                    $lista[] = $fila;
                }
            }

            return $lista;
        }
    }

?>
