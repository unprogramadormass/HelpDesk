<?php
session_start();
header('Content-Type: application/json');
require_once '../../conexion.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']); exit;
}

$conn = getDatabaseConnection();
$userId = $_SESSION['user_id']; // Quien realiza la acción (Admin/Sup)

try {
    if ($_POST['action'] === 'assign_agent') {
        $ticketId = $_POST['ticket_id'];
        $newAgentId = $_POST['agente_id'];

        // 1. Obtener agente actual para ver si cambió
        $stmtCheck = $conn->prepare("SELECT agente_actual_id FROM ticket WHERE id = ?");
        $stmtCheck->bind_param("i", $ticketId);
        $stmtCheck->execute();
        $current = $stmtCheck->get_result()->fetch_assoc();

        if ($current['agente_actual_id'] == $newAgentId) {
            echo json_encode(['success' => true, 'message' => 'El agente ya está asignado.']);
            exit;
        }

        // 2. Actualizar Ticket
        $sqlUp = "UPDATE ticket SET 
                    agente_anterior_id = agente_actual_id,
                    agente_actual_id = ?,
                    estado = IF(estado = 'Abierto', 'Asignado', estado), -- Cambiar estado si estaba abierto
                    fecha_asignacion = NOW(),
                    fecha_ultima_actualizacion = NOW()
                  WHERE id = ?";
        
        $stmtUp = $conn->prepare($sqlUp);
        $stmtUp->bind_param("ii", $newAgentId, $ticketId);
        
        if ($stmtUp->execute()) {
            // 3. Historial
            $desc = "Ticket asignado a agente ID: " . $newAgentId; // Podrías buscar el nombre para ser más prolijo
            $stmtHist = $conn->prepare("INSERT INTO ticket_historial (ticket_id, usuario_responsable_id, tipo_movimiento, descripcion_evento) VALUES (?, ?, 'Asignación', ?)");
            $stmtHist->bind_param("iis", $ticketId, $userId, $desc);
            $stmtHist->execute();

            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error SQL: " . $stmtUp->error);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>