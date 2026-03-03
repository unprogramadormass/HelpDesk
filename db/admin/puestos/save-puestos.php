<?php
// guardar_puesto.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    if (!file_exists('../../conexion.php')) throw new Exception("No se encuentra conexion.php");
    require_once '../../conexion.php'; 
    $conn = getDatabaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';
        $nombre = $input['nombre'] ?? '';

        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del puesto es obligatorio.']);
            exit;
        }

        if (!empty($id)) {
            // EDITAR
            $sql = "UPDATE puesto SET nombre=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nombre, $id);
            $msg = 'Puesto actualizado correctamente.';
        } else {
            // CREAR
            $sql = "INSERT INTO puesto (nombre, fecha_creacion) VALUES (?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $nombre);
            $msg = 'Puesto creado correctamente.';
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            throw new Exception("Error BD: " . $stmt->error);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();
?>