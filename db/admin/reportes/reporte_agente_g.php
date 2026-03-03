<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../conexion.php';

// Obtiene los filtros enviados por JavaScript
$input = json_decode(file_get_contents('php://input'), true);

$agenteId = $input['agenteId'] ?? 0;
$fechaDesde = $input['fechaDesde'] ?? '';
$fechaHasta = $input['fechaHasta'] ?? '';

$incluirAsignados = $input['incluirAsignados'] ?? false;
$incluirCerrados = $input['incluirCerrados'] ?? false;
$incluirAbiertos = $input['incluirAbiertos'] ?? false;

if (empty($agenteId) || empty($fechaDesde) || empty($fechaHasta)) {
    echo json_encode(['error' => 'Faltan filtros para generar el reporte.']);
    exit;
}

try {
    $responseData = [];

    // --- PASO 1: OBTENER TODAS LAS INCIDENCIAS POSIBLES (Sin cambios) ---
    $incidencias_map = [];
    $sqlTablasIncidencias = "SELECT DISTINCT tabla FROM rela_inci_tablas WHERE tabla != ''";
    $resultTablas = $conn->query($sqlTablasIncidencias);
    
    while ($tablaRow = $resultTablas->fetch_assoc()) {
        $tabla = $tablaRow['tabla'];
        $sqlIncidencias = "SELECT DISTINCT nombre FROM `$tabla` WHERE nombre != ''";
        $resultIncidencias = $conn->query($sqlIncidencias);
        while ($incidenciaRow = $resultIncidencias->fetch_assoc()) {
            $nombreIncidencia = $incidenciaRow['nombre'];
            if (!isset($incidencias_map[$nombreIncidencia])) {
                $incidencias_map[$nombreIncidencia] = true;
            }
        }
    }
    $incidencias = array_keys($incidencias_map);
    sort($incidencias);

    // --- PASO 2: OBTENER SUCURSALES (MODIFICADO) ---
    // ✅ MODIFICADO: Esta consulta ahora incluye Corporativo y lo pone primero,
    // luego ordena el resto por su ID.
    $sqlSucursales = "SELECT id, nombre FROM sucursales ORDER BY CASE WHEN id = 101 THEN 0 ELSE 1 END, id ASC";
    $resultSucursales = $conn->query($sqlSucursales);
    $sucursales = [];
    while ($row = $resultSucursales->fetch_assoc()) {
        $sucursales[] = $row['nombre'];
    }
    // ❌ ELIM:INADO Se quita la línea `sort($sucursales);` que reordenaba alfabéticamente.
    // sort($sucursales);

    // --- PASO 3: BITÁCORA - CONSTRUIR MATRIZ Y CONTAR TICKETS (Sin cambios) ---
    $bitacoraData = [];
    foreach ($incidencias as $incidenciaNombre) {
        $bitacoraData[$incidenciaNombre] = [];
        foreach ($sucursales as $sucursalNombre) {
            $bitacoraData[$incidenciaNombre][$sucursalNombre] = 0;
        }
    }

    $sqlBitacora = "SELECT 
                        t.titulot AS incidencia, 
                        t.sucursal AS sucursal_nombre,
                        COUNT(t.id) AS total
                    FROM ticket t
                    WHERE t.usuario_asignado = ? 
                      AND t.estadot = 'cerrado'
                      AND DATE(t.fecha_creaciont) BETWEEN ? AND ?
                    GROUP BY t.titulot, t.sucursal";
    
    $stmt = $conn->prepare($sqlBitacora);
    $stmt->bind_param("iss", $agenteId, $fechaDesde, $fechaHasta);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $incidenciaContada = $row['incidencia'];
        $sucursalContada = $row['sucursal_nombre'];
        $total = (int)$row['total'];
        
        if (isset($bitacoraData[$incidenciaContada]) && isset($bitacoraData[$incidenciaContada][$sucursalContada])) {
            $bitacoraData[$incidenciaContada][$sucursalContada] = $total;
        }
    }
    $stmt->close();

    // Obtener nombre del agente (Sin cambios)
    $sqlAgente = "SELECT nombres, apellidos FROM usuarios WHERE id = ?";
    $stmtAgente = $conn->prepare($sqlAgente);
    $stmtAgente->bind_param("i", $agenteId);
    $stmtAgente->execute();
    $resultAgente = $stmtAgente->get_result();
    $agenteData = $resultAgente->fetch_assoc();
    $nombreAgente = $agenteData ? $agenteData['nombres'] . ' ' . $agenteData['apellidos'] : 'Agente Desconocido';
    $stmtAgente->close();

    $responseData['bitacora'] = [
        'sucursales' => $sucursales, 
        'datos' => $bitacoraData,
        'incidencias' => $incidencias,
        'nombreAgente' => $nombreAgente
    ];

    // --- PASO 4: LISTADOS DE TICKETS (Sin cambios) ---
    $sqlBaseTickets = "SELECT 
                            t.id, t.titulot, t.categoriat AS categoria, t.sucursal, 
                            t.estadot, t.prioridadt, t.fecha_creaciont, t.fecha_cierret, 
                            CONCAT(creador.nombres, ' ', creador.apellidos) AS nombre_creador
                        FROM ticket t
                        LEFT JOIN usuarios creador ON t.idusrcreador = creador.id
                        WHERE t.usuario_asignado = ? 
                          AND DATE(t.fecha_creaciont) BETWEEN ? AND ?";

    function getTicketList($conn, $sql, $params, $extraCondition = "") {
        $stmt = $conn->prepare($sql . $extraCondition . " ORDER BY t.id DESC");
        $stmt->bind_param("iss", ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    $baseParams = [$agenteId, $fechaDesde, $fechaHasta];

    if ($incluirAsignados) {
        $responseData['asignados'] = getTicketList($conn, $sqlBaseTickets, $baseParams);
    }
    if ($incluirCerrados) {
        $responseData['cerrados'] = getTicketList($conn, $sqlBaseTickets, $baseParams, " AND t.estadot = 'cerrado'");
    }
    if ($incluirAbiertos) {
        $responseData['abiertos'] = getTicketList($conn, $sqlBaseTickets, $baseParams, " AND t.estadot != 'cerrado'");
    }

    echo json_encode($responseData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>