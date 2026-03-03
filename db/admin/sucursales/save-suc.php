<?php
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Recibimos el ID (puede venir vacío)
    $id = $input['id'] ?? ''; 
    
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
        // --- CASO 1: EDITAR (UPDATE) ---
        $sql = "UPDATE sucursales SET nombre=?, folio=?, direccion=?, telefono=?, estatus=? WHERE id=?";
        
        if ($stmt = $conn->prepare($sql)) {
            // 'ssssssi' -> 6 strings y 1 integer (el ID)
            $stmt->bind_param("sssssi", $nombre, $folio, $direccion, $telefono, $estatus, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Sucursal actualizada correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
            }
            $stmt->close();
        }
    } else {
        // --- CASO 2: CREAR (INSERT) ---
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