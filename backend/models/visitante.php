<?php
    class Visitante{
        private $conexion;

        function __construct($conexion){
            $this->conexion = $conexion;
        }

        function buscarPorDocumento($documento){
            $sql = "SELECT id, documento, nombre, telefono FROM visitantes WHERE documento = '$documento' LIMIT 1";
            $resultado = mysqli_query($this->conexion, $sql);
            $fila = mysqli_fetch_assoc($resultado);
            return $fila;
        }

        function insertar($documento, $nombre, $telefono){
            if ($telefono == "") {
                $telefono_sql = "NULL";
            } else {
                $telefono_sql = "'$telefono'";
            }

            $sql = "INSERT INTO visitantes (documento, nombre, telefono) VALUES ('$documento', '$nombre', $telefono_sql)";
            $resultado = mysqli_query($this->conexion, $sql);

            if ($resultado) {
                $res = ["OK", mysqli_insert_id($this->conexion)];
            } else {
                $res = ["ERROR", mysqli_error($this->conexion)];
            }

            return $res;
        }
    }

?>
