<?php

class Conexion
{
    private string $host;
    private string $baseDatos;
    private string $usuario;
    private string $contrasena;
    private ?mysqli $conexion = null;

    public function __construct()
    {
        $this->host = '127.0.0.1';
        $this->baseDatos = 'conjunto_app';
        $usuario = "root";
        $contrasena = "";

        $this->usuario = $usuario;
        $this->contrasena = $contrasena;
    }

    public function conectar(): mysqli
    {
        if ($this->conexion === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->conexion = new mysqli(
                $this->host,
                $this->usuario,
                $this->contrasena,
                $this->baseDatos
            );
            $this->conexion->set_charset('utf8mb4');
        }

        return $this->conexion;
    }
}
