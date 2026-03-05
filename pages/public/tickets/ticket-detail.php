<?php
// ticket-detail.php
session_start();

/**
 * 1. SEGURIDAD: DESACTIVAR ERRORES EN PANTALLA
 * Esto evita que rutas internas o nombres de tablas se filtren en caso de error.
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once '../../../db/conexion.php'; 
$conn = getDatabaseConnection();

// 2. VALIDACIÓN DE ACCESO BÁSICA
if (!isset($_SESSION['user_id'])) {
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success'=>false, 'message'=>'Sesión expirada']); exit;
    }
    header("Location: /HelpDesk/index.php"); 
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("ID de ticket inválido.");

$ticket_id = (int)$_GET['id']; // Forzamos entero para seguridad SQL
$user_id = (int)$_SESSION['user_id'];

// 3. OBTENER ROL ACTUAL
$stmtUser = $conn->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();

if (!$userData) {
    die("Error fatal: El usuario actual no existe en la base de datos.");
}

$currentUserRole = $userData['tipo_usuario']; 
$isStaff = in_array($currentUserRole, [1, 2, 3]); // Admin, Sup, Agente

// 4. CONSULTA EXPANDIDA DEL TICKET
$sqlTicket = "SELECT t.*, 
    ts.nombre as ticket_sucursal,
    ta.nombre as ticket_area,
    uc.firstname as c_n1, uc.secondname as c_n2, uc.firstapellido as c_a1, uc.secondapellido as c_a2,
    uc.email as c_email, uc.avatar as c_avatar, uc.celular as c_celular, uc.extension as c_extension,
    rc.nombre as c_rol,
    ua.firstname as a_n1, ua.secondname as a_n2, ua.firstapellido as a_a1, ua.secondapellido as a_a2,
    ua.email as a_email, ua.avatar as a_avatar,
    ra.nombre as a_rol
    FROM ticket t
    LEFT JOIN sucursales ts ON t.sucursal_id = ts.id
    LEFT JOIN areas ta ON t.area_id = ta.id
    LEFT JOIN usuarios uc ON t.usuario_creador_id = uc.id
    LEFT JOIN roles rc ON uc.tipo_usuario = rc.id
    LEFT JOIN usuarios ua ON t.agente_actual_id = ua.id
    LEFT JOIN roles ra ON ua.tipo_usuario = ra.id
    WHERE t.id = ?";

$stmt = $conn->prepare($sqlTicket);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$resultTicket = $stmt->get_result();

if (!$resultTicket) {
    die("Error en la consulta del ticket.");
}

$ticket = $resultTicket->fetch_assoc();

if (!$ticket) die("Ticket no encontrado.");

/**
 * 5. VALIDACIÓN DE PERMISOS (ANT-IDOR)
 * Evita que un usuario vea un ticket que no le pertenece.
 */
if ($isStaff) {
    // Si es un Agente (3) y el ticket NO está asignado a él
    if ($currentUserRole == 3 && $ticket['agente_actual_id'] != $user_id) {
        header("Location: /HelpDesk/pages/usr/soporte/index.php");
        exit;
    }
} else {
    // Si es un Usuario normal y el ticket NO es de su autoría
    if ($ticket['usuario_creador_id'] != $user_id) {
        header("Location: /HelpDesk/pages/usr/user/index.php");
        exit;
    }
}

$canEdit = ($ticket['agente_actual_id'] == $user_id) ? true : false;

// 4.5. EXTRAER EVIDENCIAS
$evidencias = [];
if (isset($ticket['evidence']) && !empty($ticket['evidence'])) {
    $evidencias = json_decode($ticket['evidence'], true) ?: [];
}

// 6. HELPERS
function showVal($val) { return !empty($val) ? htmlspecialchars($val) : '<span class="italic text-slate-400 text-xs">Sin información</span>'; }
function buildName($n1, $n2, $a1, $a2) { $full = trim("$n1 $n2 $a1 $a2"); return !empty($full) ? htmlspecialchars($full) : '<span class="italic text-slate-400">Desconocido</span>'; }

// 7. HISTORIAL (Corregido para seguridad)
$sqlHist = "SELECT th.*, CONCAT(u.firstname, ' ', u.firstapellido) as resp_nombre, u.avatar as resp_avatar
            FROM ticket_historial th JOIN usuarios u ON th.usuario_responsable_id = u.id
            WHERE th.ticket_id = ? ORDER BY th.fecha_movimiento DESC";
$stmtH = $conn->prepare($sqlHist);
$stmtH->bind_param("i", $ticket_id);
$stmtH->execute();
$historial = $stmtH->get_result();

// 8. AGENTES
$agentesList = [];
if ($canEdit) {
    $resA = $conn->query("SELECT id, CONCAT(firstname, ' ', firstapellido) as nombre FROM usuarios WHERE tipo_usuario IN (1,2,3) AND estado_id=1");
    if ($resA) {
        while($row = $resA->fetch_assoc()) { $agentesList[] = $row; }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details <?php echo htmlspecialchars($ticket['folio']); ?> | HelpDesk</title>
    <link rel="icon" href="/HelpDesk/assets/img/public/ICO.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { darkbg: '#0f172a', darkcard: '#1e293b' } } }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .theme-trans { transition: background-color 0.3s, color 0.3s; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.5); border-radius: 20px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-darkbg dark:text-slate-200 min-h-screen flex flex-col theme-trans">

    <header class="h-16 bg-white dark:bg-darkcard shadow-sm flex items-center justify-between px-4 md:px-8 border-b border-slate-100 dark:border-slate-700 sticky top-0 z-20">
        <div class="flex items-center gap-4">
            <button onclick="window.history.back()" class="text-slate-400 hover:text-blue-600 dark:hover:text-white transition">
                <i class="fas fa-arrow-left text-lg"></i>
            </button>
            <div>
                <h1 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-3">
                    <?php echo htmlspecialchars($ticket['folio']); ?>
                    <span class="hidden sm:inline-block h-4 w-px bg-slate-300 dark:bg-slate-600"></span>
                    <span class="hidden sm:block text-sm font-normal text-slate-500 dark:text-slate-400 truncate max-w-xs">
                        <?php echo htmlspecialchars($ticket['titulo']); ?>
                    </span>
                </h1>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <button id="theme-toggle" class="p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 focus:outline-none transition">
                <i id="theme-icon" class="fas fa-moon text-lg"></i>
            </button>
            
            <?php
                $statusStyles = [
                    'Abierto'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                    'Asignado'   => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
                    'En Proceso' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-400 border-cyan-200 dark:border-cyan-800',
                    'Espera'     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                    'Resuelto'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                    'Cerrado'    => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400 border-gray-200 dark:border-gray-700',
                    'Cancelado'  => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border-rose-200 dark:border-rose-800',
                ];
                $bgState = $statusStyles[$ticket['estado']] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';

                $prioStyles = [
                    'Baja'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'Media'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'Alta'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                    'Crítica' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 font-extrabold',
                ];
                $bgPrio = $prioStyles[$ticket['prioridad']] ?? 'bg-slate-100 text-slate-700';
            ?>
            
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border border-transparent <?php echo $bgPrio; ?>">
                <?php echo htmlspecialchars($ticket['prioridad']); ?>
            </span>

            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border <?php echo $bgState; ?>">
                <?php echo htmlspecialchars($ticket['estado']); ?>
            </span>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm p-6 border border-slate-100 dark:border-slate-700">
                <h2 class="font-bold text-lg mb-4 text-slate-800 dark:text-white">Detalle del Incidente</h2>
                <div class="prose dark:prose-invert max-w-none text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($ticket['descripcion']); ?></div>
            </div>

            <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2"><i class="fas fa-paperclip text-slate-400"></i> Evidencias Adjuntas</h3>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <?php if(empty($evidencias)): ?>
                        <div class="p-4 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg text-center text-slate-400 text-xs col-span-full">Sin archivos adjuntos</div>
                    <?php else: ?>
                        <?php foreach($evidencias as $ev): 
                            $ext = strtolower(pathinfo($ev, PATHINFO_EXTENSION));
                            $fileUrl = "/HelpDesk/uploads/" . htmlspecialchars($ev);
                            $fancyboxAttr = ""; $actionText = "Descargar"; $actionIcon = "fas fa-download";
                            $icon = 'fas fa-file text-slate-400';

                            if(in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                                $icon = 'fas fa-image text-blue-500'; $fancyboxAttr = 'data-fancybox="gallery" data-type="image"'; $actionText = "Vista Previa"; $actionIcon = "fas fa-eye";
                            } else if($ext == 'pdf') {
                                $icon = 'fas fa-file-pdf text-red-500'; $fancyboxAttr = 'data-fancybox="gallery" data-type="pdf"'; $actionText = "Ver PDF"; $actionIcon = "fas fa-eye";
                            }
                        ?>
                        <a href="<?php echo $fileUrl; ?>" <?php echo $fancyboxAttr; ?> class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-3 rounded-lg shadow-sm hover:border-blue-400 transition group relative overflow-hidden cursor-pointer">
                            <i class="<?php echo $icon; ?> text-2xl group-hover:scale-110 transition-transform"></i>
                            <div class="flex flex-col min-w-0">
                                <span class="truncate text-xs font-bold text-slate-700 dark:text-slate-300" title="<?php echo htmlspecialchars($ev); ?>"><?php echo htmlspecialchars($ev); ?></span>
                                <span class="text-[10px] text-slate-400 uppercase mt-0.5 group-hover:text-blue-500"><i class="<?php echo $actionIcon; ?> mr-1"></i><?php echo $actionText; ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                    <h3 class="font-bold text-slate-700 dark:text-slate-200 text-sm">Actividad y Comentarios</h3>
                </div>
                <div class="p-6 space-y-6 max-h-[500px] overflow-y-auto custom-scrollbar">
                    <?php while($h = $historial->fetch_assoc()): ?>
                        <?php 
                            $avt = $h['resp_avatar'] ? "/HelpDesk/assets/img/avatars/".$h['resp_avatar'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=".urlencode($h['resp_nombre']);
                            $date = date("d M, h:i A", strtotime($h['fecha_movimiento']));
                        ?>
                        <?php if($h['tipo_movimiento'] === 'Comentario'): ?>
                            <div class="flex gap-4 animate-fade-in">
                                <img src="<?php echo $avt; ?>" class="w-10 h-10 rounded-full border shrink-0">
                                <div class="flex-1">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <span class="font-bold text-sm text-slate-700 dark:text-white"><?php echo htmlspecialchars($h['resp_nombre']); ?></span>
                                        <span class="text-xs text-slate-400"><?php echo $date; ?></span>
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-800 p-3 rounded-xl border text-sm text-slate-600 dark:text-slate-300">
                                        <?php echo htmlspecialchars($h['descripcion_evento']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-4 justify-center py-2 opacity-60">
                                <div class="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 px-3 py-1 rounded-full border">
                                    <?php echo htmlspecialchars($h['descripcion_evento']); ?> • <?php echo $date; ?>
                                </span>
                                <div class="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-700">
                    <form id="comment-form" class="flex gap-4">
                        <input type="hidden" name="action" value="add_comment">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                        <textarea name="comment" rows="2" placeholder="Escribe un comentario..." class="w-full bg-white dark:bg-slate-900 border rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none dark:text-white resize-none" required></textarea>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-lg font-bold transition self-end h-10 flex items-center"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <?php if($canEdit): ?>
            <div class="bg-white dark:bg-darkcard rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 p-6">
                <h3 class="font-bold text-slate-700 dark:text-white mb-4 uppercase text-xs tracking-wider border-b pb-2">Gestión del Ticket</h3>
                <form id="manage-form" class="space-y-4">
                    <input type="hidden" name="action" value="update_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-500 dark:text-slate-400">Estado</label>
                        <select name="estado" class="w-full border rounded-lg p-2.5 dark:bg-slate-800 dark:text-white">
                            <?php foreach(['Abierto','Asignado','En Proceso','Espera','Resuelto','Cerrado','Cancelado'] as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo $st == $ticket['estado'] ? 'selected' : ''; ?>><?php echo $st; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-500 dark:text-slate-400">Prioridad</label>
                        <select name="prioridad" class="w-full border rounded-lg p-2.5 dark:bg-slate-800 dark:text-white">
                            <option value="Baja" class="text-green-600 font-bold" <?php echo $ticket['prioridad']=='Baja'?'selected':''; ?>>Baja</option>
                            <option value="Media" class="text-orange-500 font-bold" <?php echo $ticket['prioridad']=='Media'?'selected':''; ?>>Media</option>
                            <option value="Alta" class="text-red-500 font-bold" <?php echo $ticket['prioridad']=='Alta'?'selected':''; ?>>Alta</option>
                            <option value="Crítica" class="text-red-700 font-extrabold" <?php echo $ticket['prioridad']=='Crítica'?'selected':''; ?>>Crítica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-500 dark:text-slate-400">Reasignar Agente</label>
                        <div class="flex gap-2">
                            <select name="agente_id" id="agente-select" class="w-full border rounded-lg p-2.5 dark:bg-slate-800 dark:text-white">
                                <option value="">-- Sin Asignar --</option>
                                <?php foreach($agentesList as $ag): ?>
                                    <option value="<?php echo $ag['id']; ?>" <?php echo $ag['id'] == $ticket['agente_actual_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ag['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" onclick="assignToMe()" class="bg-slate-100 dark:bg-slate-700 p-2.5 rounded-lg transition"><i class="fas fa-hand-paper"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition mt-2">Guardar Cambios</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                <?php if ($isStaff): ?>
                    <h3 class="font-bold text-slate-700 dark:text-white mb-6 uppercase text-xs tracking-wider border-b pb-2">Información Solicitante</h3>
                    <div class="flex flex-col items-center mb-6">
                        <?php 
                            $cAvatar = $ticket['c_avatar'] ? "/HelpDesk/assets/img/avatars/".$ticket['c_avatar'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=".urlencode($ticket['c_n1']);
                            $cNombre = buildName($ticket['c_n1'], $ticket['c_n2'], $ticket['c_a1'], $ticket['c_a2']);
                        ?>
                        <img src="<?php echo $cAvatar; ?>" class="w-20 h-20 rounded-full border-4 mb-2 object-cover">
                        <p class="font-bold text-slate-800 dark:text-white text-center"><?php echo $cNombre; ?></p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-full mt-1"><?php echo showVal($ticket['c_rol']); ?></p>
                    </div>
                    <div class="space-y-4 text-sm">
                        <div><span class="block text-xs text-slate-400 uppercase">Correo Electrónico</span><a href="mailto:<?php echo $ticket['c_email']; ?>" class="text-blue-500 hover:underline break-words block"><?php echo showVal($ticket['c_email']); ?></a></div>
                        <div><span class="block text-xs text-slate-400 uppercase">Extensión</span><span class="font-medium text-slate-700 dark:text-slate-200 block"><?php echo showVal($ticket['c_extension']); ?></span></div>
                        <div><span class="block text-xs text-slate-400 uppercase">Teléfono / Celular</span><span class="font-medium text-slate-700 dark:text-slate-200 block"><?php echo showVal($ticket['c_celular']); ?></span></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><span class="block text-xs text-slate-400 uppercase">Sucursal</span><span class="font-medium text-slate-700 dark:text-slate-200 block truncate"><?php echo showVal($ticket['ticket_sucursal']); ?></span></div>
                            <div><span class="block text-xs text-slate-400 uppercase">Área Destino</span><span class="font-medium text-slate-700 dark:text-slate-200 block truncate"><?php echo showVal($ticket['ticket_area']); ?></span></div>
                        </div>
                    </div>
                <?php else: ?>
                    <h3 class="font-bold text-slate-700 dark:text-white mb-6 uppercase text-xs tracking-wider border-b pb-2">Agente Asignado</h3>
                    <?php if($ticket['agente_actual_id']): ?>
                        <?php 
                            $aAvatar = $ticket['a_avatar'] ? "/HelpDesk/assets/img/avatars/".$ticket['a_avatar'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=".urlencode($ticket['a_n1']);
                            $aNombre = buildName($ticket['a_n1'], $ticket['a_n2'], $ticket['a_a1'], $ticket['a_a2']);
                        ?>
                        <div class="flex flex-col items-center mb-6">
                            <img src="<?php echo $aAvatar; ?>" class="w-20 h-20 rounded-full border-4 mb-2 object-cover">
                            <p class="font-bold text-slate-800 dark:text-white text-center"><?php echo $aNombre; ?></p>
                            <p class="text-xs text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2 py-0.5 rounded-full mt-1"><?php echo showVal($ticket['a_rol']); ?></p>
                        </div>
                        <div class="space-y-4 text-sm">
                            <div><span class="block text-xs text-slate-400 uppercase">Correo de Soporte</span><span class="text-slate-700 dark:text-slate-200 break-words block"><?php echo showVal($ticket['a_email']); ?></span></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><span class="block text-xs text-slate-400 uppercase">Sucursal</span><span class="font-medium text-slate-700 dark:text-slate-200 block truncate"><?php echo showVal($ticket['ticket_sucursal']); ?></span></div>
                                <div><span class="block text-xs text-slate-400 uppercase">Área Ticket</span><span class="font-medium text-slate-700 dark:text-slate-200 block truncate"><?php echo showVal($ticket['ticket_area']); ?></span></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-slate-400"><div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3"><i class="fas fa-user-clock text-3xl"></i></div><p class="text-sm font-medium">Buscando agente disponible...</p></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        Fancybox.bind('[data-fancybox="gallery"]', {
            Toolbar: { display: { left: ["infobar"], middle: ["zoomIn","zoomOut","toggle1to1","rotateCCW","rotateCW","flipX","flipY"], right: ["slideshow", "thumbs", "close"] } }
        });

        const CURRENT_USER_ID = <?php echo (int)$user_id; ?>;
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const themeIcon = document.getElementById('theme-icon');

        function applyTheme(isDark) {
            if (isDark) { html.classList.add('dark'); themeIcon.classList.replace('fa-moon', 'fa-sun'); }
            else { html.classList.remove('dark'); themeIcon.classList.replace('fa-sun', 'fa-moon'); }
        }

        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) { applyTheme(true); } else { applyTheme(false); }
        themeToggle.addEventListener('click', () => { const isDark = !html.classList.contains('dark'); applyTheme(isDark); localStorage.theme = isDark ? 'dark' : 'light'; });

        function assignToMe() { const select = document.getElementById('agente-select'); if(select) select.value = CURRENT_USER_ID; }

        async function handleFormSubmit(formId) {
            const form = document.getElementById(formId);
            if(!form) return;
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                Swal.fire({ title: 'Guardando cambios...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                try {
                    const res = await fetch('/Helpdesk/db/public/tickets/update-ticket-action.php', { method: 'POST', body: formData });
                    const text = await res.text();
                    try {
                        const data = JSON.parse(text);
                        if(data.success) {
                            Swal.fire({ icon: 'success', title: '¡Actualizado!', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                        } else { Swal.fire('Error', data.message || 'Error desconocido', 'error'); }
                    } catch (e) { Swal.fire('Error del Servidor', 'Revisa la consola', 'error'); }
                } catch (error) { Swal.fire('Error de Conexión', 'No se pudo conectar', 'error'); }
            });
        }
        handleFormSubmit('manage-form');
        handleFormSubmit('comment-form');
    </script>
</body>
</html>