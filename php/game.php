<?php
require_once 'config.php';

class Game {
    private $conn;
    private $user_id;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    public function loadGameState() {
        if (!$this->user_id) {
            return ['error' => 'Usuario no autenticado'];
        }
        
        try {
            $query = "SELECT gs.* 
                      FROM game_state gs
                      WHERE gs.user_id = :user_id";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->execute();
            
            $gameState = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$gameState) {
                // Si no existe estado del juego, crear uno
                $this->initializeGameState($this->user_id);
                $stmt->execute();
                $gameState = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Cargar trabajadores
            $gameState['workers'] = $this->getWorkers();
            
            // Cargar fábricas
            $gameState['factories'] = $this->getFactories();
            
            // Cargar mejoras
            $gameState['upgrades'] = $this->getUpgrades();
            
            return $gameState;
            
        } catch (PDOException $e) {
            error_log("Error loading game state: " . $e->getMessage());
            return ['error' => 'Error al cargar el juego'];
        }
    }
    
    private function getWorkers() {
        $query = "SELECT * FROM workers WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getFactories() {
        $query = "SELECT f.* 
                  FROM factories f
                  WHERE f.user_id = :user_id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUpgrades() {
        $query = "SELECT * FROM upgrades WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function saveGameState($gameData) {
        if (!$this->user_id) {
            return ['success' => false, 'message' => 'Usuario no autenticado'];
        }
        
        try {
            $query = "UPDATE game_state SET 
                      money = :money,
                      gems = :gems,
                      prestige_points = :prestige_points,
                      level = :level,
                      experience = :experience,
                      last_update = NOW()
                      WHERE user_id = :user_id";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":money", $gameData['money']);
            $stmt->bindParam(":gems", $gameData['gems']);
            $stmt->bindParam(":prestige_points", $gameData['prestige_points']);
            $stmt->bindParam(":level", $gameData['level']);
            $stmt->bindParam(":experience", $gameData['experience']);
            $stmt->bindParam(":user_id", $this->user_id);
            
            $result = $stmt->execute();
            return ['success' => $result, 'message' => $result ? 'Juego guardado' : 'Error al guardar'];
            
        } catch (PDOException $e) {
            error_log("Error saving game: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos'];
        }
    }
    
    public function hireWorker($workerType) {
        if (!$this->user_id) {
            return ['success' => false, 'message' => 'Usuario no autenticado'];
        }
        
        try {
            $workerCosts = [
                'basic' => 100,
                'advanced' => 500,
                'expert' => 2000
            ];
            
            $cost = $workerCosts[$workerType] ?? 100;
            
            // Verificar si tiene dinero suficiente
            $query = "SELECT money FROM game_state WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->execute();
            $money = $stmt->fetchColumn();
            
            if ($money < $cost) {
                return ['success' => false, 'message' => 'Dinero insuficiente'];
            }
            
            // Crear trabajador
            $names = ['Ana', 'Carlos', 'Miguel', 'Laura', 'David', 'Elena', 'Javier', 'Sofia'];
            $surnames = ['Gómez', 'López', 'Martínez', 'García', 'Rodríguez', 'Fernández'];
            
            $name = $names[array_rand($names)] . ' ' . $surnames[array_rand($surnames)];
            
            $query = "INSERT INTO workers (user_id, name, type, hire_cost) 
                      VALUES (:user_id, :name, :type, :cost)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":type", $workerType);
            $stmt->bindParam(":cost", $cost);
            
            if ($stmt->execute()) {
                // Restar dinero
                $newMoney = $money - $cost;
                $query = "UPDATE game_state SET money = :money WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":money", $newMoney);
                $stmt->bindParam(":user_id", $this->user_id);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Trabajador contratado: ' . $name];
            }
            
            return ['success' => false, 'message' => 'Error al contratar trabajador'];
            
        } catch (PDOException $e) {
            error_log("Error hiring worker: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos'];
        }
    }
    
    private function initializeGameState($user_id) {
        $query = "INSERT INTO game_state (user_id, money, gems) VALUES (:user_id, 100, 10)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        return $stmt->execute();
    }
}

// Manejar solicitudes AJAX
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action'])) {
        $game = new Game();
        
        switch ($_GET['action']) {
            case 'load':
                $gameState = $game->loadGameState();
                echo json_encode($gameState);
                break;
                
            default:
                echo json_encode(['error' => 'Acción no válida']);
                break;
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $game = new Game();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input === null) {
            echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
            exit;
        }
        
        switch ($input['action'] ?? '') {
            case 'save':
                $result = $game->saveGameState($input);
                echo json_encode($result);
                break;
                
            case 'hire_worker':
                $result = $game->hireWorker($input['worker_type'] ?? 'basic');
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
                break;
        }
        exit;
    }
} catch (Exception $e) {
    error_log("Error en game.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>