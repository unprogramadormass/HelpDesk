<?php
header('Content-Type: application/json');
session_start();

// SEGURIDAD: Evitar que alguien borre áreas sin ser Administrador
require_once '../../security/validacion.php';
verificarAcceso([1]); // Solo Administradores pueden ejecutar esto

require_once '../../conexion.php';
$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? (int)$input['id'] : 0; // Forzamos a que sea un número

    if (empty($id) || $id <= 0) {
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