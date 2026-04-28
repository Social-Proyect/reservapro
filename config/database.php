<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'reservapro');

// Configuración general
define('SITE_URL', 'http://localhost/reservapro');
define('TIMEZONE', 'America/Mexico_City');

// Configuración de sesión (antes de iniciar la sesión)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Establecer zona horaria
date_default_timezone_set(TIMEZONE);

// Clase de conexión a base de datos
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            // Conexión deshabilitada. Usar config/supabase.php para Supabase/PostgreSQL
            throw new Exception('Conexión MySQL deshabilitada. Usa config/supabase.php');
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("No se puede deserializar singleton");
    }
}

// Función helper para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Función para sanitizar entrada
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función para generar código de confirmación único
function generarCodigoConfirmacion() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

// Función para formatear fecha
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    return date($formato, strtotime($fecha));
}

// Función para respuesta JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
