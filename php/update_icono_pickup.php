<?php
// Script para actualizar el icono de un servicio a Pickup 🚚
require_once __DIR__ . '/../config/supabase.php';
$db = getDB();
$servicio_id = 3;
$icono = '🚚';
$stmt = $db->prepare("UPDATE servicios SET icono = ? WHERE id = ?");
if ($stmt->execute([$icono, $servicio_id])) {
    echo "Icono Pickup actualizado correctamente para el servicio ID 3.";
} else {
    echo "Error al actualizar el icono.";
}
