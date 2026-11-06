<?php

require_once __DIR__ . '/conexion.php';

class Pagos {
    private mysqli $conexion;

    public function __construct()
    {
        $this->conexion = (new Conexion())->conectar();
    }
    
}