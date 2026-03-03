<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Incluimos la conexión a la BD (Asegúrate de que la ruta a conexion.php sea la correcta)
require_once(__DIR__ . '/../conexion.php'); 

/**
 * Función para verificar si el usuario tiene permiso de ver la página
 * @param array $rolesPermitidos Arreglo con los IDs de los roles que pueden entrar
 */
function verificarAcceso($rolesPermitidos = []) {
    global $conn; // Traemos la variable de conexión de conexion.php

    // 1. Verificar si el usuario ha iniciado sesión
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
        header("Location: /HelpDesk/index.html");
        exit();
    }

    $rolUsuario = (int)$_SESSION['rol'];
    
    // 2. Verificar si el rol del usuario está dentro de la lista de permitidos
    if (!empty($rolesPermitidos) && !in_array($rolUsuario, $rolesPermitidos)) {
        // ¡No tiene permiso! Consultamos su ruta en la base de datos y lo mandamos allá
        redirigirSegunRolDB($rolUsuario, $conn);
    }
}

/**
 * Función que consulta la tabla "roles" y redirige a la URL configurada
 */
function redirigirSegunRolDB($rolId, $conn) {
    if (!$conn) {
        die("Error: No hay conexión a la base de datos para validar la seguridad.");
    }

    $sql = "SELECT redirect_url FROM roles WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $rolId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $redirectUrl = $row['redirect_url']; // Ejemplo: "./pages/usr/admin/index.php"
            
            // NORMALIZACIÓN DE RUTAS: 
            // Como en tu BD tienes rutas con "./" y otras con "/helpdesk/", 
            // esto se asegura de que la redirección funcione en cualquier navegador.
            if (strpos($redirectUrl, './') === 0) {
                // Cambia "./pages/..." por "/HelpDesk/pages/..."
                $redirectUrl = '/HelpDesk/' . substr($redirectUrl, 2);
            } elseif (stripos($redirectUrl, '/helpdesk') !== 0) {
                // Si no empieza con slash o HelpDesk, se lo ponemos por seguridad
                $redirectUrl = '/HelpDesk/' . ltrim($redirectUrl, '/');
            }
            
            // Redirigir a su verdadera casa
            header("Location: " . $redirectUrl);
            exit();
        }
    }
    
    // Si llegamos aquí es porque el rol no existe en la BD o hubo un error severo.
    // Destruimos la sesión como medida de seguridad y lo mandamos a login.
    session_destroy();
    header("Location: /HelpDesk/index.html");
    exit();
}
?>