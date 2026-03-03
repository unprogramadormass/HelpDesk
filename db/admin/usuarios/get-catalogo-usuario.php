<?php
// get_catalogos_usuario.php
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

try {
    $data = [];

    // 1. Roles
    $res = $conn->query("SELECT id, nombre FROM roles ORDER BY nombre ASC");
    $data['roles'] = $res->fetch_all(MYSQLI_ASSOC);

    // 2. Puestos
    $res = $conn->query("SELECT id, nombre FROM puesto ORDER BY nombre ASC");
    $data['puestos'] = $res->fetch_all(MYSQLI_ASSOC);

    // 3. Áreas
    $res = $conn->query("SELECT id, nombre FROM areas ORDER BY nombre ASC");
    $data['areas'] = $res->fetch_all(MYSQLI_ASSOC);

    // 4. Sucursales
    $res = $conn->query("SELECT id, nombre FROM sucursales WHERE estatus='OPERATIVA' ORDER BY nombre ASC");
    $data['sucursales'] = $res->fetch_all(MYSQLI_ASSOC);

    // 5. Niveles de Incidencia
    $res = $conn->query("SELECT id, nombre_mostrar FROM niveles_incidencias ORDER BY id ASC");
    $data['niveles'] = $res->fetch_all(MYSQLI_ASSOC);

    // 6. Permisos (Para generar los checkboxes dinámicamente)
    $res = $conn->query("SELECT id, descripcion FROM permisos ORDER BY id ASC");
    $data['permisos'] = $res->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>