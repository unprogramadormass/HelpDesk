<?php
// guardar_usuario.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once '../../conexion.php'; 

// --- CARGAR LIBRERÍAS DE PHPMAILER ---
// Asegúrate de que esta ruta sea correcta dependiendo de dónde esté tu archivo guardar_usuario.php
require_once(__DIR__ . '/../../libs/PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../../libs/PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../../libs/PHPMailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- FUNCIÓN PARA ENVIAR CORREO DE BIENVENIDA ---
function enviarCorreoBienvenida($emailDestino, $nombreUsuario, $usernameAcceso, $passwordAcceso) {
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

        $mail->setFrom('helpdeskcpsp@gmail.com', 'HelpDesk - Caja San Pablo');
        $mail->addAddress($emailDestino, $nombreUsuario);

        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido a HelpDesk - Tus credenciales de acceso';
        
        // Diseño del correo
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background-color: #2563eb; color: #ffffff; padding: 20px; text-align: center; }
                .content { padding: 30px; color: #334155; }
                .credentials-box { background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .credentials-box p { margin: 8px 0; font-size: 16px; }
                .warning { background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px; font-size: 14px;}
                .footer { background-color: #f1f5f9; text-align: center; padding: 15px; font-size: 12px; color: #64748b; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0; font-size:24px;'>¡Bienvenido a HelpDesk!</h1>
                </div>
                <div class='content'>
                    <h2 style='margin-top:0; color:#1e293b;'>Hola, {$nombreUsuario}</h2>
                    <p>Tu cuenta en el sistema HelpDesk de Caja San Pablo ha sido creada exitosamente. A continuación, te proporcionamos tus credenciales de acceso:</p>
                    
                    <div class='credentials-box'>
                        <p><strong>Usuario:</strong> {$usernameAcceso}</p>
                        <p><strong>Contraseña:</strong> {$passwordAcceso}</p>
                    </div>

                    <div class='warning'>
                        <strong>⚠️ Importante:</strong> Por motivos de seguridad, te recomendamos cambiar tu contraseña una vez que inicies sesión por primera vez.
                    </div>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " HelpDesk System - Caja San Pablo
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "¡Bienvenido a HelpDesk!\n\nTu cuenta ha sido creada. Aquí tienes tus credenciales:\nUsuario: {$usernameAcceso}\nContraseña: {$passwordAcceso}\n\nPor favor, cambia tu contraseña al iniciar sesión.";

        $mail->send();
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo de bienvenida. Mailer Error: {$mail->ErrorInfo}");
    }
}

$conn = getDatabaseConnection(); // Asegúrate de que esta función exista en tu conexion.php

try {
    // Leer JSON (incluye checkboxes como array)
    $input = json_decode(file_get_contents('php://input'), true);

    // Datos Básicos
    $id = $input['id'] ?? ''; // Para editar
    $firstname = $input['firstname'] ?? '';
    $secondname = $input['secondname'] ?? '';
    $firstapellido = $input['firstapellido'] ?? '';
    $secondapellido = $input['secondapellido'] ?? '';
    $email = $input['email'] ?? '';
    $celular = $input['celular'] ?? '';
    $extension = !empty($input['extension']) ? $input['extension'] : null;
    
    // Acceso
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? ''; // Solo si cambia o es nuevo
    
    // IDs de Relación
    $rol_id = $input['rol_id'] ?? null;
    $puesto_id = !empty($input['puesto_id']) ? $input['puesto_id'] : null;
    $sucursal_id = $input['sucursal_id'] ?? null;
    $incidencia_id = !empty($input['incidencia_id']) ? $input['incidencia_id'] : null;
    $status_id = $input['status_id'] ?? 1;

    // Notificaciones (Checkboxes booleanos)
    $noti_wa = !empty($input['noti_whatsapp']) ? 1 : 0;
    $noti_email = !empty($input['noti_email']) ? 1 : 0;
    $noti_nuevo = !empty($input['noti_nuevo']) ? 1 : 0;
    $noti_sis = !empty($input['noti_sistema']) ? 1 : 0;

    // --- ARREGLOS DE PERMISOS Y ÁREAS ---
    $permisos = $input['permisos'] ?? [];
    $area_ids = $input['area_ids'] ?? []; 

    if (empty($firstname) || empty($firstapellido) || empty($email) || empty($username) || empty($rol_id) || empty($sucursal_id)) {
        throw new Exception("Faltan campos obligatorios (*).");
    }

    $conn->begin_transaction();
    $esNuevoUsuario = false;

    if (!empty($id)) {
        // --- EDITAR ---
        $sql = "UPDATE usuarios SET firstname=?, secondname=?, firstapellido=?, secondapellido=?, email=?, celular=?, extension=?, username=?, tipo_usuario=?, puesto_id=?, sucursal_id=?, incidencia_id=?, estado_id=?, noti_whatsapp=?, noti_email=?, noti_nuevo=?, noti_sistema=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssisiiiiiiiiii", $firstname, $secondname, $firstapellido, $secondapellido, $email, $celular, $extension, $username, $rol_id, $puesto_id, $sucursal_id, $incidencia_id, $status_id, $noti_wa, $noti_email, $noti_nuevo, $noti_sis, $id);
        
        if (!$stmt->execute()) throw new Exception("Error al actualizar usuario: " . $stmt->error);
        $stmt->close();

        // Si hay password nuevo, actualizarlo aparte (hashing recomendado)
        if (!empty($password)) {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE usuarios SET password='$passHash' WHERE id=$id");
        }

        // Actualizar Permisos (Borrar viejos)
        $conn->query("DELETE FROM usuario_permisos WHERE usuario_id=$id");
        
        // Actualizar Áreas (Borrar viejas)
        $conn->query("DELETE FROM usuario_areas WHERE usuario_id=$id");

        $userId = $id;

    } else {
        // --- CREAR ---
        $esNuevoUsuario = true;
        if (empty($password)) throw new Exception("La contraseña es obligatoria para nuevos usuarios.");
        
        $passHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (firstname, secondname, firstapellido, secondapellido, email, celular, extension, username, password, tipo_usuario, puesto_id, sucursal_id, incidencia_id, estado_id, noti_whatsapp, noti_email, noti_nuevo, noti_sistema, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssissiiiiiiiii", $firstname, $secondname, $firstapellido, $secondapellido, $email, $celular, $extension, $username, $passHash, $rol_id, $puesto_id, $sucursal_id, $incidencia_id, $status_id, $noti_wa, $noti_email, $noti_nuevo, $noti_sis);
        
        if (!$stmt->execute()) throw new Exception("Error al crear usuario: " . $stmt->error);
        $userId = $conn->insert_id;
        $stmt->close();
    }

    // --- INSERTAR PERMISOS ---
    if (!empty($permisos) && is_array($permisos)) {
        $stmtPerm = $conn->prepare("INSERT INTO usuario_permisos (usuario_id, permiso_id) VALUES (?, ?)");
        foreach ($permisos as $permId) {
            $stmtPerm->bind_param("ii", $userId, $permId);
            $stmtPerm->execute();
        }
        $stmtPerm->close();
    }

    // --- INSERTAR ÁREAS MÚLTIPLES ---
    if (!empty($area_ids) && is_array($area_ids)) {
        $stmtArea = $conn->prepare("INSERT INTO usuario_areas (usuario_id, area_id) VALUES (?, ?)");
        foreach ($area_ids as $a_id) {
            $stmtArea->bind_param("ii", $userId, $a_id);
            $stmtArea->execute();
        }
        $stmtArea->close();
    }

    $conn->commit();

    // --- ENVIAR CORREO SI ES UN USUARIO NUEVO ---
    if ($esNuevoUsuario && !empty($email)) {
        $nombreCompleto = trim($firstname . ' ' . $firstapellido);
        enviarCorreoBienvenida($email, $nombreCompleto, $username, $password);
    }

    echo json_encode(['success' => true, 'message' => 'Usuario guardado correctamente.']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>