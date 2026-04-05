<?php
// php/auth.php - Versión simplificada y depurada

// Incluir config solo una vez
require_once 'config.php';

// Verificar si ya hay salida
if (headers_sent()) {
    exit;
}

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($username, $email, $password) {
        // Validaciones básicas
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Todos los campos son requeridos'];
        }

        try {
            // Verificar si el usuario ya existe
            $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":username", $username);
            $checkStmt->bindParam(":email", $email);
            $checkStmt->execute();
            
            if($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'El usuario o email ya existe'];
            }

            // Insertar nuevo usuario
            $query = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password)";
            $stmt = $this->conn->prepare($query);
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password_hash);
            
            if($stmt->execute()) {
                $user_id = $this->conn->lastInsertId();
                
                // Inicializar estado del juego
                $this->initializeGameState($user_id);
                
                // Iniciar sesión automáticamente
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                
                return ['success' => true, 'message' => 'Registro exitoso. ¡Bienvenido a Factory Force!'];
            }
            
            return ['success' => false, 'message' => 'Error en el registro'];
            
        } catch (PDOException $e) {
            error_log("Error en registro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
    
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return false;
        }

        try {
            $query = "SELECT id, username, password_hash FROM users WHERE username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            
            if($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if(password_verify($password, $row['password_hash'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    return true;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }
    
    private function initializeGameState($user_id) {
        try {
            // Estado inicial del juego
            $query = "INSERT INTO game_state (user_id, money) VALUES (:user_id, 100)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            // Primer trabajador gratis
            $query = "INSERT INTO workers (user_id, name, type, hire_cost) VALUES (:user_id, 'Trabajador Inicial', 'basic', 0)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            // Primera fábrica básica
            $query = "INSERT INTO factories (user_id, type, upgrade_cost) VALUES (:user_id, 'basic', 500)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("Error inicializando juego: " . $e->getMessage());
            return false;
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function logout() {
        session_destroy();
        header("Location: ../index.php");
        exit;
    }
}

// Solo procesar si es una solicitud POST
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Establecer headers primero
    header('Content-Type: application/json');
    
    // Obtener los datos de entrada
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Si no es JSON, usar $_POST
    if ($input === null) {
        $input = $_POST;
    }
    
    // Verificar que tenemos una acción
    if (!isset($input['action'])) {
        echo json_encode(['success' => false, 'message' => 'No se especificó acción']);
        exit;
    }
    
    $auth = new Auth();
    $response = [];
    
    try {
        switch($input['action']) {
            case 'register':
                $response = $auth->register(
                    $input['username'] ?? '',
                    $input['email'] ?? '',
                    $input['password'] ?? ''
                );
                break;
                
            case 'login':
                if($auth->login($input['username'] ?? '', $input['password'] ?? '')) {
                    $response = ['success' => true, 'message' => 'Login exitoso. ¡Bienvenido de vuelta!'];
                } else {
                    $response = ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
                }
                break;
                
            case 'logout':
                $auth->logout();
                // No debería llegar aquí porque logout redirige
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Acción no reconocida'];
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}
?>