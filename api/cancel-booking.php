<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $cita_id = $_POST['cita_id'] ?? null;
    $empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
    $motivo = sanitize($_POST['motivo'] ?? '');

    if (!$cita_id || !$empresa_id) {
        throw new Exception('ID de cita y empresa_id requeridos');
    }

    $db = getDB();
    $db->beginTransaction();

    // Verificar que la cita existe y puede ser cancelada
    $stmt = $db->prepare("
        SELECT c.*, cl.total_no_shows 
        FROM citas c
        INNER JOIN clientes cl ON c.cliente_id = cl.id
        WHERE c.id = ? AND c.empresa_id = ? AND c.estado IN ('pendiente', 'confirmada')
    ");
    $stmt->execute([$cita_id, $empresa_id]);
    $cita = $stmt->fetch();

    if (!$cita) {
        throw new Exception('Cita no encontrada o no puede ser cancelada');
    }

    // Verificar tiempo de antelación
    $stmt = $db->prepare("SELECT minutos_antelacion_cancelacion FROM configuracion WHERE empresa_id = ? LIMIT 1");
    $stmt->execute([$empresa_id]);
    $config = $stmt->fetch();
    
    $fecha_cita = new DateTime($cita['fecha_hora']);
    $ahora = new DateTime();
    $diferencia_minutos = ($fecha_cita->getTimestamp() - $ahora->getTimestamp()) / 60;

    if ($diferencia_minutos < $config['minutos_antelacion_cancelacion']) {
        throw new Exception("Las cancelaciones deben realizarse con al menos {$config['minutos_antelacion_cancelacion']} minutos de anticipación");
    }

    // Actualizar estado de la cita
    $stmt = $db->prepare("
        UPDATE citas 
        SET estado = 'cancelada', 
            cancelada_por = 'cliente',
            motivo_cancelacion = ?
        WHERE id = ? AND empresa_id = ?
    ");
    $stmt->execute([$motivo, $cita_id, $empresa_id]);

    $db->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Cita cancelada exitosamente'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
