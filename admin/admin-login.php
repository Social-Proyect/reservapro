<?php
require_once '../config/supabase.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($empresa_id > 0 && !empty($username) && !empty($password)) {
        try {
            $db = getDB();
            // Buscar empresa por ID
            $stmt = $db->prepare("SELECT id FROM empresas WHERE id = ? LIMIT 1");
            $stmt->execute([$empresa_id]);
            $empresaRow = $stmt->fetch();
            if (!$empresaRow) {
                $error = 'Empresa no encontrada';
            } else {
                $stmt = $db->prepare("SELECT * FROM usuarios WHERE empresa_id = ? AND username = ? AND activo = 1");
                $stmt->execute([$empresa_id, $username]);
                $user = $stmt->fetch();
                // DEBUG TEMPORAL
                echo '<pre style="background:#222;color:#fff;padding:16px;">';
                echo "POST empresa_id: ".$empresa_id."\n";
                echo "POST username: ".$username."\n";
                echo "POST password: ".$password."\n";
                echo "DB usuario encontrado: ".print_r($user, true)."\n";
                if ($user) {
                    echo "Hash en DB: ".$user['password']."\n";
                    echo "password_verify: ".(password_verify($password, $user['password']) ? 'OK' : 'FAIL')."\n";
                }
                echo '</pre>';
                // FIN DEBUG
                $login_ok = false;
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        $login_ok = true;
                    } elseif ($password === $user['password']) { // Permitir texto plano solo para pruebas
                        $login_ok = true;
                    }
                }
                if ($login_ok) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_nombre'] = $user['nombre'];
                    $_SESSION['admin_rol'] = $user['rol'];
                    $_SESSION['empresa_id'] = $empresa_id;
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            }
        } catch (Exception $e) {
            $error = 'Error al conectar con la base de datos: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Panel de Administración</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
        <link rel="icon" type="image/png" href="../assets/img/favicon-reserva.png">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .login-box {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
        }
        
        .login-box h1 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--dark);
        }
        
        .login-logo {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">📋</div>
            <h1>ReservaPro Admin</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="background: #fee; color: #c33; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
                <form class="login-form" method="post" autocomplete="off">
                    <h2>Iniciar Sesión</h2>
                    <?php if ($error): ?>
                        <div class="error-message"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="empresa_id">ID de Empresa</label>
                        <input type="number" id="empresa_id" name="empresa_id" required placeholder="ID numérico de la empresa">
                    </div>
                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" required placeholder="Usuario">
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required placeholder="Contraseña">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                </form>
                <p class="text-center text-muted mt-20" style="margin-top:18px;">
                    <a href="register-admin.php">¿No tienes cuenta? Regístrate como administrador</a>
                </p>
        </div>
    </div>
</body>
</html>
