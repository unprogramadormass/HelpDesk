<?php
// Evitamos que PHP imprima errores en formato HTML para no romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

try {
    require_once(__DIR__ . '/../conexion.php');
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        throw new Exception("Error al conectar con la base de datos.");
    }

    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    $notifId = isset($data['id']) ? intval($data['id']) : 0;

    if ($notifId <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    /* * 1. Verificar que la notificación (movimiento del historial) 
     * pertenece realmente al usuario usando la misma lógica que al listarlas.
     */
    $checkQuery = "
        SELECT th.id 
        FROM ticket_historial th
        INNER JOIN ticket t ON th.ticket_id = t.id
        WHERE th.id = ? 
          AND (t.usuario_creador_id = ? OR t.agente_actual_id = ?) 
          AND th.usuario_responsable_id != ?
    ";
    
    $checkStmt = $conn->prepare($checkQuery);
    if (!$checkStmt) throw new Exception("Error preparando validación: " . $conn->error);
    
    // Pasamos el ID del movimiento (1 vez) y el del usuario (3 veces)
    $checkStmt->bind_param("iiii", $notifId, $user_id, $user_id, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Notificación no encontrada o no tienes permisos']);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();

    /*
     * 2. Marcar como leída (establecer vista = 1 en ticket_historial)
     */
    $updateQuery = "UPDATE ticket_historial SET vista = 1 WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) throw new Exception("Error preparando actualización: " . $conn->error);
    
    $updateStmt->bind_param("i", $notifId);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el estado']);
    }

    $updateStmt->close();

} catch (Exception $e) {
    // Si algo falla, devolvemos un error limpio en JSON
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor',
        'mensaje_backend' => $e->getMessage()
    ]);
}
?>