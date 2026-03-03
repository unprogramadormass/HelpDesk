<?php
// db/tickets/get-my-tickets.php
session_start();
header('Content-Type: application/json');
error_reporting(0);
require_once '../../conexion.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

$conn = getDatabaseConnection();
$userId = $_SESSION['user_id']; // ID del usuario logueado

try {
    // 1. Recibir Filtros del Frontend
    $search = $_GET['search'] ?? '';
    $estado = $_GET['estado'] ?? 'all';
    $prioridad = $_GET['prioridad'] ?? 'all';
    $dateStart = $_GET['date_start'] ?? '';
    $dateEnd = $_GET['date_end'] ?? '';
    
    // Paginación
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // 2. Construcción de la Consulta
    // FILTRO CRUCIAL: (Soy el Creador O Soy el Agente)
    $where = " WHERE (t.usuario_creador_id = ? OR t.agente_actual_id = ?) ";
    $params = [$userId, $userId];
    $types = "ii";

    // Filtros Adicionales (Búsqueda, Estado, Prioridad...)
    if (!empty($search)) {
        $term = "%$search%";
        $where .= " AND (t.folio LIKE ? OR t.titulo LIKE ? OR ua.firstname LIKE ? OR uc.firstname LIKE ?) ";
        array_push($params, $term, $term, $term, $term);
        $types .= "ssss";
    }

    if ($estado !== 'all') {
        if ($estado === 'sin_asignar') {
            $where .= " AND t.agente_actual_id IS NULL ";
        } else {
            $where .= " AND t.estado = ? ";
            $params[] = ucfirst($estado);
            $types .= "s";
        }
    }

    if ($prioridad !== 'all') {
        $where .= " AND t.prioridad = ? ";
        $params[] = ucfirst($prioridad);
        $types .= "s";
    }

    if (!empty($dateStart)) {
        $where .= " AND DATE(t.fecha_creacion) >= ? ";
        $params[] = $dateStart;
        $types .= "s";
    }
    if (!empty($dateEnd)) {
        $where .= " AND DATE(t.fecha_creacion) <= ? ";
        $params[] = $dateEnd;
        $types .= "s";
    }

    // 3. Contar Total
    $sqlCount = "SELECT COUNT(*) as total 
                 FROM ticket t
                 LEFT JOIN usuarios uc ON t.usuario_creador_id = uc.id
                 LEFT JOIN usuarios ua ON t.agente_actual_id = ua.id
                 $where";
    
    $stmtCount = $conn->prepare($sqlCount);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = $stmtCount->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // 4. Obtener Datos
    $sqlData = "SELECT t.id, t.folio, t.titulo, t.descripcion, t.prioridad, t.estado, t.fecha_creacion,
                       t.usuario_creador_id, t.agente_actual_id, -- Para saber mi rol en el ticket
                       CONCAT(uc.firstname, ' ', uc.firstapellido) as creador_nombre, 
                       uc.avatar as creador_avatar,
                       CONCAT(ua.firstname, ' ', ua.firstapellido) as agente_nombre, 
                       ua.avatar as agente_avatar
                FROM ticket t
                LEFT JOIN usuarios uc ON t.usuario_creador_id = uc.id
                LEFT JOIN usuarios ua ON t.agente_actual_id = ua.id
                $where
                ORDER BY t.fecha_creacion DESC
                LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sqlData);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $row['fecha_formateada'] = date("d M Y", strtotime($row['fecha_creacion']));
        
        $row['creador_avatar_url'] = $row['creador_avatar'] 
            ? "/HelpDesk/assets/img/avatars/" . $row['creador_avatar'] 
            : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $row['creador_nombre'];
        
        // Etiqueta extra para saber qué soy yo en este ticket
        $row['mi_rol'] = ($row['usuario_creador_id'] == $userId) ? 'Creador' : 'Agente';
        if ($row['usuario_creador_id'] == $userId && $row['agente_actual_id'] == $userId) {
            $row['mi_rol'] = 'Ambos';
        }

        $tickets[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $tickets,
        'meta' => [
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'start_index' => ($totalRecords > 0) ? $offset + 1 : 0,
            'end_index' => min($offset + $limit, $totalRecords)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>