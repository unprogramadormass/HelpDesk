<?php
// db/configuracion/close-session.php
session_start();
header('Content-Type: application/json');

// Ajusta la ruta a tu conexión
require_once '../conexion.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

// Recibir JSON
$input = json_decode(file_get_contents('php://input'), true);
$idSesion = $input['id'] ?? 0;
$userId = $_SESSION['user_id'];

try {
    $conn = getDatabaseConnection();

    // Actualizamos a activo = 0
    // IMPORTANTE: Validar "AND usuario_id = ?" para que nadie cierre sesiones ajenas
    $stmt = $conn->prepare("UPDATE sesiones_activas SET activo = 0 WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $idSesion, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error al actualizar la base de datos.");
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>