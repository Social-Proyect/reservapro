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
    $nombre = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $servicio_id = (int)($_POST['servicio_id'] ?? 0);
    $empleado_id = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $notas = sanitize($_POST['notas'] ?? '');
    $duracion_minutos = (int)($_POST['duracion_minutos'] ?? 0);
    $precio = (float)($_POST['precio'] ?? 0);
    
    if (empty($nombre) || empty($telefono) || $servicio_id === 0 || empty($fecha) || empty($hora)) {
        jsonResponse(['success' => false, 'message' => 'Faltan datos requeridos']);
    }
    
    // Verificar si el cliente ya existe
    $stmt = $db->prepare("SELECT id FROM clientes WHERE telefono = ? AND empresa_id = ?");
    $stmt->execute([$telefono, $empresa_id]);
    $cliente = $stmt->fetch();
    
    if ($cliente) {
        $cliente_id = $cliente['id'];
        // Actualizar datos del cliente si es necesario
        $stmt = $db->prepare("UPDATE clientes SET nombre = ?, apellido = ?, email = ? WHERE id = ?");
        $stmt->execute([$nombre, $apellido, $email, $cliente_id]);
    } else {
        // Crear nuevo cliente
        $stmt = $db->prepare("
            INSERT INTO clientes (empresa_id, nombre, apellido, telefono, email) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$empresa_id, $nombre, $apellido, $telefono, $email]);
        $cliente_id = $db->lastInsertId();
    }
    
    // Crear la cita
    $fecha_hora = $fecha . ' ' . $hora . ':00';
    $codigo_confirmacion = generarCodigoConfirmacion();
    
    $stmt = $db->prepare("
        INSERT INTO citas (
            empresa_id, cliente_id, servicio_id, empleado_id, 
            fecha_hora, duracion_minutos, precio, estado, 
            notas_cliente, codigo_confirmacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmada', ?, ?)
    ");
    
    $stmt->execute([
        $empresa_id, $cliente_id, $servicio_id, $empleado_id,
        $fecha_hora, $duracion_minutos, $precio, $notas, $codigo_confirmacion
    ]);
    
    $cita_id = $db->lastInsertId();
    
    jsonResponse([
        'success' => true, 
        'message' => 'Cita creada exitosamente',
        'cita_id' => $cita_id,
        'codigo' => $codigo_confirmacion
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error al crear la cita: ' . $e->getMessage()], 500);
}
