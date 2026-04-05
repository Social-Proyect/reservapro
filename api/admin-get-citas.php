<?php
// Devuelve las citas en formato FullCalendar para el admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}
require_once '../config/database.php';
header('Content-Type: application/json');

$db = getDB();
$empresa_id = $_SESSION['empresa_id'] ?? null;

$stmt = $db->prepare('SELECT c.id, c.fecha_hora, c.duracion_minutos, c.estado, s.nombre as servicio, cl.nombre as cliente
    FROM citas c
    INNER JOIN servicios s ON c.servicio_id = s.id
    INNER JOIN clientes cl ON c.cliente_id = cl.id
    WHERE c.empresa_id = ?');
$stmt->execute([$empresa_id]);
$citas = $stmt->fetchAll();


$eventos = [];
foreach ($citas as $cita) {
    $start = $cita['fecha_hora'];
    $end = date('Y-m-d H:i:s', strtotime($start . ' + ' . $cita['duracion_minutos'] . ' minutes'));
    $eventos[] = [
        'id' => 'cita-' . $cita['id'],
        'title' => $cita['servicio'] . ' - ' . $cita['cliente'],
        'start' => $start,
        'end' => $end,
        'color' => $cita['estado'] === 'cancelada' ? '#ef4444' : ($cita['estado'] === 'pendiente' ? '#f59e0b' : '#10b981')
    ];
}

// Bloqueos de horario
$stmt = $db->prepare('SELECT b.id, b.empleado_id, b.fecha_inicio, b.fecha_fin, b.motivo, b.tipo, e.nombre as empleado
    FROM bloqueos_horario b
    LEFT JOIN empleados e ON b.empleado_id = e.id
    WHERE (b.empleado_id IS NULL OR e.empresa_id = ?)');
$stmt->execute([$empresa_id]);
$bloqueos = $stmt->fetchAll();
foreach ($bloqueos as $bloqueo) {
    $title = 'Bloqueo';
    if ($bloqueo['tipo'] === 'vacaciones') $title = 'Vacaciones';
    elseif ($bloqueo['tipo'] === 'dia_libre') $title = 'Día libre';
    elseif ($bloqueo['tipo'] === 'mantenimiento') $title = 'Mantenimiento';
    if ($bloqueo['motivo']) $title .= ': ' . $bloqueo['motivo'];
    if ($bloqueo['empleado']) $title .= ' (' . $bloqueo['empleado'] . ')';
    $eventos[] = [
        'id' => 'bloqueo-' . $bloqueo['id'],
        'title' => $title,
        'start' => $bloqueo['fecha_inicio'],
        'end' => $bloqueo['fecha_fin'],
        'color' => '#ef4444', // rojo
        'textColor' => '#fff',
    ];
}
echo json_encode($eventos);
