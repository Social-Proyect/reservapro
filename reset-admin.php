<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Resetear Usuario Admin</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Resetear Usuario Admin</h1>";

try {
    $db = getDB();
    
    // Verificar si existe el usuario admin
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<div class='info'>Usuario admin encontrado. Actualizando contraseña...</div>";
        
        // Actualizar la contraseña
        $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE usuarios SET password = ?, activo = 1 WHERE username = 'admin'");
        $stmt->execute([$newPassword]);
        
        echo "<div class='success'>✓ Contraseña actualizada correctamente</div>";
        
    } else {
        echo "<div class='info'>Usuario admin no existe. Creando...</div>";
        
        // Crear el usuario admin
        $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO usuarios (username, password, nombre, rol, activo) VALUES ('admin', ?, 'Administrador', 'admin', 1)");
        $stmt->execute([$newPassword]);
        
        echo "<div class='success'>✓ Usuario admin creado correctamente</div>";
    }
    
    // Mostrar información de acceso
    echo "<div class='success'>
        <h3>✓ Usuario Admin Listo</h3>
        <p><strong>Usuario:</strong> admin</p>
        <p><strong>Contraseña:</strong> admin123</p>
        <p><a href='admin/admin-login.php'>Ir al login del admin</a></p>
    </div>";
    
    // Verificar el usuario nuevamente
    $stmt = $db->prepare("SELECT username, nombre, rol, activo FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    echo "<h3>Estado del usuario:</h3>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
    // Test de verificación de contraseña
    $stmt = $db->prepare("SELECT password FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && password_verify('admin123', $result['password'])) {
        echo "<div class='success'>✓ Verificación de contraseña: OK</div>";
    } else {
        echo "<div class='error'>❌ Verificación de contraseña: FALLO</div>";
    }
    
} catch(Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>
        <h3>Posibles soluciones:</h3>
        <ul>
            <li>Verifica que XAMPP esté ejecutándose (Apache y MySQL)</li>
            <li>Asegúrate de que la base de datos 'reservapro' exista</li>
            <li>Ejecuta el archivo database.sql en phpMyAdmin</li>
            <li>Verifica las credenciales en config/database.php</li>
        </ul>
    </div>";
}

echo "</body></html>";
