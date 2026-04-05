<?php
session_start();
require_once 'config/database.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM configuracion LIMIT 1");
$config = $stmt->fetch();

$citas = [];
$mensaje = '';
$cliente_autenticado = isset($_SESSION['cliente_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $telefono = sanitize($_POST['telefono']);
    $password = $_POST['password'];
    $stmt = $db->prepare('SELECT * FROM clientes WHERE telefono = ? LIMIT 1');
    $stmt->execute([$telefono]);
    $cliente = $stmt->fetch();
    if ($cliente && !empty($cliente['password']) && password_verify($password, $cliente['password'])) {
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_telefono'] = $cliente['telefono'];
        $cliente_autenticado = true;
    } else {
        $mensaje = 'Teléfono o contraseña incorrectos';
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: mis-citas.php');
    exit;
}

if ($cliente_autenticado) {
    $cliente_id = $_SESSION['cliente_id'];
    $stmt = $db->prepare('SELECT * FROM citas WHERE cliente_id = ? ORDER BY fecha_hora DESC');
    $stmt->execute([$cliente_id]);
    $citas = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - <?= htmlspecialchars($config['nombre_negocio'] ?? 'ReservaPro') ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/mis-citas.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <?php if (!empty($config['logo'])): ?>
                        <img src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo" class="logo">
                    <?php else: ?>
                        <h1 class="logo-text"><?= htmlspecialchars($config['nombre_negocio'] ?? 'ReservaPro') ?></h1>
                    <?php endif; ?>
                </div>
                <nav class="nav">
                    <a href="index.php" class="nav-link">Reservar Cita</a>
                    <a href="mis-citas.php" class="nav-link active">Mis Citas</a>
                </nav>
            </div>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
            <h1 class="page-title">Consulta tus Citas</h1>
            <?php if (!$cliente_autenticado): ?>
                <div class="search-box card">
                    <h2>Iniciar Sesión</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" required placeholder="Ej: 123-456-7890">
                        </div>
                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" required placeholder="Tu contraseña">
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">Entrar</button>
                    </form>
                    <?php if ($mensaje): ?>
                        <div class="alert alert-warning mt-20"><?= htmlspecialchars($mensaje) ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="" style="text-align:right; margin-bottom:10px;">
                    <button type="submit" name="logout" class="btn btn-secondary">Cerrar Sesión</button>
                </form>
                <?php if (!empty($citas)): ?>
                    <div class="appointments-list">
                        <?php foreach ($citas as $cita): ?>
                            <div class="appointment-card card">
                                <div class="appointment-header">
                                    <div>
                                        <h3><?= htmlspecialchars($cita['servicio_id']) ?></h3>
                                        <span class="status-badge status-<?= $cita['estado'] ?>">
                                            <?= ucfirst($cita['estado']) ?>
                                        </span>
                                    </div>
                                    <div class="appointment-code">
                                        <small>Código:</small>
                                        <strong><?= htmlspecialchars($cita['codigo_confirmacion']) ?></strong>
                                    </div>
                                </div>
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <span class="icon">📅</span>
                                        <div>
                                            <strong>Fecha y Hora</strong>
                                            <p><?= formatearFecha($cita['fecha_hora'], 'l, d/m/Y') ?></p>
                                            <p><?= formatearFecha($cita['fecha_hora'], 'H:i') ?> hrs</p>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <span class="icon">⏱️</span>
                                        <div>
                                            <strong>Duración</strong>
                                            <p><?= $cita['duracion_minutos'] ?> minutos</p>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <span class="icon">💰</span>
                                        <div>
                                            <strong>Precio</strong>
                                            <p><?php if (!empty($config['moneda'])) { echo htmlspecialchars($config['moneda']) . ' '; } ?><?= number_format($cita['precio'], 2) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($cita['notas_cliente']): ?>
                                    <div class="appointment-notes">
                                        <strong>Notas:</strong>
                                        <p><?= nl2br(htmlspecialchars($cita['notas_cliente'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-20">No tienes citas registradas.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($config['nombre_negocio'] ?? 'ReservaPro') ?>. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
