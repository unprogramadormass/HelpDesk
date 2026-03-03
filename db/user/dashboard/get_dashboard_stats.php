<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Ajusta esta ruta a donde tengas tu archivo de conexión real
require_once(__DIR__ . '/../../conexion.php'); 

// Verificamos que el usuario haya iniciado sesión (usamos la variable de tu login)
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['id_usuario'];

// Consulta optimizada: Cuenta todo en un solo viaje a la base de datos
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado IN ('Abierto', 'Asignado', 'En Proceso', 'Espera') THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado IN ('Resuelto', 'Cerrado') THEN 1 ELSE 0 END) as finalizados,
            SUM(CASE WHEN estado = 'Cancelado' THEN 1 ELSE 0 END) as cancelados
        FROM ticket 
        WHERE usuario_creador_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

// Devolvemos los datos en formato JSON para que JavaScript los lea fácil
echo json_encode([
    'success' => true,
    'data' => [
        'total' => $data['total'] ?? 0,
        'pendientes' => $data['pendientes'] ?? 0,
        'finalizados' => $data['finalizados'] ?? 0,
        'cancelados' => $data['cancelados'] ?? 0
    ]
]);
?>