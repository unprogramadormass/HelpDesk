<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. CARGAMOS LAS VARIABLES DE ENTORNO PARA OCULTAR LA CONTRASEÑA
require_once '../../load_env.php';
require_once '../../conexion.php'; 

// --- CARGAR LIBRERÍAS DE PHPMAILER ---
require_once(__DIR__ . '/../../libs/PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../../libs/PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../../libs/PHPMailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']); exit;
}

$conn = getDatabaseConnection();
$userId = $_SESSION['user_id']; 

// --- FUNCIÓN PARA ENVIAR CORREO DE ASIGNACIÓN ---
function enviarCorreoAsignacion($emailDestino, $nombreUsuario, $folioTicket, $tituloTicket, $nombreAgente) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // SEGURIDAD: Usamos las variables del archivo .env
        $mail->Username   = $_ENV['MAIL_USER'];
        $mail->Password   = $_ENV['MAIL_PASS']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_USER'], 'HelpDesk San Pablo');
        $mail->addAddress($emailDestino, $nombreUsuario);

        $mail->isHTML(true);
        $mail->Subject = "Ticket Asignado: $folioTicket - $tituloTicket";
        
        $mail->Body = "
        <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
            <div style='background: #10b981; color: white; padding: 20px; text-align: center;'>
                <h2 style='margin:0;'>¡Tu ticket tiene un responsable!</h2>
            </div>
            <div style='padding: 30px; color: #333;'>
                <p>Hola <strong>$nombreUsuario</strong>,</p>
                <p>Te informamos que tu ticket ha sido tomado por uno de nuestros agentes para comenzar con su resolución.</p>
                
                <div style='background: #f9fafb; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>Ticket:</strong> $folioTicket</p>
                    <p style='margin: 5px 0;'><strong>Título:</strong> $tituloTicket</p>
                    <p style='margin: 5px 0;'><strong>Agente asignado:</strong> $nombreAgente</p>
                </div>

                <p style='font-size: 14px; color: #666;'>El agente se pondrá en contacto contigo a través de este sistema o vía extensión si es necesario.</p>
            </div>
            <div style='background: #f1f5f9; text-align: center; padding: 15px; font-size: 12px; color: #64748b;'>
                Este es un mensaje automático del Sistema de Tickets
            </div>
        </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo de asignación: " . $mail->ErrorInfo);
    }
}

try {
    if (isset($_POST['ticket_id'])) {
        $ticketId = (int)$_POST['ticket_id']; // Forzar entero por seguridad
        $newAgentId = $userId; 

        // 1. Obtener datos del ticket, del creador y del agente nuevo
        $sqlCheck = "SELECT t.agente_actual_id, t.folio, t.titulo, 
                            u.email as creator_email, u.firstname as creator_name,
                            a.firstname as agent_name, a.firstapellido as agent_lastname
                     FROM ticket t
                     INNER JOIN usuarios u ON t.usuario_creador_id = u.id
                     INNER JOIN usuarios a ON a.id = ?
                     WHERE t.id = ?";
        
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("ii", $newAgentId, $ticketId);
        $stmtCheck->execute();
        $data = $stmtCheck->get_result()->fetch_assoc();

        if ($data['agente_actual_id'] == $newAgentId) {
            echo json_encode(['success' => false, 'message' => 'Ya tienes este ticket asignado.']);
            exit;
        } elseif ($data['agente_actual_id'] != null) {
            echo json_encode(['success' => false, 'message' => 'Este ticket ya fue tomado por otro agente.']);
            exit;
        }

        // 2. Actualizar Ticket
        $sqlUp = "UPDATE ticket SET 
                    agente_anterior_id = agente_actual_id,
                    agente_actual_id = ?,
                    estado = 'Asignado', 
                    fecha_asignacion = NOW(),
                    fecha_ultima_actualizacion = NOW()
                  WHERE id = ?";
        
        $stmtUp = $conn->prepare($sqlUp);
        $stmtUp->bind_param("ii", $newAgentId, $ticketId);
        
        if ($stmtUp->execute()) {
            // 3. Historial
            $nombreAgenteFull = $data['agent_name'] . " " . $data['agent_lastname'];
            $desc = "El agente " . $nombreAgenteFull . " ha tomado este ticket."; 
            
            $stmtHist = $conn->prepare("INSERT INTO ticket_historial (ticket_id, usuario_responsable_id, tipo_movimiento, descripcion_evento) VALUES (?, ?, 'Asignación', ?)");
            $stmtHist->bind_param("iis", $ticketId, $userId, $desc);
            $stmtHist->execute();

            // 4. ENVIAR CORREO AL CREADOR
            if (!empty($data['creator_email'])) {
                enviarCorreoAsignacion(
                    $data['creator_email'], 
                    $data['creator_name'], 
                    $data['folio'], 
                    $data['titulo'], 
                    $nombreAgenteFull
                );
            }

            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error SQL: " . $stmtUp->error);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se recibió el ID del ticket.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>