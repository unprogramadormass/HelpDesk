<?php
// obtener_sucursales.php
header('Content-Type: application/json');

// Ajusta la ruta a tu conexión igual que en el archivo anterior
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

// Traemos las sucursales ordenadas por ID descendente (las nuevas primero)
$sql = "SELECT * FROM sucursales ORDER BY id DESC";
$result = $conn->query($sql);

$sucursales = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Añadimos cada fila al array
        $sucursales[] = $row;
    }
}

// Devolvemos el JSON limpio
echo json_encode($sucursales);
$conn->close();
?>