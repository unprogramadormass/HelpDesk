<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../conexion.php');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'Sesión no iniciada']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$nueva = $data['nueva'] ?? '';
$confirmar = $data['confirmar'] ?? '';

if (empty($nueva) || empty($confirmar)) {
    echo json_encode(['error' => 'Debes completar los campos']);
    exit;
}

if ($nueva !== $confirmar) {
    echo json_encode(['error' => 'Las contraseñas no coinciden']);
    exit;
}

$id = $_SESSION['id_usuario'];
$nuevaHash = password_hash($nueva, PASSWORD_DEFAULT);

// Actualizar contraseña y quitar el flag de cambio obligatorio
$stmt = $conn->prepare("UPDATE usuarios SET password = ?, requiere_cambio_password = 0 WHERE id = ?");
$stmt->bind_param("si", $nuevaHash, $id);

if ($stmt->execute()) {
    // 🔄 Redirigir siempre a app.php
    $redirect = '../login/index.html';
    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode(['error' => 'Error al actualizar contraseña']);
}
