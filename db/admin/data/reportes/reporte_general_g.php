<?php
// Establece la cabecera para indicar que la respuesta será en formato JSON
header('Content-Type: application/json');

// Asegúrate de que esta ruta a tu archivo de conexión sea la correcta
require_once __DIR__ . '/../../../conexion.php'; 

// Obtiene los datos enviados por JavaScript mediante POST
$input = json_decode(file_get_contents('php://input'), true);

// Extrae las variables de los filtros que sí se están usando
$fechaDesde = $input['fechaDesde'] ?? '';
$fechaHasta = $input['fechaHasta'] ?? '';
$incluirComentarios = $input['incluirComentarios'] ?? false;
// MODIFICADO: Se elimina la variable $agruparSucursal que ya no se usa.

try {
    // 1. Construye la consulta SQL principal
    $sql = "SELECT
                t.id, t.titulot, t.sucursal, t.estadot, t.prioridadt,
                t.fecha_creaciont, t.fecha_cierret, t.descripciont, t.nomarchivo,
                CONCAT(creador.nombres, ' ', creador.apellidos) AS nombre_creador,
                CONCAT(agente.nombres, ' ', agente.apellidos) AS nombre_agente
            FROM ticket AS t
            LEFT JOIN usuarios AS creador ON t.idusrcreador = creador.id
            LEFT JOIN usuarios AS agente ON t.usuario_asignado = agente.id
            WHERE 1=1";

    $params = [];
    $types = '';

    // Agrega el filtro de rango de fechas si se proporcionó
    if (!empty($fechaDesde) && !empty($fechaHasta)) {
        $sql .= " AND DATE(t.fecha_creaciont) BETWEEN ? AND ?";
        $params[] = $fechaDesde;
        $params[] = $fechaHasta;
        $types .= 'ss';
    }

    // MODIFICADO: Se establece un ordenamiento por defecto, ya que la opción de agrupar se eliminó.
    $sql .= " ORDER BY t.id ASC";

    // 2. Prepara y ejecuta la consulta principal de forma segura
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta principal: ' . $conn->error);
    }

    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $tickets = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 3. Si la opción está activada, busca los comentarios de cada ticket
    if ($incluirComentarios && !empty($tickets)) {
        $commentSql = "SELECT
                           c.comentario,
                           CONCAT(u.nombres, ' ', u.apellidos) AS nombre_comentarista,
                           c.fecha
                       FROM comentarios AS c
                       JOIN usuarios AS u ON c.usuario_id = u.id
                       WHERE c.ticket_id = ?
                       ORDER BY c.fecha ASC";

        $commentStmt = $conn->prepare($commentSql);
        if ($commentStmt === false) {
            throw new Exception('Error al preparar la consulta de comentarios: ' . $conn->error);
        }

        // Itera sobre cada ticket para obtener y adjuntar sus comentarios
        foreach ($tickets as $key => $ticket) {
            $ticketId = $ticket['id'];
            $commentStmt->bind_param('i', $ticketId);
            $commentStmt->execute();
            $commentResult = $commentStmt->get_result();
            $comments = $commentResult->fetch_all(MYSQLI_ASSOC);

            $formattedComments = "";
            foreach ($comments as $comment) {
                // Formatea cada comentario en una línea
                $formattedComments .= "• [" . $comment['fecha'] . "] " . $comment['nombre_comentarista'] . ": " . $comment['comentario'] . "\n";
            }
            // Agrega la cadena de comentarios al array del ticket
            $tickets[$key]['comentarios'] = trim($formattedComments);
        }
        $commentStmt->close();
    }

    // 4. Devuelve el resultado final en formato JSON
    echo json_encode($tickets);

} catch (Exception $e) {
    // En caso de error, devuelve un mensaje de error
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>