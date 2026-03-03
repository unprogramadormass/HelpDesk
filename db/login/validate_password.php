<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../conexion.php');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'Sesión no iniciada']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$actual = $data['actual'] ?? '';

if (empty($actual)) {
    echo json_encode(['error' => 'Contraseña actual requerida']);
    exit;
}

$id = $_SESSION['id_usuario'];

$stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($actual, $user['password'])) {
    echo json_encode(['error' => 'Contraseña incorrecta']);
    exit;
}

echo json_encode(['success' => true]);
