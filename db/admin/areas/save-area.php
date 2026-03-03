<?php
header('Content-Type: application/json');
require_once '../../conexion.php'; // Ajusta la ruta a tu conexion.php

$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? '';
    $nombre = $input['nombre'] ?? '';

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del área es obligatorio.']);
        exit;
    }

    if (!empty($id)) {
        // --- EDITAR (UPDATE) ---
        $sql = "UPDATE areas SET nombre=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nombre, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Área actualizada con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
        }
    } else {
        // --- CREAR (INSERT) ---
        $sql = "INSERT INTO areas (nombre, fecha_creacion) VALUES (?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombre);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nueva área creada con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear.']);
        }
    }
    $stmt->close();
}
$conn->close();
?>