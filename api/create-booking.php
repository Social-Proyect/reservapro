<?php
header('Content-Type: application/json');

require_once '../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $db = getDB();
    $db->beginTransaction();

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

    // Si no se especificó empleado, asignar el primero disponible
    if (!$empleado_id) {
        $fecha_hora = $fecha . ' ' . $hora;
        $stmt = $db->prepare("
            SELECT es.empleado_id
            FROM empleado_servicio es
            INNER JOIN empleados e ON es.empleado_id = e.id
            WHERE es.servicio_id = ? AND e.activo = 1 AND e.empresa_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM citas c
                WHERE c.empleado_id = es.empleado_id AND c.empresa_id = ?
                AND c.estado IN ('pendiente', 'confirmada')
                AND (
                    (c.fecha_hora < ? AND DATE_ADD(c.fecha_hora, INTERVAL c.duracion_minutos MINUTE) > ?)
                    OR (c.fecha_hora >= ? AND c.fecha_hora < DATE_ADD(?, INTERVAL ? MINUTE))
                )
            )
            LIMIT 1
        ");
        $stmt->execute([$servicio_id, $empresa_id, $empresa_id, $fecha_hora, $fecha_hora, $fecha_hora, $fecha_hora, $duracion]);
        $empleado_result = $stmt->fetch();
        if ($empleado_result) {
            $empleado_id = $empleado_result['empleado_id'];
        } else {
            throw new Exception('No hay empleados disponibles en ese horario');
        }
    }

    // Verificar disponibilidad final
    $fecha_hora = $fecha . ' ' . $hora;
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM citas 
        WHERE empleado_id = ? AND empresa_id = ?
        AND estado IN ('pendiente', 'confirmada')
        AND (
            (fecha_hora < ? AND DATE_ADD(fecha_hora, INTERVAL duracion_minutos MINUTE) > ?)
            OR (fecha_hora >= ? AND fecha_hora < DATE_ADD(?, INTERVAL ? MINUTE))
        )
    ");
    $stmt->execute([$empleado_id, $empresa_id, $fecha_hora, $fecha_hora, $fecha_hora, $fecha_hora, $duracion]);
    $ocupado = $stmt->fetch();
    if ($ocupado['count'] > 0) {
        throw new Exception('Este horario ya no está disponible');
    }

    // Buscar o crear cliente
    $stmt = $db->prepare("SELECT id, password FROM clientes WHERE (telefono = ? OR email = ?) AND empresa_id = ?");
    $stmt->execute([$telefono, $email, $empresa_id]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        $cliente_id = $cliente['id'];
        // Si el cliente no tiene password, actualizarlo
        if (empty($cliente['password'])) {
            $stmt = $db->prepare("UPDATE clientes SET password = ? WHERE id = ?");
            $stmt->execute([$password_hash, $cliente_id]);
        }
        // Actualizar información del cliente
        $stmt = $db->prepare("
            UPDATE clientes 
            SET nombre = ?, apellido = ?, email = ?, total_citas = total_citas + 1
            WHERE id = ? AND empresa_id = ?
        ");
        $stmt->execute([$nombre, $apellido, $email, $cliente_id, $empresa_id]);
    } else {
        // Crear nuevo cliente con password
        $stmt = $db->prepare("
            INSERT INTO clientes (empresa_id, nombre, apellido, telefono, email, password, total_citas)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$empresa_id, $nombre, $apellido, $telefono, $email, $password_hash]);
        $cliente_id = $db->lastInsertId();
    }

    // Generar código de confirmación único
    $codigo_confirmacion = generarCodigoConfirmacion();
    while (true) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM citas WHERE codigo_confirmacion = ?");
        $stmt->execute([$codigo_confirmacion]);
        if ($stmt->fetch()['count'] == 0) break;
        $codigo_confirmacion = generarCodigoConfirmacion();
    }

    // Crear la cita
    $stmt = $db->prepare("
        INSERT INTO citas (
            empresa_id, cliente_id, servicio_id, empleado_id, fecha_hora, 
            duracion_minutos, precio, estado, notas_cliente, codigo_confirmacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmada', ?, ?)
    ");
    $stmt->execute([
        $empresa_id,
        $cliente_id,
        $servicio_id,
        $empleado_id,
        $fecha_hora,
        $duracion,
        $precio,
        $notas,
        $codigo_confirmacion
    ]);

    $cita_id = $db->lastInsertId();

    $db->commit();

    // TODO: Enviar correo de confirmación
    // TODO: Enviar SMS/WhatsApp de confirmación

    jsonResponse([
        'success' => true,
        'message' => 'Reserva creada exitosamente',
        'cita_id' => $cita_id,
        'codigo_confirmacion' => $codigo_confirmacion
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
