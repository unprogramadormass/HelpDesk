<?php
// db/configuracion/get_config_data.php
session_start();
header('Content-Type: application/json');
error_reporting(0);

if (file_exists('../conexion.php')) {
    require_once '../conexion.php';
} elseif (file_exists('../../conexion.php')) {
    require_once '../../conexion.php';
} else {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión']);
    exit;
}

$userId = $_SESSION['user_id'];
$conn = getDatabaseConnection();

try {
    // 1. Datos Perfil
    $sql = "SELECT firstname, secondname, firstapellido, secondapellido, email, celular, extension, avatar, 
                   noti_whatsapp, noti_email, noti_nuevo, noti_sistema 
            FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ---------------------------------------------------------
    // 2. SESIONES ACTIVAS (activo = 1)
    // ---------------------------------------------------------
    $sqlActive = "SELECT id, ip_address, dispositivo, ubicacion, ultimo_acceso, token_sesion 
                  FROM sesiones_activas 
                  WHERE usuario_id = ? AND activo = 1 
                  ORDER BY ultimo_acceso DESC LIMIT 5";
    $stmtActive = $conn->prepare($sqlActive);
    $stmtActive->bind_param("i", $userId);
    $stmtActive->execute();
    $resActive = $stmtActive->get_result();
    
    $activeSessions = [];
    $currentSessionId = session_id();

    while ($row = $resActive->fetch_assoc()) {
        $es_actual = ($row['token_sesion'] === $currentSessionId);
        $fechaFormatted = date("d/m/Y h:i A", strtotime($row['ultimo_acceso']));

        $activeSessions[] = [
            'id' => $row['id'],
            'dispositivo' => $row['dispositivo'],
            'ubicacion' => $row['ubicacion'],
            'fecha' => $fechaFormatted,
            'es_actual' => $es_actual
        ];
    }
    $stmtActive->close();

    // ---------------------------------------------------------
    // 3. HISTORIAL DE SESIONES (activo = 0)
    // ---------------------------------------------------------
    // Traemos las últimas 10 sesiones cerradas
    $sqlHistory = "SELECT id, ip_address, dispositivo, ubicacion, ultimo_acceso 
                   FROM sesiones_activas 
                   WHERE usuario_id = ? AND activo = 0 
                   ORDER BY ultimo_acceso DESC LIMIT 10";
    $stmtHistory = $conn->prepare($sqlHistory);
    $stmtHistory->bind_param("i", $userId);
    $stmtHistory->execute();
    $resHistory = $stmtHistory->get_result();

    $historySessions = [];
    while ($row = $resHistory->fetch_assoc()) {
        $fechaFormatted = date("d/m/Y h:i A", strtotime($row['ultimo_acceso']));
        
        $historySessions[] = [
            'id' => $row['id'],
            'dispositivo' => $row['dispositivo'],
            'ubicacion' => $row['ubicacion'],
            'fecha' => $fechaFormatted
        ];
    }
    $stmtHistory->close();

    echo json_encode([
        'success' => true,
        'profile' => $user,
        'sessions' => $activeSessions,    // Las activas
        'history' => $historySessions     // El historial nuevo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();
?>