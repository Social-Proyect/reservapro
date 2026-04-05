<?php
header('Content-Type: application/json');
require_once '../config/database.php';


try {
    $fecha = $_GET['fecha'] ?? null;
    $servicio_id = $_GET['servicio_id'] ?? null;
    $empleado_id = $_GET['empleado_id'] ?? null;
    $empresa_id = $_GET['empresa_id'] ?? null;
    if (!$fecha || !$servicio_id || !$empresa_id) {
        throw new Exception('Fecha, servicio y empresa requeridos');
    }

    $db = getDB();
    // Obtener duración del servicio
    $stmt = $db->prepare("SELECT duracion_minutos FROM servicios WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$servicio_id, $empresa_id]);
    $servicio = $stmt->fetch();
    if (!$servicio) {
        throw new Exception('Servicio no encontrado');
    }
    
    $duracion = $servicio['duracion_minutos'];
    
    // Obtener día de la semana
    $fecha_obj = new DateTime($fecha);
    $dia_semana = $fecha_obj->format('w');
    
    // Obtener horarios de trabajo
    if ($empleado_id && $empleado_id != 0) {
        // Empleado específico
        $stmt = $db->prepare("
            SELECT hora_inicio, hora_fin 
            FROM horarios_empleados 
            WHERE empleado_id = ? AND dia_semana = ? AND activo = 1
        ");
        $stmt->execute([$empleado_id, $dia_semana]);
        $horarios = $stmt->fetchAll();
        $empleados_ids = [$empleado_id];
    } else {
        // Todos los empleados que pueden hacer el servicio y pertenezcan a la empresa
        $stmt = $db->prepare("
            SELECT DISTINCT he.empleado_id, he.hora_inicio, he.hora_fin 
            FROM horarios_empleados he
            INNER JOIN empleado_servicio es ON he.empleado_id = es.empleado_id
            INNER JOIN empleados e ON he.empleado_id = e.id
            WHERE es.servicio_id = ? AND he.dia_semana = ? AND he.activo = 1 AND e.empresa_id = ?
        ");
        $stmt->execute([$servicio_id, $dia_semana, $empresa_id]);
        $horarios = $stmt->fetchAll();
        $empleados_ids = array_unique(array_column($horarios, 'empleado_id'));
    }
    
    if (empty($horarios)) {
        jsonResponse([
            'success' => true,
            'horarios' => []
        ]);
        return;
    }
    
    // Generar slots de tiempo disponibles
    $slots_disponibles = [];
    foreach ($horarios as $horario) {
        $hora_inicio = new DateTime($fecha . ' ' . $horario['hora_inicio']);
        $hora_fin = new DateTime($fecha . ' ' . $horario['hora_fin']);
        $hora_fin->modify("-{$duracion} minutes");
        $slot_actual = clone $hora_inicio;
        while ($slot_actual <= $hora_fin) {
            $hora_str = $slot_actual->format('H:i');
            $fecha_hora_completa = $fecha . ' ' . $hora_str;
            $ahora = new DateTime();
            $slot_datetime = new DateTime($fecha_hora_completa);
            if ($slot_datetime > $ahora) {
                $slot_fin = clone $slot_datetime;
                $slot_fin->modify("+{$duracion} minutes");
                $disponible = true;
                // Verificar bloqueos de horario por el administrador
                $bloqueoStmt = $db->prepare("
                    SELECT COUNT(*) as count
                    FROM bloqueos_horario
                    WHERE (empleado_id IS NULL OR empleado_id = ?)
                    AND fecha_inicio <= ?
                    AND fecha_fin > ?
                ");
                foreach ($empleados_ids as $emp_id) {
                    $bloqueoStmt->execute([
                        $emp_id,
                        $slot_datetime->format('Y-m-d H:i:s'),
                        $slot_datetime->format('Y-m-d H:i:s')
                    ]);
                    $bloqueo = $bloqueoStmt->fetch();
                    if (!isset($bloqueo['count']) || $bloqueo['count'] > 0) {
                        $disponible = false;
                        break;
                    }
                }
                // Verificar para cada empleado que podría hacer el servicio
                if ($disponible) {
                    foreach ($empleados_ids as $emp_id) {
                        $stmt = $db->prepare("
                            SELECT COUNT(*) as count 
                            FROM citas 
                            WHERE empleado_id = ? 
                            AND estado IN ('pendiente', 'confirmada')
                            AND (
                                (fecha_hora < ? AND DATE_ADD(fecha_hora, INTERVAL duracion_minutos MINUTE) > ?)
                                OR (fecha_hora >= ? AND fecha_hora < ?)
                            )
                        ");
                        $stmt->execute([
                            $emp_id,
                            $slot_datetime->format('Y-m-d H:i:s'),
                            $slot_datetime->format('Y-m-d H:i:s'),
                            $slot_datetime->format('Y-m-d H:i:s'),
                            $slot_fin->format('Y-m-d H:i:s')
                        ]);
                        $result = $stmt->fetch();
                        if (!isset($result['count']) || $result['count'] == 0) {
                            $disponible = true;
                            break;
                        } else {
                            $disponible = false;
                        }
                    }
                }
                if ($disponible && !in_array($hora_str, $slots_disponibles)) {
                    $slots_disponibles[] = $hora_str;
                }
            }
            $slot_actual->modify('+' . $duracion . ' minutes');
        }
    }
    sort($slots_disponibles);
    jsonResponse([
        'success' => true,
        'horarios' => $slots_disponibles
    ]);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
