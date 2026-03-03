<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../conexion.php'); // Ajusta tu ruta de conexión

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['id_usuario'];
// Recibimos los datos enviados desde JavaScript
$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'fetch':
        // Leer tareas según el filtro
        $filtro = $_GET['filtro'] ?? 'todas';
        $sql = "SELECT id, descripcion, estado, DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha FROM pendientes WHERE usuario_id = ?";
        
        if ($filtro === 'pendientes') $sql .= " AND estado = 'pendiente'";
        elseif ($filtro === 'completadas') $sql .= " AND estado = 'completada'";
        elseif ($filtro === 'eliminadas') $sql .= " AND estado = 'eliminada'";
        else $sql .= " AND estado != 'eliminada'"; // 'todas' muestra pendientes y completadas

        $sql .= " ORDER BY id DESC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $tareas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $tareas[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $tareas]);
        break;

    case 'add':
        // Guardar nueva tarea
        $descripcion = trim($data['descripcion']);
        $stmt = mysqli_prepare($conn, "INSERT INTO pendientes (usuario_id, descripcion) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "is", $userId, $descripcion);
        $success = mysqli_stmt_execute($stmt);
        echo json_encode(['success' => $success]);
        break;

    case 'update_status':
        // Marcar como completada, pendiente o eliminada
        $id = $data['id'];
        $estado = $data['estado'];
        $stmt = mysqli_prepare($conn, "UPDATE pendientes SET estado = ? WHERE id = ? AND usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $estado, $id, $userId);
        $success = mysqli_stmt_execute($stmt);
        echo json_encode(['success' => $success]);
        break;

    case 'edit':
        // Editar el texto de la tarea
        $id = $data['id'];
        $descripcion = trim($data['descripcion']);
        $stmt = mysqli_prepare($conn, "UPDATE pendientes SET descripcion = ? WHERE id = ? AND usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $descripcion, $id, $userId);
        $success = mysqli_stmt_execute($stmt);
        echo json_encode(['success' => $success]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>