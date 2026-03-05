<?php
// Asegurarnos de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CARGAR VARIABLES DEL .ENV
require_once(__DIR__ . '/load_env.php');

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];

// Lógica SaaS: Leer la base de datos de la empresa actual
if (isset($_SESSION['db_name']) && !empty($_SESSION['db_name'])) {
    $base_datos = $_SESSION['db_name'];
} else {
    die("Acceso denegado: Sesión expirada o no pertenece a ninguna empresa.");
}

$conn = new mysqli($host, $usuario, $contrasena, $base_datos, $port);

if ($conn->connect_error) {
    die("Error de conexión a la empresa: " . $conn->connect_error);
}

$conn->set_charset("utf8");

function getDatabaseConnection() {
    global $conn;
    return $conn;
}
?>