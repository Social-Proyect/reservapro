<?php
// Script para actualizar el icono de un servicio a SVG pickup doble cabina
require_once __DIR__ . '/../config/supabase.php';
$db = getDB();
$servicio_id = 3;
$icono = 'assets/img/pickup_doble_cabina.svg';
$stmt = $db->prepare("UPDATE servicios SET icono = ? WHERE id = ?");
if ($stmt->execute([$icono, $servicio_id])) {
    echo "Icono pickup doble cabina (SVG) actualizado correctamente para el servicio ID 3.";
} else {
    echo "Error al actualizar el icono.";
}
