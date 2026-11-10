<?php
require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// Envío cada solicitud de PQRS al controlador para validar sesión, rol y datos.
require_once __DIR__ . '/../controllers/pqrsController.php';
