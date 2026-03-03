<?php
// get-puestos.php
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

$sql = "SELECT * FROM puesto ORDER BY id DESC";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>