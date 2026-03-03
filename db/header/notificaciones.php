<?php
// Evitamos que PHP imprima errores en formato HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

try {
    require_once(__DIR__ . '/../conexion.php');
    
    $conn = getDatabaseConnection();
    if (!$conn) throw new Exception("Error al conectar con la base de datos.");

    $user_id = $_SESSION['user_id'];

    /* * CONSULTA CON LA LÓGICA DE DESTINATARIO
     * Usamos LEFT JOIN con areas por seguridad.
     */
    $query = "
        SELECT 
            th.id, 
            CONCAT(th.descripcion_evento, ' | Área destino: ', IFNULL(a.nombre, 'Sin asignar')) AS descripcion, 
            t.id AS ticket_id, 
            th.fecha_movimiento AS fecha_creacion, 
            th.vista AS leida
        FROM ticket_historial th
        INNER JOIN ticket t ON th.ticket_id = t.id
        LEFT JOIN areas a ON t.area_id = a.id
        WHERE 
            -- 1. El usuario debe ser parte del ticket (Creador o Agente)
            (t.usuario_creador_id = ? OR t.agente_actual_id = ?) 
            -- 2. El usuario NO debe ser quien originó este movimiento
            AND th.usuario_responsable_id != ?
        ORDER BY th.fecha_movimiento DESC
        LIMIT 50
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("Error en la consulta SQL: " . $conn->error);

    // Pasamos el $user_id tres veces para la comparativa que mencionaste:
    // 1 para creador, 1 para agente actual, 1 para descartarlo como responsable
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notificaciones = [];
    while ($row = $result->fetch_assoc()) {
        $row['leida'] = ($row['leida'] === null || $row['leida'] === '' || $row['leida'] == 0) ? null : (int)$row['leida'];
        $notificaciones[] = $row;
    }

    $stmt->close();
    
    echo json_encode($notificaciones);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje_backend' => $e->getMessage()
    ]);
}
?>