<?php
    // Inicia la misma sesion que usa backend/api/auth.php
    session_save_path(__DIR__ . '/../sessions');

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    function usuarioActual(){
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        return null;
    }
?>
