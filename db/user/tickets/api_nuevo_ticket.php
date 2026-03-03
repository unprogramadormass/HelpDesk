<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../conexion.php');

if (!isset($_SESSION['id_usuario']) || !is_numeric($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = intval($_SESSION['id_usuario']);

try {

    /* ==========================
       1️⃣ DATOS DEL USUARIO Y SU NIVEL FIJO
    ========================== */
    $sqlUser = "
        SELECT 
            u.id,
            u.firstname,
            u.firstapellido,
            u.extension,
            u.folio,
            u.incidencia_id,
            r.clave AS rol_clave,
            s.nombre AS sucursal_nombre,
            ni.nombre_mostrar AS nivel_nombre,
            ni.nombre_tabla_db
        FROM usuarios u
        LEFT JOIN roles r ON u.tipo_usuario = r.id
        LEFT JOIN sucursales s ON u.sucursal_id = s.id
        LEFT JOIN niveles_incidencias ni ON u.incidencia_id = ni.id
        WHERE u.id = ?
        LIMIT 1
    ";

    $stmtUser = mysqli_prepare($conn, $sqlUser);
    mysqli_stmt_bind_param($stmtUser, "i", $userId);
    mysqli_stmt_execute($stmtUser);
    $userData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));
    mysqli_stmt_close($stmtUser);

    if (!$userData) {
        throw new Exception("Usuario no encontrado.");
    }

    if (empty($userData['incidencia_id']) || empty($userData['nombre_tabla_db'])) {
        throw new Exception("No tienes un nivel de incidencias asignado en tu configuración. Contacta a sistemas.");
    }

    /* ==========================
       2️⃣ INCIDENCIAS DINÁMICAS
    ========================== */
    $incidencias = [];
    $tabla = $userData['nombre_tabla_db'];

    if (preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$tabla'");
        if (mysqli_num_rows($checkTable) > 0) {
            $query = "SELECT id, nombre, prioridad FROM `$tabla` ORDER BY nombre ASC";
            $resIncidencia = mysqli_query($conn, $query);

            while ($incRow = mysqli_fetch_assoc($resIncidencia)) {
                $incidencias[] = $incRow;
            }
        }
    }

    /* ==========================
       3️⃣ ÁREAS DEL USUARIO
    ========================== */
    $areas = [];
    $sqlAreas = "
        SELECT a.id, a.nombre 
        FROM usuario_areas ua
        JOIN areas a ON ua.area_id = a.id
        WHERE ua.usuario_id = ?
        ORDER BY a.nombre ASC
    ";
    
    $stmtAreas = mysqli_prepare($conn, $sqlAreas);
    mysqli_stmt_bind_param($stmtAreas, "i", $userId);
    mysqli_stmt_execute($stmtAreas);
    $resAreas = mysqli_stmt_get_result($stmtAreas);

    while ($row = mysqli_fetch_assoc($resAreas)) {
        $areas[] = $row;
    }
    mysqli_stmt_close($stmtAreas);

    echo json_encode([
        'success' => true,
        'user' => $userData,
        'incidencias' => $incidencias,
        'areas' => $areas
    ]);

} catch (Throwable $e) {
    http_response_code(200); 
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}