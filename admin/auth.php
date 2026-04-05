<?php
session_start();

// Función simple de autenticación (debe mejorarse en producción)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
