<?php
// db/configuracion/change-password.php
session_start();
// Importante: Esto le dice al navegador que siempre devolveremos JSON, incluso si hay error
header('Content-Type: application/json'); 

// ACTIVAR REPORTE DE ERRORES (Solo para depurar, quitar en producción)
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // 1. CORRECCIÓN DE RUTA: Según tu código antiguo, solo subimos un nivel
    if (!file_exists('../conexion.php')) {
        throw new Exception("No se encuentra el archivo ../conexion.php");
    }
    require_once '../conexion.php'; 

    // Verificar sesión
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Sesión expirada. Recarga la página.");
    }

    $userId = $_SESSION['user_id'];
    $conn = getDatabaseConnection();
    
    // Obtener datos del JS
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception("No se recibieron datos JSON válidos.");
    }

    $action = $input['action'] ?? '';

    // --- ACCIÓN 1: VALIDAR CONTRASEÑA ACTUAL ---
    if ($action === 'validate') {
        $currentPass = $input['currentPass'] ?? '';
        
        $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if ($res && password_verify($currentPass, $res['password'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'La contraseña actual no es correcta.']);
        }

    // --- ACCIÓN 2: ACTUALIZAR CONTRASEÑA ---
    } else if ($action === 'update') {
        $newPass = $input['newPass'] ?? '';
        
        if (strlen($newPass) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres.");
        }

        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
        } else {
            throw new Exception("Error al guardar en la base de datos.");
        }
    } else {
        throw new Exception("Acción no válida.");
    }
    
    if (isset($stmt)) $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // En caso de cualquier error (ruta, base de datos, lógica), devolvemos JSON
    http_response_code(500); // Error de servidor
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>