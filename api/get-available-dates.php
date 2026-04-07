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
    $servicio_id = $_GET['servicio_id'] ?? null;
    $empleado_id = $_GET['empleado_id'] ?? null;
    $empresa_id = $_GET['empresa_id'] ?? null;
    if (!$servicio_id || !$empresa_id) {
        throw new Exception('ID de servicio y empresa requeridos');
    }

    // Obtener duración del servicio desde Supabase
    $servicio_resp = supabase_request("/rest/v1/servicios?id=eq.$servicio_id&empresa_id=eq.$empresa_id");
    if (!isset($servicio_resp['data'][0])) {
        throw new Exception('Servicio no encontrado');
    }
    $servicio = $servicio_resp['data'][0];
    // Obtener configuración de la empresa desde Supabase
    $config_resp = supabase_request("/rest/v1/configuracion?empresa_id=eq.$empresa_id");
    if (!isset($config_resp['data'][0])) {
        throw new Exception('Configuración no encontrada');
    }
    $config = $config_resp['data'][0];

    // Calcular fechas disponibles (próximos 30 días)
    $fechas_disponibles = [];
    $fecha_inicio = new DateTime();
    $fecha_inicio->modify('+' . ceil($config['minutos_antelacion_reserva'] / 1440) . ' days');
    
    for ($i = 0; $i < 30; $i++) {
        $fecha = clone $fecha_inicio;
        $fecha->modify("+{$i} days");
        $dia_semana = $fecha->format('w');
        $fecha_str = $fecha->format('Y-m-d');
        // Buscar horarios en Supabase
        if ($empleado_id && $empleado_id != 0) {
            $horarios_resp = supabase_request("/rest/v1/horarios_empleados?empleado_id=eq.$empleado_id&dia_semana=eq.$dia_semana&activo=eq.1");
        } else {
            $horarios_resp = supabase_request("/rest/v1/horarios_empleados?dia_semana=eq.$dia_semana&activo=eq.1&empleado_id=not.is.null");
        }
        $horarios = isset($horarios_resp['data']) ? $horarios_resp['data'] : [];
        $hay_horario_libre = false;
        foreach ($horarios as $horario) {
            $hora_inicio = new DateTime($fecha_str . ' ' . $horario['hora_inicio']);
            $hora_fin = new DateTime($fecha_str . ' ' . $horario['hora_fin']);
            // Buscar bloqueos en Supabase
            $bloqueo_resp = supabase_request("/rest/v1/bloqueos_horario?or=(empleado_id.is.null,empleado_id.eq." . ($empleado_id ?: 0) . ")&fecha_inicio=lte." . $hora_inicio->format('Y-m-d H:i:s') . "&fecha_fin=gte." . $hora_fin->format('Y-m-d H:i:s'));
            $bloqueo = isset($bloqueo_resp['data']) ? $bloqueo_resp['data'] : [];
            if (count($bloqueo) == 0) {
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
