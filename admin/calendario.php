<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/supabase.php';

$db = getDB();
$empresa_id = $_SESSION['empresa_id'] ?? null;

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Reservas - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="../assets/css/fullcalendar-admin.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Calendario de Reservas</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="calendario.php" class="nav-link active">Calendario</a>
                <a href="logout.php" class="nav-link">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
            <?php if (isset($_GET['bloquear']) && $_GET['bloquear'] == '1'): ?>
                <h2>Bloquear Horario</h2>
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bloquear_horario'])) {
                    $empleado = $_POST['empleado'] ?? null;
                    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
                    $hora_inicio = $_POST['hora_inicio'] ?? null;
                    $fecha_fin = $_POST['fecha_fin'] ?? null;
                    $hora_fin = $_POST['hora_fin'] ?? null;
                    $motivo = $_POST['motivo'] ?? '';
                    $tipo = $_POST['tipo'] ?? 'otro';
                    if ($fecha_inicio && $hora_inicio && $fecha_fin && $hora_fin) {
                        $dt_inicio = $fecha_inicio . ' ' . $hora_inicio;
                        $dt_fin = $fecha_fin . ' ' . $hora_fin;
                        $stmt = $db->prepare("INSERT INTO bloqueos_horario (empleado_id, fecha_inicio, fecha_fin, motivo, tipo) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $empleado ?: null,
                            $dt_inicio,
                            $dt_fin,
                            $motivo,
                            $tipo
                        ]);
                        echo '<div class="alert alert-success">Horario bloqueado correctamente.</div>';
                    } else {
                        echo '<div class="alert alert-danger">Todos los campos de fecha y hora son obligatorios.</div>';
                    }
                }
                $empleados = $db->query("SELECT id, nombre FROM empleados WHERE empresa_id = $empresa_id AND activo = 1")->fetchAll();
                ?>
                <form method="post" class="card" style="max-width:500px;margin:auto;">
                    <input type="hidden" name="bloquear_horario" value="1">
                    <div class="form-group">
                        <label>Empleado (opcional)</label>
                        <select name="empleado">
                            <option value="">Todos</option>
                            <?php foreach ($empleados as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" required>
                    </div>
                    <div class="form-group">
                        <label>Hora Inicio</label>
                        <input type="time" name="hora_inicio" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Fin</label>
                        <input type="date" name="fecha_fin" required>
                    </div>
                    <div class="form-group">
                        <label>Hora Fin</label>
                        <input type="time" name="hora_fin" required>
                    </div>
                    <div class="form-group">
                        <label>Motivo</label>
                        <input type="text" name="motivo" maxlength="255" placeholder="Motivo del bloqueo">
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo">
                            <option value="vacaciones">Vacaciones</option>
                            <option value="dia_libre">Día libre</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger">Bloquear Horario</button>
                </form>
                <div style="margin-top:30px;text-align:center;">
                    <a href="calendario.php" class="btn btn-secondary">Volver al Calendario</a>
                </div>
            <?php elseif (isset($_GET['accion']) && $_GET['accion'] === 'nueva'): ?>
                <h2>Agregar Cita Manual</h2>
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $cliente = $_POST['cliente'] ?? '';
                    $servicio = $_POST['servicio'] ?? '';
                    $empleado = $_POST['empleado'] ?? '';
                    $fecha = $_POST['fecha'] ?? '';
                    $hora = $_POST['hora'] ?? '';
                    $notas = $_POST['notas'] ?? '';
                    if ($cliente && $servicio && $empleado && $fecha && $hora) {
                        $stmt = $db->prepare("INSERT INTO citas (empresa_id, cliente_id, servicio_id, empleado_id, fecha, hora, notas, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmada')");
                        $stmt->execute([$empresa_id, $cliente, $servicio, $empleado, $fecha, $hora, $notas]);
                        echo '<div class="alert alert-success">Cita agregada correctamente.</div>';
                    } else {
                        echo '<div class="alert alert-danger">Todos los campos son obligatorios.</div>';
                    }
                }
                // Obtener clientes, servicios y empleados activos
                $clientes = $db->query("SELECT id, nombre FROM clientes WHERE empresa_id = $empresa_id")->fetchAll();
                $servicios = $db->query("SELECT id, nombre FROM servicios WHERE empresa_id = $empresa_id AND activo = 1")->fetchAll();
                $empleados = $db->query("SELECT id, nombre FROM empleados WHERE empresa_id = $empresa_id AND activo = 1")->fetchAll();
                ?>
                <form method="post" class="card" style="max-width:500px;margin:auto;">
                    <div class="form-group">
                        <label>Cliente</label>
                        <select name="cliente" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Servicio</label>
                        <select name="servicio" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Empleado</label>
                        <select name="empleado" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($empleados as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha" required>
                    </div>
                    <div class="form-group">
                        <label>Hora</label>
                        <input type="time" name="hora" required>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea name="notas" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar Cita</button>
                </form>
                <div style="margin-top:30px;text-align:center;">
                    <a href="calendario.php" class="btn btn-secondary">Volver al Calendario</a>
                </div>
            <?php else: ?>
                <h2>Próximas Reservas</h2>
                <p>Aquí podrás ver y gestionar las reservas de tu empresa en formato calendario.</p>
                <div class="calendar-placeholder" style="padding:40px;text-align:center;background:#f3f4f6;border-radius:12px;">
                    <em>Funcionalidad de calendario próximamente...</em>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> ReservaPro. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>

<!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.createElement('div');
        calendarEl.id = 'calendar-admin';
        document.querySelector('.container .calendar-placeholder').replaceWith(calendarEl);
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: '../api/admin-get-citas.php', // endpoint a crear para cargar citas
            eventClick: function(info) {
                alert('Reserva: ' + info.event.title + '\nFecha: ' + info.event.start.toLocaleString());
            }
        });
        calendar.render();
    });
    </script>
