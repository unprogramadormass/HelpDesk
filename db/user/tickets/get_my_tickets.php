<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Ajusta esta ruta a tu archivo de conexión real
require_once(__DIR__ . '/../../conexion.php'); 

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['id_usuario'];

// Consulta que une los tickets con los nombres de los usuarios (Creador y Agente)
$sql = "SELECT 
            t.id, 
            t.folio, 
            t.titulo, 
            t.prioridad, 
            t.estado, 
            DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y') as fecha,
            CONCAT(u_creador.firstname, ' ', u_creador.firstapellido) as empleado,
            IFNULL(CONCAT(u_agente.firstname, ' ', u_agente.firstapellido), 'Sin asignar') as agente
        FROM ticket t
        INNER JOIN usuarios u_creador ON t.usuario_creador_id = u_creador.id
        LEFT JOIN usuarios u_agente ON t.agente_actual_id = u_agente.id
        WHERE t.usuario_creador_id = ?
        ORDER BY t.id DESC"; // Los más recientes primero

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}

// Retornamos el JSON limpio
echo json_encode(['success' => true, 'data' => $tickets]);
?>