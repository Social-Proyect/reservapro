<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/database.php';

$db = getDB();
$empresa_id = $_SESSION['empresa_id'] ?? null;
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT * FROM clientes WHERE empresa_id = ?";
$params = [$empresa_id];
if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR telefono LIKE ? OR email LIKE ?)";
    $like = "%$busqueda%";
    $params = [$empresa_id, $like, $like, $like, $like];
}
$sql .= " ORDER BY nombre, apellido";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$mensaje = '';

// Actualizar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cliente'])) {
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $stmt = $db->prepare("UPDATE clientes SET nombre=?, apellido=?, telefono=?, email=? WHERE id=? AND empresa_id=?");
    $stmt->execute([$nombre, $apellido, $telefono, $email, $id, $empresa_id]);
    $mensaje = 'Cliente actualizado correctamente.';
    $edit_id = null;
}

// Eliminar cliente
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM clientes WHERE id=? AND empresa_id=?");
    $stmt->execute([$id, $empresa_id]);
    $mensaje = 'Cliente eliminado.';
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Clientes</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="clientes.php" class="nav-link active">Clientes</a>
                <a href="calendario.php" class="nav-link">Calendario</a>
                <a href="logout.php" class="nav-link">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
            <h2>Listado de Clientes</h2>
            <form method="get" action="" style="margin-bottom:20px;">
                <input type="text" name="q" placeholder="Buscar por nombre, teléfono o email" value="<?= htmlspecialchars($busqueda) ?>" style="padding:8px;min-width:220px;">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
            <?php if ($mensaje): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                <?= $mensaje ?>
            </div>
            <?php endif; ?>
            <div class="card">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                            <th style="padding:8px;">Nombre</th>
                            <th style="padding:8px;">Teléfono</th>
                            <th style="padding:8px;">Email</th>
                            <th style="padding:8px;">Total Citas</th>
                            <th style="padding:8px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): ?>
                        <tr>
                            <?php if ($edit_id === (int)$c['id']): ?>
                            <form method="post" action="">
                                <td style="padding:8px;"><input type="text" name="nombre" value="<?= htmlspecialchars($c['nombre']) ?>" required style="width:90px;"></td>
                                <td style="padding:8px;"><input type="text" name="telefono" value="<?= htmlspecialchars($c['telefono']) ?>" required style="width:110px;"></td>
                                <td style="padding:8px;"><input type="email" name="email" value="<?= htmlspecialchars($c['email']) ?>" required style="width:140px;"></td>
                                <td style="padding:8px;">-</td>
                                <td style="padding:8px;">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="apellido" value="<?= htmlspecialchars($c['apellido']) ?>">
                                    <button type="submit" name="update_cliente" class="btn btn-success btn-sm">Guardar</button>
                                    <a href="clientes.php" class="btn btn-secondary btn-sm">Cancelar</a>
                                </td>
                            </form>
                            <?php else: ?>
                            <td style="padding:8px;"><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></td>
                            <td style="padding:8px;"><?= htmlspecialchars($c['telefono']) ?></td>
                            <td style="padding:8px;"><?= htmlspecialchars($c['email']) ?></td>
                            <td style="padding:8px;"><?= (int)$c['total_citas'] ?></td>
                            <td style="padding:8px;" class="action-btns">
                                <a href="clientes.php?edit=<?= $c['id'] ?>" class="btn btn-primary btn-sm" style="font-size:0.56rem;padding:3px 8px;min-width:0;">Editar</a>
                                <a href="clientes.php?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" style="font-size:0.56rem;padding:3px 8px;min-width:0;" onclick="return confirm('¿Eliminar este cliente?')">Eliminar</a>
                                <a href="clientes.php?historial=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:0.56rem;padding:3px 8px;min-width:0;">Historial</a>
                                <a href="clientes.php?resetpw=<?= $c['id'] ?>" class="btn btn-warning btn-sm" onclick="return confirm('¿Restablecer la contraseña de este cliente?')">Restablecer Contraseña</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clientes)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:12px;">No se encontraron clientes.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
            // Mostrar historial de citas si se solicita
            if (isset($_GET['historial'])) {
                $cid = (int)$_GET['historial'];
                $stmt = $db->prepare('SELECT * FROM citas WHERE cliente_id = ? ORDER BY fecha_hora DESC');
                $stmt->execute([$cid]);
                $citas_hist = $stmt->fetchAll();
                echo '<div class="card" style="margin-top:20px;"><h3>Historial de Citas</h3>';
                if ($citas_hist) {
                    echo '<table style="width:100%;margin-top:10px;"><thead><tr><th>Servicio</th><th>Fecha</th><th>Estado</th></tr></thead><tbody>';
                    foreach ($citas_hist as $ch) {
                        echo '<tr><td>' . htmlspecialchars($ch['servicio_id']) . '</td><td>' . htmlspecialchars($ch['fecha_hora']) . '</td><td>' . htmlspecialchars($ch['estado']) . '</td></tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<div class="alert alert-info">No hay citas para este cliente.</div>';
                }
                echo '</div>';
            }
            // Restablecer contraseña
            if (isset($_GET['resetpw'])) {
                $cid = (int)$_GET['resetpw'];
                $nueva = substr(bin2hex(random_bytes(4)),0,8);
                $hash = password_hash($nueva, PASSWORD_BCRYPT);
                $stmt = $db->prepare('UPDATE clientes SET password=? WHERE id=? AND empresa_id=?');
                $stmt->execute([$hash, $cid, $empresa_id]);
                echo '<div class="alert alert-success" style="margin-top:20px;">La nueva contraseña temporal es: <b>' . htmlspecialchars($nueva) . '</b></div>';
            }
            ?>
        </div>
    </main>
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> ReservaPro. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
