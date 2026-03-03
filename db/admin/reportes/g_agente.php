<?php
// db/admin/agentes/g_agente.php
header('Content-Type: application/json');

// 1. Incluir tu archivo de conexión (ajusta los ../ si cambias la carpeta)
require_once '../../conexion.php'; 

try {
    // 2. Obtener la instancia de conexión usando tu función
    $conn = getDatabaseConnection();

    // 3. Consulta SQL
    // - Corregí 'firtsapellido' a 'firstapellido' (como está en tu BD).
    // - Agregué 'AS nombres' y 'AS apellidos' para que coincida con tu JS reporte_agente_g.js
    // - Agregué 'AND estado_id = 1' para traer solo agentes Activos (opcional, pero recomendado).
    $sql = "SELECT 
                id, 
                firstname AS nombres, 
                firstapellido AS apellidos 
            FROM usuarios 
            WHERE tipo_usuario IN (1, 2, 3) AND estado_id = 1
            ORDER BY firstname ASC";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    // Obtener los datos como array asociativo
    $agentes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 4. Devolver JSON
    echo json_encode($agentes);

} catch (Exception $e) {
    // Manejo de errores limpio
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron cargar los agentes: ' . $e->getMessage()]);
}

// Cerrar conexión si sigue abierta
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>