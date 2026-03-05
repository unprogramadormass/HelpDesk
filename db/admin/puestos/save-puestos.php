<?php
// guardar_puesto.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// SEGURIDAD: Solo Administradores
session_start();
require_once '../../security/validacion.php';
verificarAcceso([1]); 

try {
    require_once '../../conexion.php'; 
    $conn = getDatabaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : ''; // Sanitización a INT
        $nombre = trim($input['nombre'] ?? ''); // Sanitizar espacios

        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del puesto es obligatorio.']);
            exit;
        }

        if (!empty($id)) {
            $sql = "UPDATE puesto SET nombre=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nombre, $id);
            $msg = 'Puesto actualizado correctamente.';
        } else {
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