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
            $sql = "SELECT id, parqueadero_id, fecha_ingreso FROM ingresos WHERE visitante_id = $visitante_id AND fecha_salida IS NULL ORDER BY id DESC LIMIT 1";

            $resultado = mysqli_query($this->conexion, $sql);

            if (!$resultado) {
                return null;
            }

            return mysqli_fetch_assoc($resultado);
        }

        // Registra la entrada del visitante
        function insertar($visitante_id, $apartamento_id, $portero_id, $placa, $parqueadero_id = 0){
            $visitante_id = intval($visitante_id);
            $apartamento_id = intval($apartamento_id);
            $portero_id = intval($portero_id);
            $parqueadero_id = intval($parqueadero_id);

            if ($placa == null) {
                $placa = "";
            }

            $placa = trim($placa);

            if ($placa == "") {
                $valorPlaca = "NULL";
            } else {
                $valorPlaca = "'" . mysqli_real_escape_string($this->conexion, $placa) . "'";
            }

            if ($parqueadero_id > 0) {
                $valorParqueadero = $parqueadero_id;
            } else {
                $valorParqueadero = "NULL";
            }

            $sql = "INSERT INTO ingresos (visitante_id, apartamento_id, portero_id, parqueadero_id, placa) VALUES ($visitante_id, $apartamento_id, $portero_id, $valorParqueadero, $valorPlaca)";

            $resultado = mysqli_query($this->conexion, $sql);

            if ($resultado) {
                $res = ["OK", "Ingreso registrado"];
            } else {
                $res = ["ERROR", mysqli_error($this->conexion)];
            }

            return $res;
        }

        // Registra la salida del visitante, libera su parqueadero y devuelve cuantas filas se actualizaron
        function registrarSalida($visitante_id){
            $visitante_id = intval($visitante_id);
            $abierto = $this->buscarAbierto($visitante_id);

            if ($abierto == null) {
                return 0;
            }

            $sql = "UPDATE ingresos SET fecha_salida = NOW() WHERE id = " . intval($abierto['id']);
            mysqli_query($this->conexion, $sql);
            $filas = mysqli_affected_rows($this->conexion);

            if ($filas > 0 && $abierto['parqueadero_id'] != null) {
                $this->liberarParqueadero($abierto['parqueadero_id']);
            }

            return $filas;
        }

        // Lista todos los parqueaderos de visitantes con su estado
        function listarParqueaderos(){
            $sql = "SELECT id, codigo, ocupado FROM parqueaderos_visitantes ORDER BY codigo";
            $resultado = mysqli_query($this->conexion, $sql);
            $lista = [];

            if ($resultado) {
                while ($fila = mysqli_fetch_assoc($resultado)) {
                    $lista[] = $fila;
                }
            }

            return $lista;
        }

        function buscarParqueadero($id){
            $id = intval($id);
            $sql = "SELECT id, codigo, ocupado FROM parqueaderos_visitantes WHERE id = $id LIMIT 1";
            $resultado = mysqli_query($this->conexion, $sql);

            if (!$resultado) {
                return null;
            }

            return mysqli_fetch_assoc($resultado);
        }

        function insertarParqueadero($codigo){
            $codigo = trim($codigo);

            if ($codigo == "") {
                return ["ERROR", "Escriba el codigo del parqueadero"];
            }

            $codigo = mysqli_real_escape_string($this->conexion, $codigo);
            $sql = "INSERT INTO parqueaderos_visitantes (codigo, ocupado) VALUES ('$codigo', 0)";
            $resultado = mysqli_query($this->conexion, $sql);

            if ($resultado) {
                $res = ["OK", "Parqueadero creado"];
            } else {
                $res = ["ERROR", mysqli_error($this->conexion)];
            }

            return $res;
        }

        function editarParqueadero($id, $codigo){
            $id = intval($id);
            $codigo = trim($codigo);

            if ($codigo == "") {
                return ["ERROR", "Escriba el codigo del parqueadero"];
            }

            $codigo = mysqli_real_escape_string($this->conexion, $codigo);
            $sql = "UPDATE parqueaderos_visitantes SET codigo = '$codigo' WHERE id = $id";
            $resultado = mysqli_query($this->conexion, $sql);

            if ($resultado && mysqli_affected_rows($this->conexion) > 0) {
                $res = ["OK", "Parqueadero actualizado"];
            } elseif ($resultado) {
                $res = ["ERROR", "No se encontro el parqueadero"];
            } else {
                $res = ["ERROR", mysqli_error($this->conexion)];
            }

            return $res;
        }

        function eliminarParqueadero($id){
            $parqueadero = $this->buscarParqueadero($id);

            if ($parqueadero == null) {
                return ["ERROR", "No se encontro el parqueadero"];
            }

            if ($parqueadero['ocupado'] == 1) {
                return ["ERROR", "No se puede eliminar un parqueadero ocupado"];
            }

            $id = intval($id);
            $sql = "DELETE FROM parqueaderos_visitantes WHERE id = $id";
            $resultado = mysqli_query($this->conexion, $sql);

            if ($resultado && mysqli_affected_rows($this->conexion) > 0) {
                $res = ["OK", "Parqueadero eliminado"];
            } else {
                $res = ["ERROR", "No se encontro el parqueadero"];
            }

            return $res;
        }

        // Marca el parqueadero como ocupado. Devuelve 0 si ya estaba ocupado.
        function ocuparParqueadero($id){
            $id = intval($id);
            $sql = "UPDATE parqueaderos_visitantes SET ocupado = 1 WHERE id = $id AND ocupado = 0";
            mysqli_query($this->conexion, $sql);

            return mysqli_affected_rows($this->conexion);
        }

        function liberarParqueadero($id){
            $id = intval($id);
            $sql = "UPDATE parqueaderos_visitantes SET ocupado = 0 WHERE id = $id";
            mysqli_query($this->conexion, $sql);
        }

        // Bitacora de ingresos con los filtros de fecha, torre y apartamento
        function listarBitacora($fecha, $torre, $apartamento){
            $condicion = "";

            if ($fecha != "") {
                $condicion .= " AND DATE(i.fecha_ingreso) = '$fecha'";
            }

            if ($torre != "") {
                $condicion .= " AND t.nombre LIKE '%$torre%'";
            }

            if ($apartamento != "") {
                $condicion .= " AND a.numero LIKE '%$apartamento%'";
            }

            $sql = "SELECT i.id, v.nombre AS visitante, v.documento, a.numero AS apartamento, t.nombre AS torre, CONCAT(u.nombres, ' ', u.apellidos) AS portero, p.codigo AS parqueadero, i.placa, i.fecha_ingreso, i.fecha_salida, CASE WHEN i.fecha_salida IS NULL THEN 'Dentro' ELSE 'Fuera' END AS estado
                    FROM ingresos i
                    INNER JOIN visitantes v ON i.visitante_id = v.id
                    INNER JOIN apartamentos a ON i.apartamento_id = a.id
                    INNER JOIN torres t ON a.torre_id = t.id
                    INNER JOIN usuarios u ON i.portero_id = u.id
                    LEFT JOIN parqueaderos_visitantes p ON i.parqueadero_id = p.id
                    WHERE 1 = 1$condicion
                    ORDER BY i.fecha_ingreso DESC, i.id DESC
                    LIMIT 100";

            $resultado = mysqli_query($this->conexion, $sql);
            $lista = [];

            if ($resultado) {
                while ($fila = mysqli_fetch_assoc($resultado)) {
                    $lista[] = $fila;
                }
            }

            return $lista;
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
