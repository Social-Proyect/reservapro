<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['empresa_id'])) {
    header('Location: admin-login.php');
    exit;
}
require_once '../config/supabase.php';
$db = getDB();
$empresa_id = $_SESSION['empresa_id'];

// Obtener datos actuales
$stmt = $db->prepare('SELECT * FROM empresas WHERE id = ?');
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch();
if (!$empresa) {
    echo '<h2>Empresa no encontrada.</h2>';
    exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $moneda = trim($_POST['moneda'] ?? '');
    $logo = $empresa['logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['logo']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $dest = '../assets/img/logo_empresa_' . $empresa_id . '.' . $ext;
            if (move_uploaded_file($tmp, $dest)) {
                $logo = 'assets/img/logo_empresa_' . $empresa_id . '.' . $ext;
            }
        }
    }
    if ($nombre && $email && $moneda) {
        $stmt = $db->prepare('UPDATE empresas SET nombre=?, email=?, telefono=?, direccion=?, moneda=?, logo=? WHERE id=?');
        $stmt->execute([$nombre, $email, $telefono, $direccion, $moneda, $logo, $empresa_id]);
        $mensaje = 'Datos de la empresa actualizados correctamente.';
        // Refrescar datos
        $stmt = $db->prepare('SELECT * FROM empresas WHERE id = ?');
        $stmt->execute([$empresa_id]);
        $empresa = $stmt->fetch();
    } else {
        $mensaje = 'Por favor completa los campos obligatorios.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
        <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <style>
        .edit-empresa-container { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 32px; }
        .edit-empresa-container h1 { text-align: center; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; }
        input, textarea { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        .btn { width: 100%; }
    </style>
</head>
<body>
    <div class="edit-empresa-container">
        <h1>Editar Datos de la Empresa</h1>
        <?php if ($mensaje): ?>
            <div class="alert alert-info" style="margin-bottom:18px;"> <?= htmlspecialchars($mensaje) ?> </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>ID de Empresa</label>
                <input type="text" value="<?= htmlspecialchars($empresa['id']) ?>" disabled>
            </div>
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($empresa['nombre']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($empresa['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($empresa['telefono']) ?>">
            </div>
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <textarea id="direccion" name="direccion" rows="2"><?= htmlspecialchars($empresa['direccion']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="moneda">Moneda *</label>
                <input type="text" id="moneda" name="moneda" value="<?= htmlspecialchars($empresa['moneda']) ?>" required maxlength="10">
            </div>
            <div class="form-group">
                <label for="logo">Logo de la Empresa</label>
                <?php if (!empty($empresa['logo'])): ?>
                    <div style="margin-bottom:8px;"><img src="../<?= htmlspecialchars($empresa['logo']) ?>" alt="Logo actual" style="max-width:120px;max-height:80px;"></div>
                <?php endif; ?>
                <input type="file" id="logo" name="logo" accept="image/*">
                <small>Puedes subir JPG, PNG, GIF o WEBP. Si subes una nueva imagen, reemplazará la anterior.</small>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
        <div style="text-align:center;margin-top:18px;">
            <a href="index.php" class="btn btn-secondary">Volver al Panel</a>
        </div>
    </div>
</body>
</html>
