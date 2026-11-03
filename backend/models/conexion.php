<?php
    $server = "localhost";
    $user = "root";
    $clave = "";
    $bd = "conjunto_app";

    $conexion = mysqli_connect($server, $user, $clave) or die("No se conecto a mysql " . mysqli_connect_error());
    mysqli_select_db($conexion, $bd) or die("No encontro la base de datos " . mysqli_connect_error());

?>
