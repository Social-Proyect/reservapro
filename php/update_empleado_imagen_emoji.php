<?php
// Script para actualizar el campo imagen de un empleado con un emoji de cuerpo completo
require_once __DIR__ . '/../config/supabase.php';
$db = getDB();
$empleado_id = 1; // Cambia este valor al id del empleado que desees
$emoji = '🧍'; // Emoji de persona de pie (cuerpo completo)
$stmt = $db->prepare("UPDATE empleados SET foto = ? WHERE id = ?");
if ($stmt->execute([$emoji, $empleado_id])) {
    echo "Emoji de cuerpo completo insertado correctamente en el campo imagen del empleado ID $empleado_id.";
} else {
    echo "Error al actualizar el campo imagen.";
}
