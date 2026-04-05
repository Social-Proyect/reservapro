<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $servicio_id = $_GET['servicio_id'] ?? null;
    $empleado_id = $_GET['empleado_id'] ?? null;
    $empresa_id = $_GET['empresa_id'] ?? null;
    if (!$servicio_id || !$empresa_id) {
        throw new Exception('ID de servicio y empresa requeridos');
    }

    $db = getDB();
    // Obtener duración del servicio
    $stmt = $db->prepare("SELECT duracion_minutos FROM servicios WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$servicio_id, $empresa_id]);
    $servicio = $stmt->fetch();
    if (!$servicio) {
        throw new Exception('Servicio no encontrado');
    }
    // Obtener configuración de la empresa
    $stmt = $db->prepare("SELECT minutos_antelacion_reserva, hora_cierre_reservas_mismo_dia FROM configuracion WHERE empresa_id = ? LIMIT 1");
    $stmt->execute([$empresa_id]);
    $config = $stmt->fetch();

    // Calcular fechas disponibles (próximos 30 días)
    $fechas_disponibles = [];
    $fecha_inicio = new DateTime();
    $fecha_inicio->modify('+' . ceil($config['minutos_antelacion_reserva'] / 1440) . ' days');
    
    for ($i = 0; $i < 30; $i++) {
        $fecha = clone $fecha_inicio;
        $fecha->modify("+{$i} days");
        
        $dia_semana = $fecha->format('w');
        
        // Verificar si hay empleados trabajando ese día
        if ($empleado_id && $empleado_id != 0) {
            // Empleado específico
            $stmt = $db->prepare("
                SELECT hora_inicio, hora_fin 
                FROM horarios_empleados 
                WHERE empleado_id = ? AND dia_semana = ? AND activo = 1
            ");
            $stmt->execute([$empleado_id, $dia_semana]);
        } else {
            // Cualquier empleado que pueda hacer el servicio y pertenezca a la empresa
            $stmt = $db->prepare("
                SELECT he.hora_inicio, he.hora_fin 
                FROM horarios_empleados he
                INNER JOIN empleado_servicio es ON he.empleado_id = es.empleado_id
                INNER JOIN empleados e ON he.empleado_id = e.id
                WHERE es.servicio_id = ? AND he.dia_semana = ? AND he.activo = 1 AND e.empresa_id = ?
            ");
            $stmt->execute([$servicio_id, $dia_semana, $empresa_id]);
        }
        $horarios = $stmt->fetchAll();
        $fecha_str = $fecha->format('Y-m-d');
        $hay_horario_libre = false;
        foreach ($horarios as $horario) {
            $hora_inicio = new DateTime($fecha_str . ' ' . $horario['hora_inicio']);
            $hora_fin = new DateTime($fecha_str . ' ' . $horario['hora_fin']);
            // Buscar bloqueos que cubran este horario
            $stmtBloqueo = $db->prepare("
                SELECT COUNT(*) as count 
                FROM bloqueos_horario 
                WHERE (empleado_id IS NULL OR empleado_id = ?)
                AND (
                    (fecha_inicio <= ? AND fecha_fin > ?)
                    OR (fecha_inicio < ? AND fecha_fin >= ?)
                )
            ");
            $stmtBloqueo->execute([
                $empleado_id ?: 0,
                $hora_inicio->format('Y-m-d H:i:s'),
                $hora_inicio->format('Y-m-d H:i:s'),
                $hora_fin->format('Y-m-d H:i:s'),
                $hora_fin->format('Y-m-d H:i:s')
            ]);
            $bloqueo = $stmtBloqueo->fetch();
            if ($bloqueo['count'] == 0) {
                $hay_horario_libre = true;
                break;
            }
        }
        if ($horarios && $hay_horario_libre) {
            $fechas_disponibles[] = $fecha_str;
        }
    }

    jsonResponse([
        'success' => true,
        'fechas' => $fechas_disponibles
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
