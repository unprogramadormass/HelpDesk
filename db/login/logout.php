<?php
// /HelpDesk/db/login/logout.php

session_start();
header('Content-Type: application/json');

// Ajusta la ruta a tu conexión si es necesario
require_once(__DIR__ . '/../../db/conexion.php'); 

$response = ['success' => false];

try {
    // Verificamos si existen los datos en la sesión
    // Nota: 'token_sesion' ahora existirá porque lo agregamos en log.php
    if (isset($_SESSION['user_id']) && isset($_SESSION['token_sesion'])) {
        
        $userId = $_SESSION['user_id'];
        $token = $_SESSION['token_sesion']; // El token que guardamos al loguear

        // Usamos sintaxis MySQLi (Signos de interrogación ?) en lugar de PDO (:token)
        $sql = "UPDATE sesiones_activas SET activo = 0 WHERE token_sesion = ? AND usuario_id = ?";
        
        if ($conn) {
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                // "si" significa String (token), Integer (userId)
                mysqli_stmt_bind_param($stmt, "si", $token, $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Limpieza de sesión estándar
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    $response['success'] = true;
    $response['message'] = 'Sesión finalizada correctamente';

} catch (Exception $e) {
    session_destroy();
    $response['success'] = true; 
    $response['message'] = 'Error técnico (BD): ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>