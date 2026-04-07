<?php
header('Content-Type: application/json');

require_once '../config/supabase.php';
// Copia de funciones necesarias
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
function generarCodigoConfirmacion() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }



    // Validar datos requeridos
    $required_fields = ['empresa_id', 'nombre', 'telefono', 'email', 'servicio_id', 'fecha', 'hora', 'duracion', 'precio', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Campo requerido: {$field}");
        }
    }

    // Sanitizar datos
    $empresa_id = (int)$_POST['empresa_id'];
    $nombre_completo = sanitize($_POST['nombre']);
    $telefono = sanitize($_POST['telefono']);
    $email = sanitize($_POST['email']);
    $notas = sanitize($_POST['notas'] ?? '');
    $servicio_id = (int)$_POST['servicio_id'];
    $empleado_id = isset($_POST['empleado_id']) && $_POST['empleado_id'] != 0 ? (int)$_POST['empleado_id'] : null;
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $duracion = (int)$_POST['duracion'];
    $precio = (float)$_POST['precio'];
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    if (!$password) {
        throw new Exception('Campo requerido: password');
    }
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Separar nombre y apellido
    $partes_nombre = explode(' ', $nombre_completo, 2);
    $nombre = $partes_nombre[0];
    $apellido = isset($partes_nombre[1]) ? $partes_nombre[1] : '';

    // Si no se especificó empleado, asignar el primero disponible (lógica simplificada para Supabase)
    if (!$empleado_id) {
        $fecha_hora = $fecha . ' ' . $hora;
        // Buscar empleados activos para el servicio y empresa
        $query = "/rest/v1/empleado_servicio?servicio_id=eq.$servicio_id&select=empleado_id,empleados(id,nombre,empresa_id,activo)&empleados.activo=eq.1&empleados.empresa_id=eq.$empresa_id";
        $response = supabase_request($query);
        if (!isset($response['data']) || count($response['data']) == 0) {
            throw new Exception('No hay empleados disponibles para este servicio');
        }
        // Seleccionar el primero (no se verifica solapamiento de citas aquí, requiere lógica extra)
        $empleado_id = $response['data'][0]['empleado_id'];
    }

    // Verificar disponibilidad final (simplificado, requiere lógica avanzada para solapamiento)
    $fecha_hora = $fecha . ' ' . $hora;
    $query = "/rest/v1/citas?empleado_id=eq.$empleado_id&empresa_id=eq.$empresa_id&estado=in.(pendiente,confirmada)&fecha_hora=eq.$fecha_hora";
    $ocupado = supabase_request($query);
    if (isset($ocupado['data']) && count($ocupado['data']) > 0) {
        throw new Exception('Este horario ya no está disponible');
    }

    // Buscar o crear cliente en Supabase
    $cliente_id = null;
    $query = "/rest/v1/clientes?or=(telefono.eq.$telefono,email.eq.$email)&empresa_id=eq.$empresa_id";
    $cliente_resp = supabase_request($query);
    if (isset($cliente_resp['data']) && count($cliente_resp['data']) > 0) {
        $cliente = $cliente_resp['data'][0];
        $cliente_id = $cliente['id'];
        // Si el cliente no tiene password, actualizarlo
        if (empty($cliente['password'])) {
            supabase_request("/rest/v1/clientes?id=eq.$cliente_id", 'PATCH', [ 'password' => $password_hash ]);
        }
        // Actualizar información del cliente
        supabase_request("/rest/v1/clientes?id=eq.$cliente_id", 'PATCH', [ 'nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'total_citas' => ($cliente['total_citas'] ?? 0) + 1 ]);
    } else {
        // Crear nuevo cliente
        $cliente_data = [
            'empresa_id' => $empresa_id,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'email' => $email,
            'password' => $password_hash,
            'total_citas' => 1
        ];
        $insert_cliente = supabase_request("/rest/v1/clientes", 'POST', $cliente_data);
        if (!isset($insert_cliente['data'][0]['id'])) {
            throw new Exception('No se pudo crear el cliente');
        }
        $cliente_id = $insert_cliente['data'][0]['id'];
    }

    // Generar código de confirmación único
    $codigo_confirmacion = generarCodigoConfirmacion();
    // Verificar unicidad en Supabase
    $intentos = 0;
    do {
        $check = supabase_request("/rest/v1/citas?codigo_confirmacion=eq.$codigo_confirmacion");
        if (!isset($check['data']) || count($check['data']) == 0) break;
        $codigo_confirmacion = generarCodigoConfirmacion();
        $intentos++;
    } while ($intentos < 5);

    // Crear la cita en Supabase
    $cita_data = [
        'empresa_id' => $empresa_id,
        'cliente_id' => $cliente_id,
        'servicio_id' => $servicio_id,
        'empleado_id' => $empleado_id,
        'fecha_hora' => $fecha_hora,
        'duracion_minutos' => $duracion,
        'precio' => $precio,
        'estado' => 'confirmada',
        'notas_cliente' => $notas,
        'codigo_confirmacion' => $codigo_confirmacion
    ];
    $insert_cita = supabase_request("/rest/v1/citas", 'POST', $cita_data);
    if (!isset($insert_cita['data'][0]['id'])) {
        throw new Exception('No se pudo crear la cita');
    }
    $cita_id = $insert_cita['data'][0]['id'];

    // TODO: Enviar correo de confirmación
    // TODO: Enviar SMS/WhatsApp de confirmación

    jsonResponse([
        'success' => true,
        'message' => 'Reserva creada exitosamente',
        'cita_id' => $cita_id,
        'codigo_confirmacion' => $codigo_confirmacion
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
