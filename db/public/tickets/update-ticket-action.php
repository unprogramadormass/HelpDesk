<?php
// db/tickets/update-ticket-action.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// SEGURIDAD: Cargar entorno para ocultar contraseñas
require_once '../../load_env.php';
require_once '../../conexion.php'; 
require_once(__DIR__ . '/../../libs/PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../../libs/PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../../libs/PHPMailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

$conn = getDatabaseConnection();
$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// --- FUNCIÓN CORREO ---
function enviarCorreoActualizacion($emailDestino, $nombreUsuario, $folioTicket, $tituloTicket, $nombreAgenteNuevo, $cambios, $nombreAgenteAnterior = null, $esReasignacion = false) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // CREDENCIALES OCULTAS
        $mail->Username   = $_ENV['MAIL_USER'];
        $mail->Password   = $_ENV['MAIL_PASS']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_USER'], 'HelpDesk San Pablo');
        $mail->addAddress($emailDestino, $nombreUsuario);

        $mail->isHTML(true);
        $mail->Subject = "Actualización en tu Ticket: $folioTicket";

        // Generar la lista de cambios para el correo (<li>)
        $listaCambiosHTML = "";
        foreach ($cambios as $cambio) {
            $listaCambiosHTML .= "<li style='margin-bottom: 8px;'>$cambio</li>";
        }

        // Lógica visual dependiendo si es reasignación o actualización normal
        $infoAgentesHTML = "";
        if ($esReasignacion) {
            $infoAgentesHTML = "
                <p style='margin: 5px 0;'><strong>Agente anterior:</strong> " . ($nombreAgenteAnterior ?: 'Sin asignar') . "</p>
                <p style='margin: 5px 0;'><strong>Agente reasignado:</strong> $nombreAgenteNuevo</p>
            ";
        } else {
            $infoAgentesHTML = "
                <p style='margin: 5px 0;'><strong>Agente asignado:</strong> $nombreAgenteNuevo</p>
            ";
        }
        
        $mail->Body = "
        <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
            <div style='background: #3b82f6; color: white; padding: 20px; text-align: center;'>
                <h2 style='margin:0;'>Hay novedades en tu ticket</h2>
            </div>
            <div style='padding: 30px; color: #333;'>
                <p>Hola <strong>$nombreUsuario</strong>,</p>
                <p>Te informamos que se han registrado actualizaciones en tu ticket de soporte:</p>
                
                <div style='background: #f9fafb; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #e5e7eb;'>
                    <p style='margin: 5px 0;'><strong>Ticket:</strong> $folioTicket</p>
                    <p style='margin: 5px 0;'><strong>Título:</strong> $tituloTicket</p>
                    $infoAgentesHTML
                </div>

                <h3 style='color: #1f2937; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 5px;'>Cambios realizados:</h3>
                <ul style='background: #eff6ff; padding: 15px 15px 15px 35px; border-radius: 8px; color: #1e40af;'>
                    $listaCambiosHTML
                </ul>

                <p style='font-size: 14px; color: #666; margin-top: 20px;'>Si tienes dudas, puedes consultar más detalles o agregar comentarios ingresando al sistema HelpDesk.</p>
            </div>
            <div style='background: #f1f5f9; text-align: center; padding: 15px; font-size: 12px; color: #64748b;'>
                Este es un mensaje automático del Sistema de Tickets - Caja San Pablo
            </div>
        </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo de actualización: " . $mail->ErrorInfo);
    }
}

// =================================================================================
// FUNCIÓN NUEVA: PARA ENVIAR CORREO DE NUEVO COMENTARIO
// =================================================================================
function enviarCorreoComentario($emailDestino, $nombreUsuario, $folioTicket, $tituloTicket, $nombreComentarista, $comentario) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USER'];
        $mail->Password   = $_ENV['MAIL_PASS']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_USER'], 'Notificaciones HelpDesk');
        $mail->addAddress($emailDestino, $nombreUsuario);

        $mail->isHTML(true);
        $mail->Subject = "Nuevo mensaje en el Ticket: $folioTicket";
        
        $year = date('Y');
        $mail->Body = "
        <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
            <div style='background: #3b82f6; color: white; padding: 20px; text-align: center;'>
                <h2 style='margin:0;'>Nuevo comentario en tu ticket</h2>
            </div>
            <div style='padding: 30px; color: #374151;'>
                <p>Hola <strong>{$nombreUsuario}</strong>,</p>
                <p>El usuario <strong>{$nombreComentarista}</strong> ha agregado una nueva respuesta al ticket <strong>{$folioTicket} - {$tituloTicket}</strong>:</p>
                
                <div style='background: #f3f4f6; padding: 15px; border-left: 4px solid #3b82f6; border-radius: 4px; margin: 20px 0; font-style: italic; color: #1f2937;'>
                    \"{$comentario}\"
                </div>

                <p style='font-size: 14px; color: #6b7280;'>Por favor, ingresa al sistema HelpDesk para ver los detalles y continuar la conversación.</p>
            </div>
            <div style='background: #f8fafc; text-align: center; padding: 15px; font-size: 12px; color: #94a3b8;'>
                &copy; {$year} Sistema HelpDesk
            </div>
        </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo de comentario: " . $mail->ErrorInfo);
    }
}


try {
    if ($action === 'update_ticket') {
        $ticketId = (int)$_POST['ticket_id']; // SANITIZADO
        
        // 1. OBTENER DATOS ACTUALES + DATOS DEL CREADOR + NOMBRE DEL AGENTE ANTERIOR
        $checkSql = "SELECT t.agente_actual_id, t.estado, t.prioridad, t.folio, t.titulo, 
                            u.email as creator_email, u.firstname as creator_name,
                            a.firstname as old_ag_fname, a.firstapellido as old_ag_lname
                     FROM ticket t
                     LEFT JOIN usuarios u ON t.usuario_creador_id = u.id
                     LEFT JOIN usuarios a ON t.agente_actual_id = a.id
                     WHERE t.id = ?";
        $stmtCh = $conn->prepare($checkSql);
        $stmtCh->bind_param("i", $ticketId);
        $stmtCh->execute();
        $currentData = $stmtCh->get_result()->fetch_assoc();

        // Regla: Solo si soy el agente asignado
        if ($currentData['agente_actual_id'] != $userId) {
            throw new Exception("No tienes permiso para modificar este ticket.");
        }

        // Armamos el nombre del agente anterior
        $nombreAgenteAnterior = "Sin asignar";
        if (!empty($currentData['old_ag_fname'])) {
            $nombreAgenteAnterior = trim($currentData['old_ag_fname'] . " " . $currentData['old_ag_lname']);
        }

        // 2. OBTENER DATOS DEL FORMULARIO
        $estado = $_POST['estado'];
        $prioridad = $_POST['prioridad'];
        $agenteId = !empty($_POST['agente_id']) ? $_POST['agente_id'] : NULL;

        // 3. BUSCAR EL NOMBRE REAL DEL AGENTE NUEVO/ACTUAL
        $nombreAgenteNuevo = "Sin asignar";
        if ($agenteId) {
            $sqlAg = "SELECT CONCAT(firstname, ' ', firstapellido) as nombre FROM usuarios WHERE id = ?";
            $stmtAg = $conn->prepare($sqlAg);
            $stmtAg->bind_param("i", $agenteId);
            $stmtAg->execute();
            $agData = $stmtAg->get_result()->fetch_assoc();
            if ($agData) {
                $nombreAgenteNuevo = $agData['nombre'];
            }
        }

        // 4. DETECTAR CAMBIOS (Dos listas: una para la BD, otra para el correo)
        $cambiosDB = [];
        $cambiosCorreo = [];
        $esReasignacion = false; // Bandera para saber si enviar la plantilla especial

        if ($currentData['estado'] !== $estado) {
            $cambiosDB[] = "Estado: $estado";
            $cambiosCorreo[] = "El estado del ticket cambió a: <strong>$estado</strong>";
        }
        if ($currentData['prioridad'] !== $prioridad) {
            $cambiosDB[] = "Prioridad: $prioridad";
            $cambiosCorreo[] = "La prioridad cambió a: <strong>$prioridad</strong>";
        }
        
        // Detectar Reasignación
        if ($currentData['agente_actual_id'] != $agenteId) {
            $esReasignacion = true;
            $cambiosDB[] = "Agente reasignado de {$nombreAgenteAnterior} a {$nombreAgenteNuevo}";
            $cambiosCorreo[] = "El agente anterior <strong>{$nombreAgenteAnterior}</strong> reasignó tu ticket al nuevo agente <strong>{$nombreAgenteNuevo}</strong>.";
        }

        // 5. LÓGICA DE FECHAS
        $fechaCierre = ($estado == 'Resuelto' || $estado == 'Cerrado') ? date('Y-m-d H:i:s') : NULL;

        // 6. UPDATE COMPLETO (Se agregó fecha_reasignacion)
        $sqlUpdate = "UPDATE ticket SET 
                        estado = ?, 
                        prioridad = ?, 
                        agente_anterior_id = IF(? != IFNULL(agente_actual_id, 0), agente_actual_id, agente_anterior_id),
                        agente_actual_id = ?, 
                        fecha_asignacion = IF(? IS NOT NULL AND agente_actual_id IS NULL, NOW(), fecha_asignacion),
                        fecha_reasignacion = IF(? != IFNULL(agente_actual_id, 0), NOW(), fecha_reasignacion),
                        fecha_ultima_actualizacion = NOW(),
                        fecha_cierre = ?
                      WHERE id = ?";
        
        $stmtUp = $conn->prepare($sqlUpdate);
        
        // IMPORTANTE: Ahora son 8 parámetros (ssiiiisi)
        // estado(s), prioridad(s), agenteId(i), agenteId(i), agenteId(i), agenteId(i), fechaCierre(s), ticketId(i)
        $stmtUp->bind_param("ssiiiisi", 
            $estado, 
            $prioridad, 
            $agenteId, // Para revisar agente_anterior_id
            $agenteId, // Para establecer agente_actual_id
            $agenteId, // Para verificar si era nulo (fecha_asignacion)
            $agenteId, // Para actualizar fecha_reasignacion si cambió
            $fechaCierre, 
            $ticketId  
        );
        
        if ($stmtUp->execute()) {
            // 7. INSERTAR EN HISTORIAL Y ENVIAR CORREO (Solo si hubo cambios)
            if (!empty($cambiosDB)) {
                
                // A) Guardar en Base de Datos
                $desc = "Actualización: " . implode(" | ", $cambiosDB);
                $sqlHist = "INSERT INTO ticket_historial (ticket_id, usuario_responsable_id, tipo_movimiento, descripcion_evento) 
                            VALUES (?, ?, 'Actualización', ?)";
                $stmtH = $conn->prepare($sqlHist);
                $stmtH->bind_param("iis", $ticketId, $userId, $desc);
                $stmtH->execute();

                // B) Enviar Correo al Creador del ticket
                if (!empty($currentData['creator_email'])) {
                    enviarCorreoActualizacion(
                        $currentData['creator_email'],
                        $currentData['creator_name'],
                        $currentData['folio'],
                        $currentData['titulo'],
                        $nombreAgenteNuevo,
                        $cambiosCorreo,
                        $nombreAgenteAnterior,
                        $esReasignacion
                    );
                }
            }
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error al actualizar la base de datos: " . $stmtUp->error);
        }

    } elseif ($action === 'add_comment') {
        $ticketId = (int)$_POST['ticket_id']; // PREVIENE INYECCIÓN SQL
        $comment = htmlspecialchars(trim($_POST['comment']), ENT_QUOTES, 'UTF-8'); // PREVIENE XSS

        if (empty($comment)) throw new Exception("El comentario no puede estar vacío");

        // VALIDAR PROPIEDAD DEL TICKET (Previene IDOR)
        $sqlCheckAccess = "SELECT id FROM ticket WHERE id = ? AND (usuario_creador_id = ? OR agente_actual_id = ?)";
        $stmtAcc = $conn->prepare($sqlCheckAccess);
        $stmtAcc->bind_param("iii", $ticketId, $userId, $userId);
        $stmtAcc->execute();
        if ($stmtAcc->get_result()->num_rows === 0) {
            // Si el usuario no es el creador ni el agente (y no es Admin), se bloquea.
            if($_SESSION['rol'] != 1) { 
                throw new Exception("No tienes permisos para comentar en este ticket.");
            }
        }
        $stmtAcc->close();

        // GUARDAR COMENTARIO
        $sqlComm = "INSERT INTO ticket_historial (ticket_id, usuario_responsable_id, tipo_movimiento, descripcion_evento) VALUES (?, ?, 'Comentario', ?)";
        $stmtComm = $conn->prepare($sqlComm);
        $stmtComm->bind_param("iis", $ticketId, $userId, $comment);
        
        if ($stmtComm->execute()) {
            // PREVENCIÓN DE INYECCIÓN SQL PARCHEADA AQUÍ:
            $stmtUpdDate = $conn->prepare("UPDATE ticket SET fecha_ultima_actualizacion = NOW() WHERE id = ?");
            $stmtUpdDate->bind_param("i", $ticketId);
            $stmtUpdDate->execute();
            $stmtUpdDate->close();
            
            // =====================================================================
            // NUEVO: LÓGICA DE NOTIFICACIONES (¿Quién le habla a quién?)
            // =====================================================================
            $sqlData = "SELECT t.folio, t.titulo, t.usuario_creador_id, t.agente_actual_id,
                               uc.email as creador_email, uc.firstname as creador_nombre, uc.firstapellido as creador_apellido,
                               ua.email as agente_email, ua.firstname as agente_nombre, ua.firstapellido as agente_apellido,
                               u_actor.firstname as actor_nombre, u_actor.firstapellido as actor_apellido
                        FROM ticket t
                        LEFT JOIN usuarios uc ON t.usuario_creador_id = uc.id
                        LEFT JOIN usuarios ua ON t.agente_actual_id = ua.id
                        LEFT JOIN usuarios u_actor ON u_actor.id = ?
                        WHERE t.id = ?";
            $stmtData = $conn->prepare($sqlData);
            $stmtData->bind_param("ii", $userId, $ticketId);
            $stmtData->execute();
            $ticketInfo = $stmtData->get_result()->fetch_assoc();
            $stmtData->close();

            if ($ticketInfo) {
                $actorNombre = trim($ticketInfo['actor_nombre'] . ' ' . $ticketInfo['actor_apellido']);
                $creadorNombre = trim($ticketInfo['creador_nombre'] . ' ' . $ticketInfo['creador_apellido']);
                $agenteNombre = trim($ticketInfo['agente_nombre'] . ' ' . $ticketInfo['agente_apellido']);

                // CASO A: Si el que comenta es el CREADOR, le mandamos el correo al AGENTE
                if ($userId == $ticketInfo['usuario_creador_id'] && !empty($ticketInfo['agente_email'])) {
                    enviarCorreoComentario($ticketInfo['agente_email'], $agenteNombre, $ticketInfo['folio'], $ticketInfo['titulo'], $actorNombre, $comment);
                }
                // CASO B: Si el que comenta es el AGENTE, le mandamos el correo al CREADOR
                elseif ($userId == $ticketInfo['agente_actual_id'] && !empty($ticketInfo['creador_email'])) {
                    enviarCorreoComentario($ticketInfo['creador_email'], $creadorNombre, $ticketInfo['folio'], $ticketInfo['titulo'], $actorNombre, $comment);
                }
                // CASO C: Si el que comenta es un TERCERO (Ej. Un Administrador interviniendo)
                elseif ($userId != $ticketInfo['usuario_creador_id'] && $userId != $ticketInfo['agente_actual_id']) {
                    if (!empty($ticketInfo['creador_email'])) {
                        enviarCorreoComentario($ticketInfo['creador_email'], $creadorNombre, $ticketInfo['folio'], $ticketInfo['titulo'], $actorNombre, $comment);
                    }
                    if (!empty($ticketInfo['agente_email'])) {
                        enviarCorreoComentario($ticketInfo['agente_email'], $agenteNombre, $ticketInfo['folio'], $ticketInfo['titulo'], $actorNombre, $comment);
                    }
                }
            }
            
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error al guardar comentario");
        }

    } else {
        throw new Exception("Acción no válida");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>