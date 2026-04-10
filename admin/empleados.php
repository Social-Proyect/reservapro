<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/supabase.php';

$db = getDB();
$empresa_id = $_SESSION['empresa_id'] ?? null;
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$mensaje = '';

// Actualizar empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_empleado'])) {
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $especialidad = trim($_POST['especialidad']);
    $descripcion = trim($_POST['descripcion']);
    $stmt = $db->prepare("UPDATE empleados SET nombre=?, apellido=?, telefono=?, email=?, especialidad=?, descripcion=? WHERE id=? AND empresa_id=?");
    $stmt->execute([$nombre, $apellido, $telefono, $email, $especialidad, $descripcion, $id, $empresa_id]);
    $mensaje = 'Empleado actualizado correctamente.';
    $edit_id = null;
}

// Eliminar empleado
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM empleados WHERE id=? AND empresa_id=?");
    $stmt->execute([$id, $empresa_id]);
    $mensaje = 'Empleado eliminado.';
}

// Agregar empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_empleado'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $especialidad = trim($_POST['especialidad']);
    $descripcion = trim($_POST['descripcion']);
    $stmt = $db->prepare("INSERT INTO empleados (empresa_id, nombre, apellido, telefono, email, especialidad, descripcion, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$empresa_id, $nombre, $apellido, $telefono, $email, $especialidad, $descripcion]);
    $mensaje = 'Empleado agregado correctamente.';
}

$sql = "SELECT * FROM empleados WHERE empresa_id = ?";
$params = [$empresa_id];
if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR telefono LIKE ? OR email LIKE ? OR especialidad LIKE ?)";
    $like = "%$busqueda%";
    $params = [$empresa_id, $like, $like, $like, $like, $like];
}
$sql .= " ORDER BY nombre, apellido";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$empleados = $stmt->fetchAll();

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .action-btns a { margin-right: 8px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Empleados</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="empleados.php" class="nav-link active">Empleados</a>
                <a href="clientes.php" class="nav-link">Clientes</a>
                <a href="calendario.php" class="nav-link">Calendario</a>
                <a href="logout.php" class="nav-link">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
            <h2>Listado de Empleados</h2>
            <?php if ($mensaje): ?>
                <div class="alert alert-info" style="margin-bottom:15px;"> <?= htmlspecialchars($mensaje) ?> </div>
            <?php endif; ?>
            <form method="get" action="" style="margin-bottom:20px;">
                <input type="text" name="q" placeholder="Buscar por nombre, teléfono, email o especialidad" value="<?= htmlspecialchars($busqueda) ?>" style="padding:8px;min-width:220px;">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
            <div class="card" style="margin-bottom:30px;">
                <form method="post" action="">
                    <h3>Agregar Nuevo Empleado</h3>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                        <input type="text" name="nombre" placeholder="Nombre" required style="padding:8px;">
                        <input type="text" name="apellido" placeholder="Apellido" required style="padding:8px;">
                        <input type="text" name="telefono" placeholder="Teléfono" required style="padding:8px;">
                        <input type="email" name="email" placeholder="Email" required style="padding:8px;">
                        <input type="text" name="especialidad" placeholder="Especialidad" style="padding:8px;">
                        <input type="text" name="descripcion" placeholder="Descripción" style="padding:8px;min-width:180px;">
                        <button type="submit" name="add_empleado" class="btn btn-success">Agregar</button>
                    </div>
                </form>
            </div>
            <div class="card">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                            <th style="padding:8px;">Nombre</th>
                            <th style="padding:8px;">Teléfono</th>
                            <th style="padding:8px;">Email</th>
                            <th style="padding:8px;">Especialidad</th>
                            <th style="padding:8px;">Descripción</th>
                            <th style="padding:8px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $e): ?>
                        <tr>
                        <?php if ($edit_id === (int)$e['id']): ?>
                        <form method="post" action="">
                            <td style="padding:8px;"><input type="text" name="nombre" value="<?= htmlspecialchars($e['nombre']) ?>" required style="width:90px;"></td>
                            <td style="padding:8px;"><input type="text" name="telefono" value="<?= htmlspecialchars($e['telefono']) ?>" required style="width:110px;"></td>
                            <td style="padding:8px;"><input type="email" name="email" value="<?= htmlspecialchars($e['email']) ?>" required style="width:140px;"></td>
                            <td style="padding:8px;"><input type="text" name="especialidad" value="<?= htmlspecialchars($e['especialidad']) ?>" style="width:90px;"></td>
                            <td style="padding:8px;"><input type="text" name="descripcion" value="<?= htmlspecialchars($e['descripcion']) ?>" style="width:120px;"></td>
                            <td style="padding:8px;">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                <input type="hidden" name="apellido" value="<?= htmlspecialchars($e['apellido']) ?>">
                                <button type="submit" name="update_empleado" class="btn btn-success btn-sm">Guardar</button>
                                <a href="empleados.php" class="btn btn-secondary btn-sm">Cancelar</a>
                            </td>
                        </form>
                        <?php else: ?>
                        <td style="padding:8px;"><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($e['telefono']) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($e['email']) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($e['especialidad']) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($e['descripcion']) ?></td>
                        <td style="padding:8px;" class="action-btns">
                            <a href="empleados.php?edit=<?= $e['id'] ?>" class="btn btn-primary btn-sm" style="font-size:0.56rem;padding:3px 8px;min-width:0;">Editar</a>
                            <a href="empleados.php?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm" style="font-size:0.56rem;padding:3px 8px;min-width:0;" onclick="return confirm('¿Eliminar este empleado?')">Eliminar</a>
                        </td>
                        <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($empleados)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:12px;">No se encontraron empleados.</td></tr>
                        <?php endif; ?>
                    </tbody>
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
