<?php
// db/admin/niveles/get_niveles_incidencias.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    if (!file_exists('../../conexion.php')) throw new Exception("No se encuentra conexion.php");
    require_once '../../conexion.php'; 
    $conn = getDatabaseConnection();

    // 1. Obtener los Niveles (Maestro)
    $sqlNiveles = "SELECT * FROM niveles_incidencias ORDER BY id ASC";
    $resultNiveles = $conn->query($sqlNiveles);
    
    $data = [];

    if ($resultNiveles) {
        while ($nivel = $resultNiveles->fetch_assoc()) {
            $nombreTabla = $nivel['nombre_tabla_db'];
            $nivel['incidencias'] = []; // Inicializamos array de incidencias

            // 2. Verificar si la tabla dinámica existe antes de consultar
            $checkTable = $conn->query("SHOW TABLES LIKE '$nombreTabla'");
            
            if ($checkTable && $checkTable->num_rows > 0) {
                // 3. Consultar las incidencias de ESTE nivel específico
                $sqlIncidencias = "SELECT * FROM `$nombreTabla` ORDER BY id DESC";
                $resultIncidencias = $conn->query($sqlIncidencias);
                
                if ($resultIncidencias) {
                    while ($row = $resultIncidencias->fetch_assoc()) {
                        $nivel['incidencias'][] = $row;
                    }
                }
            }
            
            $data[] = $nivel;
        }
    }

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>