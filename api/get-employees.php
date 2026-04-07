<?php
header('Content-Type: application/json');
require_once '../config/supabase.php';

try {

    $servicio_id = $_GET['servicio_id'] ?? null;
    $empresa_id = $_GET['empresa_id'] ?? null;
    if (!$servicio_id || !$empresa_id) {
        throw new Exception('ID de servicio y empresa requeridos');
    }

    // Consulta a Supabase: empleados activos que pueden realizar el servicio y pertenecen a la empresa
    $query = "/rest/v1/empleados?activo=eq.1&empresa_id=eq.$empresa_id&select=id,nombre,apellido,foto,especialidad,descripcion,empleado_servicio(servicio_id)&empleado_servicio.servicio_id=eq.$servicio_id&order=nombre";
    $response = supabase_request($query);
    if (!isset($response['data'])) {
        throw new Exception('Error consultando Supabase');
    }
    // Filtrar empleados que tengan el servicio en la relación
    $empleados = array_filter($response['data'], function($e) use ($servicio_id) {
        if (!isset($e['empleado_servicio']) || !is_array($e['empleado_servicio'])) return false;
        foreach ($e['empleado_servicio'] as $rel) {
            if ($rel['servicio_id'] == $servicio_id) return true;
        }
        return false;
    });
    jsonResponse([
        'success' => true,
        'empleados' => array_values($empleados)
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
