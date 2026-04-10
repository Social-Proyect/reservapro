<?php
require_once '../config/supabase.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($nombre_empresa && $nombre && $username && $password && $password === $password2) {
        try {
            $db = getDB();
            // Crear empresa
            $stmt = $db->prepare('INSERT INTO empresas (nombre) VALUES (?)');
            $stmt->execute([$nombre_empresa]);
            $empresa_id = $db->lastInsertId();
            // Crear usuario admin
            $stmt = $db->prepare('SELECT id FROM usuarios WHERE empresa_id = ? AND username = ?');
            $stmt->execute([$empresa_id, $username]);
            if ($stmt->fetch()) {
                $error = 'El usuario ya existe para esta empresa.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare('INSERT INTO usuarios (empresa_id, nombre, username, password, rol, activo) VALUES (?, ?, ?, ?, ?, 1)');
                $stmt->execute([$empresa_id, $nombre, $username, $hash, 'admin']);
                $success = 'Usuario administrador y empresa registrados correctamente. El ID de tu empresa es: <b>' . htmlspecialchars($empresa_id) . '</b>. Guárdalo para iniciar sesión.';
            }
        } catch (Exception $e) {
            $error = 'Error al registrar: ' . $e->getMessage();
        }
    } else {
        $error = 'Completa todos los campos y asegúrate que las contraseñas coincidan.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Administrador</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .register-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .register-box { background: var(--white); border-radius: var(--border-radius); padding: 40px; width: 100%; max-width: 400px; box-shadow: var(--shadow-lg); }
        .register-box h1 { text-align: center; margin-bottom: 30px; color: var(--dark); }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h1>Registrar Administrador</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: #fee; color: #c33; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" style="background: #efe; color: #080; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="form-group">
                    <label for="nombre_empresa">Nombre de la Empresa</label>
                    <input type="text" id="nombre_empresa" name="nombre_empresa" required placeholder="Nombre de la empresa">
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Nombre completo">
                </div>
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required placeholder="Usuario">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="Contraseña">
                </div>
                <div class="form-group">
                    <label for="password2">Repetir Contraseña</label>
                    <input type="password" id="password2" name="password2" required placeholder="Repite la contraseña">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Registrar</button>
            </form>
            <p class="text-center text-muted mt-20"><a href="admin-login.php">Volver al login</a></p>
        </div>
    </div>
</body>
</html>
