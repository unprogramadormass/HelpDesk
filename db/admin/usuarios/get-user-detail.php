<?php
// get_user_detail.php
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

try {
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        throw new Exception("ID de usuario no proporcionado.");
    }

    // 1. Obtener Datos del Usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        throw new Exception("Usuario no encontrado.");
    }

    // 2. Obtener IDs de Permisos asignados
    $permisos = [];
    $stmtPerm = $conn->prepare("SELECT permiso_id FROM usuario_permisos WHERE usuario_id = ?");
    $stmtPerm->bind_param("i", $id);
    $stmtPerm->execute();
    $resPerm = $stmtPerm->get_result();
    
    while ($row = $resPerm->fetch_assoc()) {
        $permisos[] = $row['permiso_id'];
    }
    $stmtPerm->close();
    
    // Agregar permisos al objeto usuario para enviarlo todo junto
    $usuario['permisos_asignados'] = $permisos;

    // -----------------------------------------------------------------
    // 3. NUEVO: Obtener IDs de Áreas asignadas desde la tabla pivote
    // -----------------------------------------------------------------
    $areas = [];
    $stmtAreas = $conn->prepare("SELECT area_id FROM usuario_areas WHERE usuario_id = ?");
    $stmtAreas->bind_param("i", $id);
    $stmtAreas->execute();
    $resAreas = $stmtAreas->get_result();
    
    while ($row = $resAreas->fetch_assoc()) {
        $areas[] = $row['area_id'];
    }
    $stmtAreas->close();

    // Agregar áreas al objeto usuario
    $usuario['areas_asignadas'] = $areas;
    // -----------------------------------------------------------------

    // Limpiar password por seguridad (no se debe enviar al front)
    unset($usuario['password']);

    echo json_encode(['success' => true, 'data' => $usuario]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>