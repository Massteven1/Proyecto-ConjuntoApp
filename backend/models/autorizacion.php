<?php
    class Autorizacion{
        private $conexion;

        function __construct($conexion){
            $this->conexion = $conexion;
        }

        function insertar($visitante_id, $apartamento_id, $usuario_id, $fecha_visita, $con_vehiculo, $placa){
            if ($placa == "") {
                $placa_sql = "NULL";
            } else {
                $placa_sql = "'$placa'";
            }

            $sql = "INSERT INTO autorizaciones (visitante_id, apartamento_id, usuario_id, fecha_visita, con_vehiculo, placa) VALUES ($visitante_id, $apartamento_id, $usuario_id, '$fecha_visita', $con_vehiculo, $placa_sql)";
            $resultado = mysqli_query($this->conexion, $sql);

            if ($resultado) {
                $res = ["OK", "Visitante preautorizado"];
            } else {
                $res = ["ERROR", mysqli_error($this->conexion)];
            }

            return $res;
        }

        function listar($usuario_id){
            $usuario_id = intval($usuario_id);
            $sql = "SELECT au.id, v.nombre AS visitante, v.documento, au.fecha_visita, au.con_vehiculo, au.placa, a.numero AS apartamento, t.nombre AS torre, CONCAT(u.nombres, ' ', u.apellidos) AS residente
                    FROM autorizaciones au
                    INNER JOIN visitantes v ON au.visitante_id = v.id
                    INNER JOIN apartamentos a ON au.apartamento_id = a.id
                    INNER JOIN torres t ON a.torre_id = t.id
                    INNER JOIN usuarios u ON au.usuario_id = u.id
                    WHERE au.usuario_id = $usuario_id
                    ORDER BY au.fecha_visita DESC, au.id DESC";
            $resultado = mysqli_query($this->conexion, $sql);

            $vec = [];

            while ($fila = mysqli_fetch_assoc($resultado)) {
                $vec[] = $fila;
            }

            return $vec;
        }

        function apartamentos($usuario_id){
            $usuario_id = intval($usuario_id);
            $sql = "SELECT a.id, t.nombre AS torre, a.numero
                    FROM residencias r
                    INNER JOIN apartamentos a ON r.apartamento_id = a.id
                    INNER JOIN torres t ON a.torre_id = t.id
                    WHERE r.usuario_id = $usuario_id
                    ORDER BY t.nombre, a.numero";
            $resultado = mysqli_query($this->conexion, $sql);

            $vec = [];

            while ($fila = mysqli_fetch_assoc($resultado)) {
                $vec[] = $fila;
            }

            return $vec;
        }
    }

?>
