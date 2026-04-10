<?php
// php/config.php
if (!class_exists('Database')) {
    class Database {
        private $host = "localhost";
        private $db_name = "factory_game";
        private $username = "root";
        private $password = "";
        public $conn;

        public function getConnection() {
            $this->conn = null;
            try {
                // Conexión migrada a config/supabase.php para Supabase/PostgreSQL
                require_once __DIR__ . '/../config/supabase.php';
                $this->conn = $pdo;
                $this->conn->exec("set names utf8");
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                // No mostrar errores al usuario, solo log
                error_log("Error de conexión: " . $exception->getMessage());
                return null;
            }
            return $this->conn;
        }
    }
}

// Iniciar sesión solo si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>