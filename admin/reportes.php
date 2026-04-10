<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/supabase.php';


$db = getDB();
$empresa_id = $_SESSION['empresa_id'] ?? null;

// Rango de fechas
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Reporte de citas en rango
$stmt = $db->prepare("SELECT COUNT(*) as total_citas FROM citas WHERE empresa_id = ? AND DATE(fecha_hora) BETWEEN ? AND ?");
$stmt->execute([$empresa_id, $fecha_inicio, $fecha_fin]);
$total_citas = $stmt->fetchColumn();

// Reporte de ingresos en rango
$stmt = $db->prepare("SELECT COALESCE(SUM(precio),0) as total_ingresos FROM citas WHERE estado = 'completada' AND empresa_id = ? AND DATE(fecha_hora) BETWEEN ? AND ?");
$stmt->execute([$empresa_id, $fecha_inicio, $fecha_fin]);
$total_ingresos = $stmt->fetchColumn();

// Reporte de clientes nuevos en rango (si existe la columna fecha_alta)
$total_clientes = null;
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total_clientes FROM clientes WHERE empresa_id = ? AND DATE(fecha_hora) BETWEEN ? AND ?");
    $stmt->execute([$empresa_id, $fecha_inicio, $fecha_fin]);
    $total_clientes = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Si la columna no existe, mostrar el total de clientes sin filtro de fecha
    $stmt = $db->prepare("SELECT COUNT(*) as total_clientes FROM clientes WHERE empresa_id = ?");
    $stmt->execute([$empresa_id]);
    $total_clientes = $stmt->fetchColumn();
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Reportes</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="calendario.php" class="nav-link">Calendario</a>
                <a href="clientes.php" class="nav-link">Clientes</a>
                <a href="servicios.php" class="nav-link">Servicios</a>
                <a href="empleados.php" class="nav-link">Empleados</a>
                <a href="reportes.php" class="nav-link active">Reportes</a>
                <a href="logout.php" class="nav-link">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
            <h2>Resumen de la Empresa</h2>
            <form method="get" style="margin-bottom:20px;display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
                <div>
                    <label for="fecha_inicio">Desde:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div>
                    <label for="fecha_fin">Hasta:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
            <div class="card" style="max-width:400px;margin-bottom:30px;">
                <table style="width:100%;font-size:1.1em;">
                    <tr><th style="text-align:left;padding:8px;">Total de Citas</th><td style="padding:8px;"><?= $total_citas ?></td></tr>
                    <tr><th style="text-align:left;padding:8px;">Total de Ingresos</th><td style="padding:8px;">$<?= number_format($total_ingresos,2) ?></td></tr>
                    <tr><th style="text-align:left;padding:8px;">Clientes Nuevos</th><td style="padding:8px;"><?= $total_clientes ?></td></tr>
                </table>
            </div>
        </div>
    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> ReservaPro. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
