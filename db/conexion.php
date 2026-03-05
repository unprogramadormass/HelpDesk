<?php
/// Verificar si la sesión ya está activa antes de intentar configurar ini_set
if (session_status() === PHP_SESSION_NONE) {
    // Configurar seguridad de cookies SOLO si la sesión no ha iniciado aún
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

require_once(__DIR__ . '/load_env.php');

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];

if (isset($_SESSION['db_name']) && !empty($_SESSION['db_name'])) {
    $base_datos = $_SESSION['db_name'];
} else {
    die("Acceso denegado: Sesión expirada.");
}

$conn = new mysqli($host, $usuario, $contrasena, $base_datos, $port);

if ($conn->connect_error) {
    die("Error de conexión");
}

$conn->set_charset("utf8");

function getDatabaseConnection() {
    global $conn;
    return $conn;
}
?>