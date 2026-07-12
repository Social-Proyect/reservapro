<?php
require_once '../config/database.php';

// Verificar sesión de admin
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['empresa_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

$empresa_id = $_SESSION['empresa_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

try {
    $db = getDB();
    
    // Validar datos requeridos
    $tipo = sanitize($_POST['tipo'] ?? '');
    $empleado_id = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $motivo = sanitize($_POST['motivo'] ?? '');
    
    if (empty($tipo) || empty($fecha_inicio) || empty($fecha_fin)) {
        jsonResponse(['success' => false, 'message' => 'Faltan datos requeridos']);
    }
    
    // Validar que las fechas sean válidas
    $inicio = strtotime($fecha_inicio);
    $fin = strtotime($fecha_fin);
    
    if ($inicio === false || $fin === false) {
        jsonResponse(['success' => false, 'message' => 'Fechas inválidas']);
    }
    
    if ($fin <= $inicio) {
        jsonResponse(['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio']);
    }
    
    // Si se especifica un empleado, verificar que pertenece a la empresa
    if ($empleado_id) {
        $stmt = $db->prepare("SELECT id FROM empleados WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$empleado_id, $empresa_id]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Empleado no válido']);
        }
    }
    
    // Crear el bloqueo
    $stmt = $db->prepare("
        INSERT INTO bloqueos_horario (
            empleado_id, fecha_inicio, fecha_fin, motivo, tipo
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $empleado_id,
        date('Y-m-d H:i:s', $inicio),
        date('Y-m-d H:i:s', $fin),
        $motivo,
        $tipo
    ]);
    
    $bloqueo_id = $db->lastInsertId();
    
    jsonResponse([
        'success' => true, 
        'message' => 'Horario bloqueado exitosamente',
        'bloqueo_id' => $bloqueo_id
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error al crear el bloqueo: ' . $e->getMessage()], 500);
}
