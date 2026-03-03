<?php
// db/admin/usuarios/get_usuarios_data.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    if (!file_exists('../../conexion.php')) throw new Exception("No se encuentra conexion.php");
    require_once '../../conexion.php'; 
    $conn = getDatabaseConnection();

    // --- 1. OBTENER FILTROS RECIBIDOS ---
    $search = $_GET['search'] ?? '';
    $roleFilter = $_GET['rol'] ?? '0';
    $statusFilter = $_GET['estado'] ?? '0';

    // --- 2. CONSULTA BASE PARA USUARIOS ---
    // Usamos LEFT JOIN para traer los nombres de Rol, Sucursal y Estado en una sola consulta
    $sql = "SELECT 
                u.id, u.username, u.firstname, u.firstapellido, u.email, u.folio, u.fecha_creacion, u.avatar,
                r.nombre AS nombre_rol,
                s.nombre AS nombre_sucursal,
                e.nombre AS nombre_estado,
                e.id AS estado_id
            FROM usuarios u
            LEFT JOIN roles r ON u.tipo_usuario = r.id
            LEFT JOIN sucursales s ON u.sucursal_id = s.id
            LEFT JOIN estados_usuario e ON u.estado_id = e.id
            WHERE 1=1";

    // Aplicar Filtros Dinámicos
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND (u.firstname LIKE ? OR u.firstapellido LIKE ? OR u.email LIKE ? OR u.folio LIKE ?)";
        $searchTerm = "%$search%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $types .= "ssss";
    }

    if ($roleFilter != '0' && $roleFilter != '') {
        $sql .= " AND u.tipo_usuario = ?";
        array_push($params, $roleFilter);
        $types .= "i";
    }

    if ($statusFilter != '0' && $statusFilter != '') {
        $sql .= " AND u.estado_id = ?";
        array_push($params, $statusFilter);
        $types .= "i";
    }

    $sql .= " ORDER BY u.id DESC";

    // Ejecutar consulta preparada
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    $stmt->close();

    // --- 3. OBTENER CONTADORES (KPIs) ---
    // Solo calculamos contadores globales (sin filtros de búsqueda) para los cuadros de arriba
    $kpiSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado_id = 1 THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado_id = 2 THEN 1 ELSE 0 END) as suspendidos,
                SUM(CASE WHEN estado_id = 3 THEN 1 ELSE 0 END) as inactivos
               FROM usuarios";
    $kpiResult = $conn->query($kpiSql);
    $kpis = $kpiResult->fetch_assoc();

    // --- 4. OBTENER OPCIONES PARA LOS SELECTS (ROLES Y ESTADOS) ---
    // Esto llena los <select> del filtro dinámicamente
    $rolesRes = $conn->query("SELECT id, nombre FROM roles");
    $rolesList = [];
    while($r = $rolesRes->fetch_assoc()) $rolesList[] = $r;

    $estadosRes = $conn->query("SELECT id, nombre FROM estados_usuario");
    $estadosList = [];
    while($e = $estadosRes->fetch_assoc()) $estadosList[] = $e;


    // --- 5. RESPUESTA FINAL ---
    echo json_encode([
        'success' => true,
        'kpis' => $kpis,
        'usuarios' => $usuarios,
        'meta' => [
            'roles' => $rolesList,
            'estados' => $estadosList
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();
?>