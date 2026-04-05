<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {

    $servicio_id = $_GET['servicio_id'] ?? null;
    $empresa_id = $_GET['empresa_id'] ?? null;
    if (!$servicio_id || !$empresa_id) {
        throw new Exception('ID de servicio y empresa requeridos');
    }

    $db = getDB();
    // Obtener empleados que pueden realizar este servicio y pertenecen a la empresa
    $stmt = $db->prepare("
        SELECT DISTINCT e.id, e.nombre, e.apellido, e.foto, e.especialidad, e.descripcion
        FROM empleados e
        INNER JOIN empleado_servicio es ON e.id = es.empleado_id
        WHERE es.servicio_id = ? AND e.empresa_id = ? AND e.activo = 1
        ORDER BY e.nombre
    ");
    $stmt->execute([$servicio_id, $empresa_id]);
    $empleados = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'empleados' => $empleados
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
