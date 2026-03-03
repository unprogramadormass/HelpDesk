<?php
// db/header/user_data.php
session_start();
header('Content-Type: application/json');

// Desactivar errores visibles para no romper el JSON
error_reporting(0); 

// Ajusta esta ruta si tu archivo conexion.php está en otro lado
// __DIR__ es la carpeta actual (db/header), subimos un nivel (db) y buscamos conexion.php
require_once __DIR__ . '/../conexion.php';

$response = ["error" => "No se pudo procesar la solicitud"];

// Verificar si el usuario está logueado
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        if (!$conn) {
            throw new Exception("Error de conexión a BD");
        }

        // --- CONSULTA CON LOS NOMBRES EXACTOS DE TUS TABLAS ---
        // usuarios (u), puesto (p), roles (r)
        $sql = "SELECT 
                    u.firstname, 
                    u.firstapellido, 
                    u.avatar, 
                    p.nombre AS nombre_puesto, 
                    r.nombre AS nombre_rol 
                FROM usuarios u 
                LEFT JOIN puesto p ON u.puesto_id = p.id 
                LEFT JOIN roles r ON u.tipo_usuario = r.id 
                WHERE u.id = ?";
                
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error preparando SQL: " . $conn->error);

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            
            // 1. Nombre: "Juan" + Espacio + Primera Letra Apellido + "."
            $apellidoCorto = !empty($row["firstapellido"]) ? substr($row["firstapellido"], 0, 1) . "." : "";
            $nombreMostrar = $row["firstname"] . " " . $apellidoCorto;

            // 2. Rol: "NombrePuesto - NombreRol"
            // Si alguno es null, ponemos un texto por defecto
            $puesto = $row["nombre_puesto"] ?? "Sin Puesto";
            $rol = $row["nombre_rol"] ?? "Usuario";
            $rolMostrar = "$puesto - $rol";

            // 3. Respuesta JSON Exitosa
            $response = [
                "success" => true,
                "nombre_mostrar" => $nombreMostrar, // Ej: Felix M.
                "rol_mostrar"    => $rolMostrar,    // Ej: Gerente - Administrador
                "avatar"         => $row["avatar"]  // nombre_archivo.jpg
            ];
            
        } else {
            $response = ["error" => "Usuario no encontrado en BD (ID: $user_id)"];
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $response = ["error" => "Error interno: " . $e->getMessage()];
    }
} else {
    // Si llegas aquí, es porque NO has iniciado sesión o la sesión caducó
    $response = ["error" => "No hay sesión activa. Login requerido."];
}

echo json_encode($response);
exit;
?>