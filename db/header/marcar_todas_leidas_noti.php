<?php
// Evitamos que PHP imprima errores en formato HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

try {
    require_once(__DIR__ . '/../conexion.php');
    $conn = getDatabaseConnection();
    if (!$conn) throw new Exception("Error al conectar con la base de datos.");

    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    $ids = isset($data['ids']) ? $data['ids'] : [];

    $user_id = $_SESSION['user_id'];

    // Si no se enviaron IDs específicos, marcar TODAS las no leídas del usuario
    if (empty($ids) || !is_array($ids)) {
        
        // Actualizamos usando INNER JOIN para validar los permisos
        $query = "
            UPDATE ticket_historial th
            INNER JOIN ticket t ON th.ticket_id = t.id
            SET th.vista = 1 
            WHERE (t.usuario_creador_id = ? OR t.agente_actual_id = ?) 
              AND th.usuario_responsable_id != ? 
              AND th.vista = 0
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) throw new Exception("Error preparando actualización masiva: " . $conn->error);
        
        // Pasamos el ID 3 veces (creador, agente, no-responsable)
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            echo json_encode([
                'success' => true, 
                'message' => "Se marcaron $affectedRows notificaciones como leídas",
                'affected_rows' => $affectedRows
            ]);
        } else {
            throw new Exception("Error al ejecutar la actualización masiva.");
        }
        
        $stmt->close();

    } else {
        // Opción 2: Marcar solo los IDs específicos enviados (Es la que usa tu JS)
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) {
            return $id > 0;
        });
        
        if (empty($ids)) {
            echo json_encode(['success' => false, 'error' => 'IDs inválidos']);
            exit();
        }
        
        // Crear placeholders (?, ?, ?) para la consulta
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Verificar que las notificaciones pertenecen al usuario destino
        $checkQuery = "
            SELECT COUNT(th.id) as total 
            FROM ticket_historial th
            INNER JOIN ticket t ON th.ticket_id = t.id
            WHERE th.id IN ($placeholders) 
              AND (t.usuario_creador_id = ? OR t.agente_actual_id = ?) 
              AND th.usuario_responsable_id != ?
        ";
        
        $checkStmt = $conn->prepare($checkQuery);
        if (!$checkStmt) throw new Exception("Error preparando validación: " . $conn->error);
        
        // Tipos de parámetros: IDs (todos 'i') + 3 veces 'i' para los user_id
        $types = str_repeat('i', count($ids)) . 'iii';
        $params = array_merge($ids, [$user_id, $user_id, $user_id]);
        
        // Usar operador spread (...) para pasar el array dinámico a bind_param
        $checkStmt->bind_param($types, ...$params);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $row = $checkResult->fetch_assoc();
        
        // Solo como medida de seguridad estricta, verificamos que coincidan
        if ($row['total'] !== count($ids)) {
            echo json_encode([
                'success' => false, 
                'error' => 'Algunas notificaciones no existen o no te pertenecen'
            ]);
            $checkStmt->close();
            exit();
        }
        $checkStmt->close();
        
        // Ahora sí, marcamos como leídas en ticket_historial
        $updateQuery = "UPDATE ticket_historial SET vista = 1 WHERE id IN ($placeholders)";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) throw new Exception("Error preparando actualización por IDs: " . $conn->error);
        
        // Vincular solo los IDs
        $types = str_repeat('i', count($ids));
        $updateStmt->bind_param($types, ...$ids);
        
        if ($updateStmt->execute()) {
            $affectedRows = $updateStmt->affected_rows;
            echo json_encode([
                'success' => true,
                'message' => "Se marcaron $affectedRows notificaciones como leídas",
                'affected_rows' => $affectedRows
            ]);
        } else {
            throw new Exception("Error al actualizar por IDs.");
        }
        
        $updateStmt->close();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor',
        'mensaje_backend' => $e->getMessage()
    ]);
}
?>