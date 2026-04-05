<?php
require_once 'config/database.php';

echo "=== TEST DE BASE DE DATOS ===\n\n";

try {
    $db = getDB();
    echo "✓ Conexión a base de datos exitosa\n\n";
    
    // Verificar usuarios
    $stmt = $db->query("SELECT username, nombre, rol, activo FROM usuarios");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ No hay usuarios en la base de datos\n";
        echo "Necesitas ejecutar el archivo database.sql\n";
    } else {
        echo "✓ Usuarios encontrados:\n";
        foreach($users as $user) {
            echo "  - Usuario: {$user['username']} | Nombre: {$user['nombre']} | Rol: {$user['rol']} | Activo: " . ($user['activo'] ? 'SI' : 'NO') . "\n";
        }
    }
    
    echo "\n=== TEST DE CONTRASEÑA ===\n";
    // Verificar el hash de la contraseña admin123
    $password = 'admin123';
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    if (password_verify($password, $hash)) {
        echo "✓ La contraseña 'admin123' coincide con el hash de la base de datos\n";
    } else {
        echo "❌ La contraseña 'admin123' NO coincide con el hash\n";
    }
    
    // Generar nuevo hash si es necesario
    echo "\nNuevo hash para 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT) . "\n";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
