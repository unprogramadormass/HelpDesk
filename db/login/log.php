<?php
ob_clean(); 
header('Content-Type: application/json; charset=utf-8');
session_start();

// 1. CARGAR LIBRERÍAS DE PHPMAILER
require_once(__DIR__ . '/../../db/libs/PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../../db/libs/PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../../db/libs/PHPMailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . '/../../db/conexion.php');

if (!$conn) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']));
}

// --- FUNCIÓN PARA OBTENER UBICACIÓN ---
function obtenerUbicacionPorIP($ip) {
    if ($ip == '::1' || $ip == '127.0.0.1') return "Localhost (Dev)"; 
    try {
        $ctx = stream_context_create(['http'=> ['timeout' => 2]]);
        $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,city,regionName", false, $ctx);
        if ($json) {
            $data = json_decode($json, true);
            if ($data && $data['status'] == 'success') return $data['city'] . ", " . $data['regionName'];
        }
    } catch (Exception $e) {}
    return "Ubicación Desconocida";
}

// --- FUNCIÓN PARA ENVIAR CORREO ---
function enviarAlertaCorreo($emailDestino, $nombreUsuario, $dispositivo, $ubicacion, $ip) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'helpdeskcpsp@gmail.com';
        $mail->Password   = 'xdsvmcxizlywhxmn'; // Tu contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('helpdeskcpsp@gmail.com', 'Seguridad HelpDesk');
        $mail->addAddress($emailDestino, $nombreUsuario);

        $mail->isHTML(true);
        $mail->Subject = 'Alerta de Seguridad: Nuevo inicio de sesión';
        
        $fecha = date("d/m/Y H:i:s");
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background-color: #2563eb; color: #ffffff; padding: 20px; text-align: center; }
                .content { padding: 30px; color: #334155; }
                .alert-box { background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .details { background-color: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; }
                .details p { margin: 8px 0; font-size: 14px; }
                .footer { background-color: #f1f5f9; text-align: center; padding: 15px; font-size: 12px; color: #64748b; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0; font-size:22px;'>Nuevo Inicio de Sesión</h1>
                </div>
                <div class='content'>
                    <h2 style='margin-top:0; color:#1e293b;'>Hola, {$nombreUsuario}</h2>
                    <p>Se ha detectado un nuevo acceso a tu cuenta de HelpDesk en un dispositivo o ubicación no registrados previamente.</p>
                    
                    <div class='details'>
                        <p><strong>🕒 Fecha:</strong> {$fecha}</p>
                        <p><strong>📱 Dispositivo:</strong> {$dispositivo}</p>
                        <p><strong>📍 Ubicación:</strong> {$ubicacion}</p>
                        <p><strong>🌐 IP:</strong> {$ip}</p>
                    </div>

                    <div class='alert-box'>
                        <strong style='color:#1e40af;'>¿Fuiste tú?</strong><br>
                        Si fuiste tú, puedes ignorar este correo. Si no reconoces esta actividad, por favor contacta al administrador de TI inmediatamente.
                    </div>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " HelpDesk System - Caja San Pablo
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Nuevo inicio de sesión detectado.\nFecha: $fecha\nDispositivo: $dispositivo\nIP: $ip\nSi no fuiste tú, contacta a soporte.";

        $mail->send();
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo. Mailer Error: {$mail->ErrorInfo}");
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = trim(filter_input(INPUT_POST, 'usern', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    $password = $_POST['pass'] ?? '';
    
    if (empty($username) || empty($password)) {
        die(json_encode(['success' => false, 'error' => 'Por favor, completa todos los campos']));
    }

    // Consulta para traer los datos del usuario
    $sql = "SELECT u.id, u.username, u.password, u.email, u.firstname, u.firstapellido, u.tipo_usuario, u.requiere_cambio_password, u.connected, r.redirect_url 
            FROM usuarios u
            INNER JOIN roles r ON u.tipo_usuario = r.id
            WHERE u.username = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) !== 1) {
        die(json_encode(['success' => false, 'error' => 'Usuario no encontrado']));
    }

    $user = mysqli_fetch_assoc($result);
    
    if (!password_verify($password, $user['password'])) {
        die(json_encode(['success' => false, 'error' => 'Contraseña incorrecta']));
    }

    // Actualizar estado de conexión
    $updateStmt = mysqli_prepare($conn, "UPDATE usuarios SET connected = 1 WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, "i", $user['id']);
    mysqli_stmt_execute($updateStmt);

    // Configurar Sesión
    session_regenerate_id(true);
    $token = session_id(); 
    $_SESSION['id_usuario'] = $user['id']; 
    $_SESSION['user_id'] = $user['id']; 
    $_SESSION['username'] = $user['username'];
    $_SESSION['rol'] = $user['tipo_usuario'];
    $_SESSION['token_sesion'] = $token; 
    $_SESSION['last_activity'] = time();

    // -------------------------------------------------------------------------
    // LOGICA DE SEGURIDAD Y REGISTRO DE SESIÓN
    // -------------------------------------------------------------------------
    try {
        // 1. Obtener y corregir la IP local
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        // 2. Obtener la huella del dispositivo (User Agent completo)
        $userAgentCompleto = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : 'Desconocido';
        
        // 3. Etiqueta amigable para correo/interfaz
        $tipoDispositivo = "PC";
        if (strpos($userAgentCompleto, 'Mobile') !== false) {
            $tipoDispositivo = "Móvil";
        }
        
        $ubicacion = obtenerUbicacionPorIP($ip);

        // 4. VERIFICAR SI ESTA IP Y NAVEGADOR YA SON CONOCIDOS
        $esNuevaConexion = true; // Por defecto es nuevo
        
        $sqlCheck = "SELECT id FROM sesiones_activas WHERE usuario_id = ? AND ip_address = ? AND user_agent = ? LIMIT 1";
        $stmtCheck = mysqli_prepare($conn, $sqlCheck);
        mysqli_stmt_bind_param($stmtCheck, "iss", $user['id'], $ip, $userAgentCompleto);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        
        // Si hay un registro previo, ya no es una conexión nueva
        if (mysqli_stmt_num_rows($stmtCheck) > 0) {
            $esNuevaConexion = false;
        }
        mysqli_stmt_close($stmtCheck);

        // 5. REGISTRAR LA NUEVA SESIÓN (Incluyendo el User-Agent)
        $sqlInsertSession = "INSERT INTO sesiones_activas (usuario_id, token_sesion, ip_address, dispositivo, user_agent, ubicacion, activo) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmtSession = mysqli_prepare($conn, $sqlInsertSession);
        mysqli_stmt_bind_param($stmtSession, "isssss", $user['id'], $token, $ip, $tipoDispositivo, $userAgentCompleto, $ubicacion);
        mysqli_stmt_execute($stmtSession);

        // 6. ENVIAR CORREO SOLO SI ES NUEVA CONEXIÓN
        if ($esNuevaConexion && !empty($user['email'])) {
            $nombreCompleto = $user['firstname'] . " " . $user['firstapellido'];
            enviarAlertaCorreo($user['email'], $nombreCompleto, $tipoDispositivo, $ubicacion, $ip);
        }

    } catch (Exception $e) { 
        error_log($e->getMessage()); 
    }

    // Redireccionar
    $redirectUrl = ($user['requiere_cambio_password'] == 1) ? '/helpdesk/pages/auth/pass/changepass.html' : ($user['redirect_url'] ?? '/helpdesk/pages/usr/user/index.php');
    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
    exit;
}
?>