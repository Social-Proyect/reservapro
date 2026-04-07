<?php
// Devuelve las citas en formato FullCalendar para el admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}
require_once '../config/supabase.php';
header('Content-Type: application/json');


$empresa_id = $_SESSION['empresa_id'] ?? null;
$eventos = [];
// Obtener citas desde Supabase
$citas_resp = supabase_request("/rest/v1/citas?empresa_id=eq.$empresa_id&select=id,fecha_hora,duracion_minutos,estado,servicio_id,cliente_id,servicios(nombre),clientes(nombre)");
$citas = isset($citas_resp['data']) ? $citas_resp['data'] : [];
foreach ($citas as $cita) {
    $start = $cita['fecha_hora'];
    $end = date('Y-m-d H:i:s', strtotime($start . ' + ' . $cita['duracion_minutos'] . ' minutes'));
    $servicio = isset($cita['servicios']['nombre']) ? $cita['servicios']['nombre'] : '';
    $cliente = isset($cita['clientes']['nombre']) ? $cita['clientes']['nombre'] : '';
    $eventos[] = [
        'id' => 'cita-' . $cita['id'],
        'title' => $servicio . ' - ' . $cliente,
        'start' => $start,
        'end' => $end,
        'color' => $cita['estado'] === 'cancelada' ? '#ef4444' : ($cita['estado'] === 'pendiente' ? '#f59e0b' : '#10b981')
    ];
}
// Bloqueos de horario desde Supabase
$bloqueos_resp = supabase_request("/rest/v1/bloqueos_horario?or=(empleado_id.is.null,empleado_id.in.(select id from empleados where empresa_id.eq.$empresa_id))&select=id,empleado_id,fecha_inicio,fecha_fin,motivo,tipo,empleados(nombre)");
$bloqueos = isset($bloqueos_resp['data']) ? $bloqueos_resp['data'] : [];
foreach ($bloqueos as $bloqueo) {
    $title = 'Bloqueo';
    if ($bloqueo['tipo'] === 'vacaciones') $title = 'Vacaciones';
    elseif ($bloqueo['tipo'] === 'dia_libre') $title = 'Día libre';
    elseif ($bloqueo['tipo'] === 'mantenimiento') $title = 'Mantenimiento';
    if ($bloqueo['motivo']) $title .= ': ' . $bloqueo['motivo'];
    if (isset($bloqueo['empleados']['nombre'])) $title .= ' (' . $bloqueo['empleados']['nombre'] . ')';
    $eventos[] = [
        'id' => 'bloqueo-' . $bloqueo['id'],
        'title' => $title,
        'start' => $bloqueo['fecha_inicio'],
        'end' => $bloqueo['fecha_fin'],
        'color' => '#ef4444',
        'textColor' => '#fff',
    ];
}
echo json_encode($eventos);
