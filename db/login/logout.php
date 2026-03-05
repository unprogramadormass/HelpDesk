<?php
// /HelpDesk/db/login/logout.php

session_start();
header('Content-Type: application/json');

// IMPORTANTE: Incluimos conexion.php AHORA, mientras $_SESSION['db_name'] 
// aún existe, para que pueda conectarse a la BD de la empresa correcta.
require_once(__DIR__ . '/../../db/conexion.php'); 

$response = ['success' => false];

try {
    // Verificamos si existen los datos en la sesión
    if (isset($_SESSION['user_id']) && isset($_SESSION['token_sesion'])) {
        
        $userId = $_SESSION['user_id'];
        $token = $_SESSION['token_sesion'];

        if ($conn) {
            // 1. Desactivar la sesión en la tabla de control (dispositivos)
            $sql_sesion = "UPDATE sesiones_activas SET activo = 0 WHERE token_sesion = ? AND usuario_id = ?";
            $stmt_sesion = mysqli_prepare($conn, $sql_sesion);
            if ($stmt_sesion) {
                mysqli_stmt_bind_param($stmt_sesion, "si", $token, $userId);
                mysqli_stmt_execute($stmt_sesion);
                mysqli_stmt_close($stmt_sesion);
            }

            // 2. Marcar al usuario como "Desconectado" en la tabla de usuarios
            $sql_user = "UPDATE usuarios SET connected = 0 WHERE id = ?";
            $stmt_user = mysqli_prepare($conn, $sql_user);
            if ($stmt_user) {
                mysqli_stmt_bind_param($stmt_user, "i", $userId);
                mysqli_stmt_execute($stmt_user);
                mysqli_stmt_close($stmt_user);
            }
        }
    }

    // =========================================================================
    // LIMPIEZA TOTAL DE LA SESIÓN
    // =========================================================================
    
    // Vaciamos el arreglo de la sesión
    $_SESSION = array();

    // Borramos la cookie de la sesión del navegador
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente destruimos la sesión en el servidor
    session_destroy();

    $response['success'] = true;
    $response['message'] = 'Sesión finalizada correctamente';

} catch (Exception $e) {
    // Si algo falla, destruimos la sesión de todas formas por seguridad
    session_destroy();
    $response['success'] = false; 
    $response['message'] = 'Error técnico al cerrar sesión: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>