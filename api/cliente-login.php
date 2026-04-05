<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$telefono = $_POST['telefono'] ?? '';
$password = $_POST['password'] ?? '';

if (!$telefono || !$password) {
    echo json_encode(['success' => false, 'message' => 'Teléfono y contraseña requeridos']);
    exit;
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM clientes WHERE telefono = ? LIMIT 1');
$stmt->execute([$telefono]);
$cliente = $stmt->fetch();

if ($cliente && !empty($cliente['password']) && password_verify($password, $cliente['password'])) {
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_telefono'] = $cliente['telefono'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
