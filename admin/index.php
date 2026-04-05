<?php
require_once 'auth.php';
require_once '../config/database.php';


$db = getDB();
if (!isset($_SESSION['empresa_id'])) {
    header('Location: admin-login.php');
    exit;
}
$empresa_id = $_SESSION['empresa_id'];

// Obtener estadísticas de hoy
$hoy = date('Y-m-d');
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_citas_hoy,
        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas_hoy,
        SUM(CASE WHEN estado = 'completada' THEN precio ELSE 0 END) as ingresos_hoy
    FROM citas 
    WHERE DATE(fecha_hora) = ? AND empresa_id = ?
");
$stmt->execute([$hoy, $empresa_id]);
$stats_hoy = $stmt->fetch();

// No-shows de ayer
$ayer = date('Y-m-d', strtotime('-1 day'));
$stmt = $db->prepare("
    SELECT COUNT(*) as no_shows_ayer
    FROM citas 
    WHERE DATE(fecha_hora) = ? AND estado = 'no_asistio' AND empresa_id = ?
");
$stmt->execute([$ayer, $empresa_id]);
$no_shows = $stmt->fetch();

// Tasa de ocupación de la semana
$inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fin_semana = date('Y-m-d', strtotime('sunday this week'));
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as citas_semana,
        (SELECT COUNT(*) * 8 * 5 FROM empleados WHERE activo = 1 AND empresa_id = ?) as slots_disponibles
    FROM citas 
    WHERE DATE(fecha_hora) BETWEEN ? AND ?
    AND estado IN ('confirmada', 'completada')
    AND empresa_id = ?
");
$stmt->execute([$empresa_id, $inicio_semana, $fin_semana, $empresa_id]);
$ocupacion = $stmt->fetch();
$tasa_ocupacion = $ocupacion['slots_disponibles'] > 0 
    ? round(($ocupacion['citas_semana'] / $ocupacion['slots_disponibles']) * 100, 1) 
    : 0;

// Empleado más solicitado
$stmt = $db->prepare("
    SELECT 
        CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
        COUNT(c.id) as total_citas
    FROM citas c
    INNER JOIN empleados e ON c.empleado_id = e.id
    WHERE c.fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND c.empresa_id = ?
      AND e.empresa_id = ?
    GROUP BY c.empleado_id
    ORDER BY total_citas DESC
    LIMIT 1
");
$stmt->execute([$empresa_id, $empresa_id]);
$empleado_top = $stmt->fetch();

// Servicio más vendido
$stmt = $db->prepare("
    SELECT 
        s.nombre as servicio_nombre,
        COUNT(c.id) as total_ventas
    FROM citas c
    INNER JOIN servicios s ON c.servicio_id = s.id
    WHERE c.fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND c.empresa_id = ?
      AND s.empresa_id = ?
    GROUP BY c.servicio_id
    ORDER BY total_ventas DESC
    LIMIT 1
");
$stmt->execute([$empresa_id, $empresa_id]);
$servicio_top = $stmt->fetch();

// Próximas citas de hoy
$stmt = $db->prepare("
        SELECT c.*, 
                     s.nombre as servicio_nombre,
                     CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
                     CONCAT(cl.nombre, ' ', IFNULL(cl.apellido, '')) as cliente_nombre,
                     cl.telefono as cliente_telefono
        FROM citas c
        INNER JOIN servicios s ON c.servicio_id = s.id
        LEFT JOIN empleados e ON c.empleado_id = e.id
        INNER JOIN clientes cl ON c.cliente_id = cl.id
        WHERE DATE(c.fecha_hora) = ? 
            AND c.empresa_id = ?
            AND c.fecha_hora >= NOW()
            AND c.estado IN ('pendiente', 'confirmada')
        ORDER BY c.fecha_hora
        LIMIT 5
");
$stmt->execute([$hoy, $empresa_id]);
$proximas_citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel de Administración</title>
        <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <?php include 'includes/topbar.php'; ?>
        
        <main class="main-content-admin">
            <div class="page-header">
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="location.href='calendario.php?accion=nueva'">
                        ➕ Agregar Cita Manual
                    </button>
                    <button class="btn btn-secondary" onclick="bloquearHorario()">
                        🚫 Bloquear Horario
                    </button>
                </div>
            </div>

            <!-- Resumen de Hoy -->
            <div class="stats-grid">
                <div class="stat-card card">
                    <div class="stat-icon" style="background-color: #dbeafe;">📅</div>
                    <div class="stat-content">
                        <h3>Citas de Hoy</h3>
                        <p class="stat-value"><?= $stats_hoy['total_citas_hoy'] ?></p>
                        <small class="stat-label"><?= $stats_hoy['confirmadas_hoy'] ?> confirmadas</small>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-icon" style="background-color: #d1fae5;">💰</div>
                    <div class="stat-content">
                        <h3>Ingresos Hoy</h3>
                        <p class="stat-value">$<?= number_format($stats_hoy['ingresos_hoy'], 2) ?></p>
                        <small class="stat-label">Proyectados</small>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-icon" style="background-color: #fee2e2;">❌</div>
                    <div class="stat-content">
                        <h3>No-Shows Ayer</h3>
                        <p class="stat-value"><?= $no_shows['no_shows_ayer'] ?></p>
                        <small class="stat-label">Ausencias</small>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-icon" style="background-color: #fef3c7;">📊</div>
                    <div class="stat-content">
                        <h3>Ocupación Semanal</h3>
                        <p class="stat-value"><?= $tasa_ocupacion ?>%</p>
                        <small class="stat-label">Esta semana</small>
                    </div>
                </div>
            </div>

            <!-- Métricas Clave -->
            <div class="metrics-grid">
                <div class="metric-card card">
                    <h3>👤 Empleado Más Solicitado</h3>
                    <div class="metric-value">
                        <?= $empleado_top ? htmlspecialchars($empleado_top['empleado_nombre']) : 'N/A' ?>
                    </div>
                    <small><?= $empleado_top ? $empleado_top['total_citas'] . ' citas en 30 días' : '' ?></small>
                </div>

                <div class="metric-card card">
                    <h3>⭐ Servicio Más Vendido</h3>
                    <div class="metric-value">
                        <?= $servicio_top ? htmlspecialchars($servicio_top['servicio_nombre']) : 'N/A' ?>
                    </div>
                    <small><?= $servicio_top ? $servicio_top['total_ventas'] . ' ventas en 30 días' : '' ?></small>
                </div>
            </div>

            <!-- Próximas Citas -->
            <div class="upcoming-appointments card">
                <h2>Próximas Citas de Hoy</h2>
                <?php if (!empty($proximas_citas)): ?>
                    <div class="appointments-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Cliente</th>
                                    <th>Servicio</th>
                                    <th>Empleado</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proximas_citas as $cita): ?>
                                <tr>
                                    <td><strong><?= date('H:i', strtotime($cita['fecha_hora'])) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($cita['cliente_nombre']) ?><br>
                                        <small><?= htmlspecialchars($cita['cliente_telefono']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($cita['servicio_nombre']) ?></td>
                                    <td><?= htmlspecialchars($cita['empleado_nombre']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $cita['estado'] ?>">
                                            <?= ucfirst($cita['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-icon" onclick="verCita(<?= $cita['id'] ?>)" title="Ver detalles">
                                            👁️
                                        </button>
                                        <button class="btn-icon" onclick="editarCita(<?= $cita['id'] ?>)" title="Editar">
                                            ✏️
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No hay más citas programadas para hoy</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
