<?php
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

// Obtenemos las áreas ordenadas por las más nuevas primero
$sql = "SELECT * FROM areas ORDER BY id DESC";
$result = $conn->query($sql);

$areas = [];
while ($row = $result->fetch_assoc()) {
    $areas[] = $row;
}

echo json_encode($areas);
$conn->close();
?>