<?php
header('Content-Type: application/json');
require_once '../../conexion.php';

$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID no válido.']);
        exit;
    }

    $sql = "DELETE FROM areas WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Área eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar (quizás tiene puestos asignados).']);
    }
    $stmt->close();
}
$conn->close();
?>