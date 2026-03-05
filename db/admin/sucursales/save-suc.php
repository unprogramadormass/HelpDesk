<?php
header('Content-Type: application/json');
session_start();

// SEGURIDAD: Evitar que alguien cree/edite sucursales sin ser Administrador
require_once '../../security/validacion.php';
verificarAcceso([1]); 

require_once '../../conexion.php'; 
$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = isset($input['id']) ? (int)$input['id'] : ''; 
    $nombre = $input['nombre'] ?? '';
    $folio = $input['folio'] ?? '';
    $direccion = $input['direccion'] ?? '';
    $telefono = $input['telefono'] ?? '';
    $estatus = $input['estatus'] ?? 'OPERATIVA';

    if (empty($nombre) || empty($folio)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
        exit;
    }

    if (!empty($id)) {
        $sql = "UPDATE sucursales SET nombre=?, folio=?, direccion=?, telefono=?, estatus=? WHERE id=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssi", $nombre, $folio, $direccion, $telefono, $estatus, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Sucursal actualizada correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
            }
            $stmt->close();
        }
    } else {
        $sql = "INSERT INTO sucursales (nombre, folio, direccion, telefono, estatus, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $nombre, $folio, $direccion, $telefono, $estatus);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Sucursal creada correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear: ' . $stmt->error]);
            }
            $stmt->close();
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
$conn->close();
?>