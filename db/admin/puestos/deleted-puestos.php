<?php
// eliminar_puesto.php
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../conexion.php';

$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    $sql = "DELETE FROM puesto WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Puesto eliminado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
    $stmt->close();
}
$conn->close();
?>