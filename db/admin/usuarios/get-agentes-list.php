<?php
// db/admin/usuarios/get-agentes-list.php
header('Content-Type: application/json');
error_reporting(0); // Ocultar errores HTML para no romper el JSON

// 1. Buscador de conexión robusto (para evitar errores de ruta)
$paths = [
    '../../conexion.php',
    '../../../conexion.php',
    '../../../db/conexion.php',
    '../../db/conexion.php'
];

$conexionPath = null;
foreach ($paths as $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        $conexionPath = __DIR__ . '/' . $path;
        break;
    }
}

if (!$conexionPath) {
    // Si falla, enviamos error JSON en vez de HTML
    echo json_encode(['success' => false, 'message' => 'No se encontró conexion.php']);
    exit;
}

require_once $conexionPath;
$conn = getDatabaseConnection();

try {
    // 2. Obtener usuarios con rol 1 (Admin), 2 (Sup) o 3 (Agente) que estén activos (estado_id = 1)
    $sql = "SELECT id, CONCAT(firstname, ' ', firstapellido) as nombre 
            FROM usuarios 
            WHERE tipo_usuario IN (1, 2, 3) AND estado_id = 1 
            ORDER BY firstname ASC";
    
    $result = $conn->query($sql);
    
    $agentes = [];
    while($row = $result->fetch_assoc()) {
        $agentes[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $agentes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>