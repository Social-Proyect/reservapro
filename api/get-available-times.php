<?php
header('Content-Type: application/json');
require_once '../config/supabase.php';
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}


try {
    $fecha = $_GET['fecha'] ?? null;
    $servicio_id = $_GET['servicio_id'] ?? null;
    $empleado_id = $_GET['empleado_id'] ?? null;
    $empresa_id = $_GET['empresa_id'] ?? null;
    if (!$fecha || !$servicio_id || !$empresa_id) {
        throw new Exception('Fecha, servicio y empresa requeridos');
    }

    // Obtener duración del servicio desde Supabase
    $servicio_resp = supabase_request("/rest/v1/servicios?id=eq.$servicio_id&empresa_id=eq.$empresa_id");
    if (!isset($servicio_resp['data'][0])) {
        throw new Exception('Servicio no encontrado');
    }
    $servicio = $servicio_resp['data'][0];
    $duracion = $servicio['duracion_minutos'];
    
    // Obtener día de la semana
    $fecha_obj = new DateTime($fecha);
    $dia_semana = $fecha_obj->format('w');
    
    // Obtener horarios de trabajo
    if ($empleado_id && $empleado_id != 0) {
        // Empleado específico
        $horarios_resp = supabase_request("/rest/v1/horarios_empleados?empleado_id=eq.$empleado_id&dia_semana=eq.$dia_semana&activo=eq.1");
        $horarios = isset($horarios_resp['data']) ? $horarios_resp['data'] : [];
        $empleados_ids = [$empleado_id];
    } else {
        // Todos los empleados que pueden hacer el servicio y pertenezcan a la empresa
        $horarios_resp = supabase_request("/rest/v1/horarios_empleados?dia_semana=eq.$dia_semana&activo=eq.1");
        $horarios = isset($horarios_resp['data']) ? $horarios_resp['data'] : [];
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
                // Verificar bloqueos de horario por el administrador en Supabase
                foreach ($empleados_ids as $emp_id) {
                    $bloqueo_resp = supabase_request("/rest/v1/bloqueos_horario?or=(empleado_id.is.null,empleado_id.eq.$emp_id)&fecha_inicio=lte." . $slot_datetime->format('Y-m-d H:i:s') . "&fecha_fin=gt." . $slot_datetime->format('Y-m-d H:i:s'));
                    $bloqueo = isset($bloqueo_resp['data']) ? $bloqueo_resp['data'] : [];
                    if (count($bloqueo) > 0) {
                        $disponible = false;
                        break;
                    }
                }
                // Verificar para cada empleado que podría hacer el servicio en Supabase
                if ($disponible) {
                    foreach ($empleados_ids as $emp_id) {
                        $citas_resp = supabase_request("/rest/v1/citas?empleado_id=eq.$emp_id&estado=in.(pendiente,confirmada)&fecha_hora=eq." . $slot_datetime->format('Y-m-d H:i:s'));
                        $result = isset($citas_resp['data']) ? $citas_resp['data'] : [];
                        if (count($result) == 0) {
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
