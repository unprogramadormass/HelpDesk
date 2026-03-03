<?php
$host = "127.0.0.1";
$port = "3306";
$usuario = "root";
$contrasena = "";
$base_datos = "tickets";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos, $port);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer el charset
$conn->set_charset("utf8");
function getDatabaseConnection() {
    global $conn;
    return $conn;
}
?>