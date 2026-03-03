<?php
// db/admin/niveles/get_niveles_lista.php
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();
$sql = "SELECT id, nombre_mostrar FROM niveles_incidencias ORDER BY id ASC";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>