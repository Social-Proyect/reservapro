<?php
require_once __DIR__ . '/../config/supabase.php';

class WorkerManager {
    private $conn;
    private $user_id;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    public function trainWorker($workerId) {
        if (!$this->user_id) {
            return ['success' => false, 'message' => 'Usuario no autenticado'];
        }
        
        try {
            // Obtener información del trabajador
            $query = "SELECT w.*, gs.money 
                      FROM workers w 
                      JOIN game_state gs ON w.user_id = gs.user_id 
                      WHERE w.id = :worker_id AND w.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":worker_id", $workerId);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->execute();
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$worker) {
                return ['success' => false, 'message' => 'Trabajador no encontrado'];
            }
            
            $trainingCost = $worker['level'] * 50;
            
            if ($worker['money'] < $trainingCost) {
                return ['success' => false, 'message' => 'Dinero insuficiente para entrenar'];
            }
            
            // Actualizar trabajador
            $query = "UPDATE workers SET 
                      level = level + 1,
                      experience = 0,
                      speed_skill = LEAST(10, speed_skill + 1),
                      efficiency_skill = LEAST(10, efficiency_skill + 1),
                      management_skill = LEAST(10, management_skill + 1)
                      WHERE id = :worker_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":worker_id", $workerId);
            $stmt->bindParam(":user_id", $this->user_id);
            
            if ($stmt->execute()) {
                // Restar dinero
                $newMoney = $worker['money'] - $trainingCost;
                $query = "UPDATE game_state SET money = :money WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":money", $newMoney);
                $stmt->bindParam(":user_id", $this->user_id);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Trabajador entrenado exitosamente'];
            }
            
            return ['success' => false, 'message' => 'Error al entrenar trabajador'];
            
        } catch (PDOException $e) {
            error_log("Error training worker: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos'];
        }
    }
}

// Manejar solicitudes
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input === null) {
            echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
            exit;
        }
        
        $workerManager = new WorkerManager();
        
        switch ($_GET['action'] ?? '') {
            case 'train':
                $result = $workerManager->trainWorker($input['worker_id'] ?? 0);
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
                break;
        }
        exit;
    }
} catch (Exception $e) {
    error_log("Error en workers.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>