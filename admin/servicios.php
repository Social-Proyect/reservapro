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

// Actualizar servicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_servicio'])) {
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $duracion = (int)$_POST['duracion'];
    $icono = trim($_POST['icono']);
    // Procesar imagen subida
    if (!empty($_FILES['icono_img']['name'])) {
        $target_dir = '../assets/img/';
        $target_file = $target_dir . basename($_FILES['icono_img']['name']);
        if (move_uploaded_file($_FILES['icono_img']['tmp_name'], $target_file)) {
            $icono = 'assets/img/' . basename($_FILES['icono_img']['name']);
        }
    }
    $stmt = $db->prepare("UPDATE servicios SET nombre=?, descripcion=?, precio=?, duracion_minutos=?, icono=? WHERE id=? AND empresa_id=?");
    $stmt->execute([$nombre, $descripcion, $precio, $duracion, $icono, $id, $empresa_id]);
    $mensaje = 'Servicio actualizado correctamente.';
    $edit_id = null;
}

// Eliminar servicio
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM servicios WHERE id=? AND empresa_id=?");
    $stmt->execute([$id, $empresa_id]);
    $mensaje = 'Servicio eliminado.';
}

// Agregar servicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_servicio'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $duracion = (int)$_POST['duracion'];
    $icono = trim($_POST['icono']);
    // Procesar imagen subida
    if (!empty($_FILES['icono_img']['name'])) {
        $target_dir = '../assets/img/';
        $target_file = $target_dir . basename($_FILES['icono_img']['name']);
        if (move_uploaded_file($_FILES['icono_img']['tmp_name'], $target_file)) {
            $icono = 'assets/img/' . basename($_FILES['icono_img']['name']);
        }
    }
    $stmt = $db->prepare("INSERT INTO servicios (empresa_id, nombre, descripcion, precio, duracion_minutos, icono, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$empresa_id, $nombre, $descripcion, $precio, $duracion, $icono]);
    $mensaje = 'Servicio agregado correctamente.';
}

$sql = "SELECT * FROM servicios WHERE empresa_id = ?";
$params = [$empresa_id];
if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $like = "%$busqueda%";
    $params = [$empresa_id, $like, $like];
}
$sql .= " ORDER BY nombre";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$servicios = $stmt->fetchAll();

// Obtener moneda de la empresa
$stmt = $db->prepare('SELECT moneda FROM empresas WHERE id = ?');
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch();
$moneda = '';
if ($empresa && isset($empresa['moneda'])) {
    $simbolo = trim($empresa['moneda']);
    if ($simbolo !== '' && $simbolo !== '-' && strlen($simbolo) <= 5) {
        $moneda = $simbolo;
    }
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .action-btns a { margin-right: 8px; }
        .action-btns a.btn {
            font-size: 0.7rem;
            padding: 4px 10px;
            min-width: 0;
        }
        .btn.btn-success.btn-sm,
        .btn.btn-warning.btn-sm {
            font-size: 0.7rem;
            padding: 4px 10px;
            min-width: 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Servicios</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="servicios.php" class="nav-link active">Servicios</a>
                <a href="empleados.php" class="nav-link">Empleados</a>
                <a href="clientes.php" class="nav-link">Clientes</a>
                <a href="calendario.php" class="nav-link">Calendario</a>
                <a href="logout.php" class="nav-link">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
            <h2>Listado de Servicios</h2>
            <?php if ($mensaje): ?>
                <div class="alert alert-info" style="margin-bottom:15px;"> <?= htmlspecialchars($mensaje) ?> </div>
            <?php endif; ?>
            <form method="get" action="" style="margin-bottom:20px;">
                <input type="text" name="q" placeholder="Buscar por nombre o descripción" value="<?= htmlspecialchars($busqueda) ?>" style="padding:8px;min-width:220px;">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
            <div class="card" style="margin-bottom:30px;">
                <form method="post" action="" enctype="multipart/form-data">
                    <h3>Agregar Nuevo Servicio</h3>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                        <input type="text" name="nombre" placeholder="Nombre" required style="padding:8px;">
                        <input type="text" name="descripcion" placeholder="Descripción" style="padding:8px;min-width:180px;">
                        <input type="number" name="precio" placeholder="Precio" step="0.01" min="0" required style="padding:8px;width:90px;">
                        <input type="number" name="duracion" placeholder="Duración (min)" min="1" required style="padding:8px;width:90px;">
                        <input type="text" name="icono" placeholder="Icono (emoji)" style="padding:8px;width:60px;">
                        <input type="file" name="icono_img" accept="image/*" style="margin-left:10px;">
                        <button type="submit" name="add_servicio" class="btn btn-success">Agregar</button>
                    </div>
                </form>
            </div>
            <div class="card">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                            <th style="padding:8px;">Nombre</th>
                            <th style="padding:8px;">Descripción</th>
                            <th style="padding:8px;">Precio</th>
                            <th style="padding:8px;">Duración</th>
                            <th style="padding:8px;">Icono</th>
                            <th style="padding:8px;">Activo</th>
                            <th style="padding:8px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicios as $s): ?>
                        <tr>
                        <?php if ($edit_id === (int)$s['id']): ?>
                        <form method="post" action="">
                            <td style="padding:8px;"><input type="text" name="nombre" value="<?= htmlspecialchars($s['nombre']) ?>" required style="width:90px;"></td>
                            <td style="padding:8px;"><input type="text" name="descripcion" value="<?= htmlspecialchars($s['descripcion']) ?>" style="width:120px;"></td>
                            <td style="padding:8px;"><input type="number" name="precio" value="<?= htmlspecialchars($s['precio']) ?>" step="0.01" min="0" required style="width:70px;"></td>
                            <td style="padding:8px;"><input type="number" name="duracion" value="<?= (int)$s['duracion_minutos'] ?>" min="1" required style="width:60px;"></td>
                            <td style="padding:8px;">
                                <input type="text" name="icono" value="<?= htmlspecialchars($s['icono']) ?>" style="width:40px;">
                                <input type="file" name="icono_img" accept="image/*" style="margin-left:10px;">
                            </td>
                            <td style="padding:8px;">-</td>
                            <td style="padding:8px;">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" name="update_servicio" class="btn btn-success btn-sm">Guardar</button>
                                <a href="servicios.php" class="btn btn-secondary btn-sm">Cancelar</a>
                            </td>
                        </form>
                        <?php else: ?>
                        <td style="padding:8px;"><?= htmlspecialchars($s['nombre']) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($s['descripcion']) ?></td>
                        <td style="padding:8px; white-space:nowrap;"><span><?php if ($moneda !== '') { echo htmlspecialchars($moneda) . ' '; } ?></span><span><?= number_format($s['precio'],2) ?></span></td>
                        <td style="padding:8px;"><?= (int)$s['duracion_minutos'] ?> min</td>
                        <td style="padding:8px; font-size:1.5rem; text-align:center;"> <?= htmlspecialchars($s['icono']) ?> </td>
                        <td style="padding:8px; text-align:center;">
                            <?php if ($s['activo']): ?>
                                <a href="servicios.php?toggle=<?= $s['id'] ?>" class="btn btn-success btn-sm">Activo</a>
                            <?php else: ?>
                                <a href="servicios.php?toggle=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Inactivo</a>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px;" class="action-btns">
                            <a href="servicios.php?edit=<?= $s['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                            <a href="servicios.php?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este servicio?')">Eliminar</a>
                            <a href="servicios.php?empleados=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Asignar Empleados</a>
                        </td>
                        <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($servicios)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:12px;">No se encontraron servicios.</td></tr>
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

<?php
// Activar/desactivar servicio
if (isset($_GET['toggle'])) {
    $sid = (int)$_GET['toggle'];
    $stmt = $db->prepare('UPDATE servicios SET activo = IF(activo=1,0,1) WHERE id=? AND empresa_id=?');
    $stmt->execute([$sid, $empresa_id]);
    header('Location: servicios.php');
    exit;
}
// Asignar empleados a servicio
if (isset($_GET['empleados'])) {
    $sid = (int)$_GET['empleados'];
    // Obtener empleados
    $stmt = $db->prepare('SELECT * FROM empleados WHERE empresa_id=? ORDER BY nombre, apellido');
    $stmt->execute([$empresa_id]);
    $empleados = $stmt->fetchAll();
    // Obtener asignaciones actuales
    $stmt = $db->prepare('SELECT empleado_id FROM empleado_servicio WHERE servicio_id=?');
    $stmt->execute([$sid]);
    $asignados = array_column($stmt->fetchAll(), 'empleado_id');
    echo '<div class="card" style="margin-top:20px;"><h3>Asignar Empleados a Servicio</h3>';
    echo '<form method="post" action=""><input type="hidden" name="sid" value="'.$sid.'">';
    foreach ($empleados as $e) {
        $checked = in_array($e['id'], $asignados) ? 'checked' : '';
        echo '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="empleados[]" value="'.$e['id'].'" '.$checked.'> '.htmlspecialchars($e['nombre'].' '.$e['apellido']).'</label>';
    }
    echo '<button type="submit" name="save_empleados" class="btn btn-primary" style="margin-top:10px;">Guardar</button>';
    echo ' <a href="servicios.php" class="btn btn-secondary">Cancelar</a>';
    echo '</form></div>';
}
if (isset($_POST['save_empleados'])) {
    $sid = (int)$_POST['sid'];
    $empleados = isset($_POST['empleados']) ? $_POST['empleados'] : [];
    $db->prepare('DELETE FROM empleado_servicio WHERE servicio_id=?')->execute([$sid]);
    foreach ($empleados as $eid) {
        $db->prepare('INSERT INTO empleado_servicio (servicio_id, empleado_id) VALUES (?, ?)')->execute([$sid, $eid]);
    }
    echo '<div class="alert alert-success" style="margin-top:20px;">Asignaciones actualizadas.</div>';
}
?>
