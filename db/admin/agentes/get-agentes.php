<?php
// Archivo: db/admin/agentes/get-agentes.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ocultar errores HTML para no romper el JSON

try {
    // 1. Búsqueda automática de conexion.php (A prueba de errores de ruta)
    $paths = [
        '../../conexion.php',
        '../../../db/conexion.php',
        '../conexion.php',
        '../../db/conexion.php'
    ];
    
    $conexionPath = null;
    foreach ($paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            $conexionPath = __DIR__ . '/' . $path;
            break;
        }
    }

    if (!$conexionPath) {
        throw new Exception("Error Crítico: No se encuentra el archivo conexion.php. Buscado en: " . implode(', ', $paths));
    }
    require_once $conexionPath;

    $conn = getDatabaseConnection();

    // 2. Parámetros
    $range = $_GET['range'] ?? 'week';
    $days = ($range === 'month') ? 30 : 7;

    // Etiquetas de días para la gráfica
    $labels = [];
    $fechasQuery = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        // Generamos etiquetas en español manualmente para evitar problemas de locale
        $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $timestamp = strtotime("-$i days");
        $diaIndex = date('w', $timestamp);
        $labels[] = $diasSemana[$diaIndex] . " " . date('d', $timestamp);
        $fechasQuery[] = date('Y-m-d', $timestamp);
    }

    // 3. Consulta Principal de Agentes
    // Nota: Usamos LEFT JOIN para que no desaparezcan agentes si no tienen rol asignado correctamente
    $sqlAgentes = "SELECT u.id, u.firstname, u.firstapellido, u.avatar, u.username, u.tipo_usuario, u.connected 
                   FROM usuarios u
                   WHERE u.tipo_usuario IN (1, 2, 3) AND u.estado_id = 1 
                   ORDER BY u.firstname ASC";
    
    $resAgentes = $conn->query($sqlAgentes);
    if (!$resAgentes) throw new Exception("Error SQL: " . $conn->error);

    $agentesData = [];
    $chartDatasets = [];
    
    // Colores Neon
    $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4'];
    $cIndex = 0;

    while ($row = $resAgentes->fetch_assoc()) {
        $id = $row['id'];

        // Métricas
        $sqlOpen = "SELECT COUNT(*) as c FROM ticket WHERE agente_actual_id = $id AND estado IN ('Abierto', 'Asignado', 'En Proceso')";
        $open = $conn->query($sqlOpen)->fetch_assoc()['c'];

        $sqlRes = "SELECT COUNT(*) as c FROM ticket WHERE agente_actual_id = $id AND estado IN ('Resuelto', 'Cerrado')";
        $resolved = $conn->query($sqlRes)->fetch_assoc()['c'];

        // Cálculo Eficacia
        $total = $open + $resolved;
        $eficacia = ($total > 0) ? round(($resolved / $total) * 100) : 100; // 100% si no tiene tickets (beneficio de la duda)

        // Datos Gráfica
        $dailyData = [];
        foreach ($fechasQuery as $fecha) {
            // Importante: Si fecha_cierre es NULL, usamos fecha_ultima_actualizacion para que la gráfica pinte algo
            // Solo para tickets cerrados/resueltos
            $sqlDay = "SELECT COUNT(*) as c FROM ticket 
                       WHERE agente_actual_id = $id 
                       AND estado IN ('Resuelto', 'Cerrado')
                       AND (DATE(fecha_cierre) = '$fecha' OR DATE(fecha_ultima_actualizacion) = '$fecha')";
            $dailyData[] = (int)$conn->query($sqlDay)->fetch_assoc()['c'];
        }

        $chartDatasets[] = [
            'label' => $row['firstname'],
            'data' => $dailyData,
            'borderColor' => $colors[$cIndex % count($colors)],
            'backgroundColor' => 'transparent',
            'borderWidth' => 2,
            'tension' => 0.4,
            'pointRadius' => 3
        ];
        $cIndex++;

        $row['tickets_abiertos'] = $open;
        $row['tickets_resueltos'] = $resolved;
        $row['eficacia'] = $eficacia;
        
        $agentesData[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $agentesData,
        'chart' => [
            'labels' => $labels,
            'datasets' => $chartDatasets
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>