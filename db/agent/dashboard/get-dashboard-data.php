<?php
// db/dashboard/get-dashboard-data.php
session_start();
header('Content-Type: application/json');
error_reporting(0);
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

try {
    // --- 1. KPIs (Tarjetas Superiores) ---
    
    // Total Tickets
    $sqlTotal = "SELECT COUNT(*) as total FROM ticket";
    $total = $conn->query($sqlTotal)->fetch_assoc()['total'];

    // Pendientes (Abierto, Asignado, En Proceso, Espera)
    $sqlPending = "SELECT COUNT(*) as pendientes FROM ticket WHERE estado IN ('Abierto', 'Asignado', 'En Proceso', 'Espera')";
    $pending = $conn->query($sqlPending)->fetch_assoc()['pendientes'];

    // Resueltos (Para satisfacción)
    $sqlResolved = "SELECT COUNT(*) as resueltos FROM ticket WHERE estado IN ('Resuelto', 'Cerrado')";
    $resolved = $conn->query($sqlResolved)->fetch_assoc()['resueltos'];

    // Tiempo Promedio (Diferencia entre creación y cierre en minutos)
    // Solo de los tickets cerrados
    $sqlTime = "SELECT AVG(TIMESTAMPDIFF(MINUTE, fecha_creacion, fecha_cierre)) as promedio 
                FROM ticket WHERE fecha_cierre IS NOT NULL";
    $avgMinutes = $conn->query($sqlTime)->fetch_assoc()['promedio'];
    $avgTimeFormatted = $avgMinutes ? round($avgMinutes / 60, 1) . " hrs" : "0 hrs";

    // Satisfacción (Simulada: % de tickets resueltos vs total)
    $satisfaction = ($total > 0) ? round(($resolved / $total) * 100) : 100;

    // --- 2. GRÁFICA PRINCIPAL (Últimos 7 días) ---
    $chartLabels = [];
    $chartDataNew = [];
    $chartDataResolved = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('D', strtotime($date)); // Lun, Mar, etc. (Depende idioma servidor)

        // Contar creados ese día
        $sqlDayNew = "SELECT COUNT(*) as c FROM ticket WHERE DATE(fecha_creacion) = '$date'";
        $chartDataNew[] = $conn->query($sqlDayNew)->fetch_assoc()['c'];

        // Contar resueltos ese día
        $sqlDayRes = "SELECT COUNT(*) as c FROM ticket WHERE DATE(fecha_cierre) = '$date'";
        $chartDataResolved[] = $conn->query($sqlDayRes)->fetch_assoc()['c'];
    }

    // --- 3. GRÁFICA DE DONA (Distribución por Nivel) ---
    // Mapeamos nivel_incidencia_id a los nombres reales
    $sqlPie = "SELECT n.nombre_mostrar, COUNT(t.id) as cantidad 
               FROM ticket t 
               LEFT JOIN niveles_incidencias n ON t.nivel_incidencia_id = n.id 
               GROUP BY t.nivel_incidencia_id";
    $resPie = $conn->query($sqlPie);
    
    $pieLabels = [];
    $pieData = [];
    while($row = $resPie->fetch_assoc()) {
        $pieLabels[] = $row['nombre_mostrar'] ?? 'Sin Clasificar';
        $pieData[] = $row['cantidad'];
    }

    // --- 4. TABLA DE TICKETS RECIENTES (Últimos 5) ---
    $sqlRecent = "SELECT t.id, t.folio, t.titulo, t.prioridad, t.estado, 
                         t.agente_actual_id as agente_id, 
                         CONCAT(uc.firstname, ' ', uc.firstapellido) as creador_nombre, 
                         uc.avatar as creador_avatar,
                         CONCAT(ua.firstname, ' ', ua.firstapellido) as agente_nombre, 
                         ua.avatar as agente_avatar
                  FROM ticket t
                  LEFT JOIN usuarios uc ON t.usuario_creador_id = uc.id
                  LEFT JOIN usuarios ua ON t.agente_actual_id = ua.id
                  ORDER BY t.fecha_creacion DESC LIMIT 5";
                  
    $resRecent = $conn->query($sqlRecent);
    
    $recentTickets = [];
    while($row = $resRecent->fetch_assoc()) {
        // Procesar avatares
        $row['creador_avatar_url'] = $row['creador_avatar'] 
            ? "/HelpDesk/assets/img/avatars/" . $row['creador_avatar']
            : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $row['creador_nombre'];
            
        $recentTickets[] = $row;
    }

    // --- 5. HISTORIAL COMPLETO (Para el Modal y Buscador) --- [NUEVO]
    // Misma consulta pero SIN LÍMITE y solo campos necesarios para la lista
    $sqlHistory = "SELECT t.id, t.folio, t.titulo, t.estado, t.prioridad
                   FROM ticket t
                   ORDER BY t.fecha_creacion DESC";
                   
    $resHistory = $conn->query($sqlHistory);
    $historyTickets = [];
    while($row = $resHistory->fetch_assoc()) {
        $historyTickets[] = $row;
    }

    echo json_encode([
        'success' => true,
        'kpis' => [
            'total' => $total,
            'pending' => $pending,
            'avgTime' => $avgTimeFormatted,
            'satisfaction' => $satisfaction,
            'resolved' => $resolved
        ],
        'charts' => [
            'main' => [
                'labels' => $chartLabels,
                'new' => $chartDataNew,
                'resolved' => $chartDataResolved
            ],
            'pie' => [
                'labels' => $pieLabels,
                'data' => $pieData
            ]
        ],
        'recent' => $recentTickets,
        'history' => $historyTickets // <--- Agregamos esto para el modal
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>