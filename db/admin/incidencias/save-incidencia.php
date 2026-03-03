<?php
// guardar_incidencia.php
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? ''; // ID de la incidencia (para editar)
    $originLevelId = $input['originLevelId'] ?? ''; // ID de la tabla donde vive
    
    $levelIds = $input['levelIds'] ?? [];
    $nombre = $input['nombre'] ?? '';
    $slaVal = intval($input['slaVal'] ?? 0);
    $slaUnit = $input['slaUnit'] ?? 'Min';
    $prioridad = $input['prioridad'] ?? 'Baja';

    // Calcular minutos
    $minutos = $slaVal;
    if ($slaUnit === 'Hrs') $minutos = $slaVal * 60;
    if ($slaUnit === 'Días') $minutos = $slaVal * 1440;

    if (!empty($id) && !empty($originLevelId)) {
        // --- MODO EDITAR (UPDATE) ---
        
        // 1. Buscamos la tabla correcta
        $stmt = $conn->prepare("SELECT nombre_tabla_db FROM niveles_incidencias WHERE id = ?");
        $stmt->bind_param("i", $originLevelId);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $tabla = $row['nombre_tabla_db'];
            
            // 2. Ejecutamos UPDATE en esa tabla específica
            $sqlUpdate = "UPDATE `$tabla` SET nombre=?, tiempo_resolucion=?, prioridad=? WHERE id=?";
            $stmtUpd = $conn->prepare($sqlUpdate);
            $stmtUpd->bind_param("sisi", $nombre, $minutos, $prioridad, $id);
            
            if ($stmtUpd->execute()) {
                echo json_encode(['success' => true, 'message' => 'Incidencia actualizada.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
            }
            $stmtUpd->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Nivel no encontrado.']);
        }
        $stmt->close();

    } else {
        // --- MODO CREAR (INSERT) - (Se mantiene tu lógica anterior) ---
        
        $exitoCount = 0;
        foreach ($levelIds as $lvlId) {
            $stmt = $conn->prepare("SELECT nombre_tabla_db FROM niveles_incidencias WHERE id = ?");
            $stmt->bind_param("i", $lvlId);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($row = $res->fetch_assoc()) {
                $tabla = $row['nombre_tabla_db'];
                // Insertar...
                $sqlInsert = "INSERT INTO `$tabla` (nombre, tiempo_resolucion, prioridad, fecha_creacion) VALUES (?, ?, ?, NOW())";
                $stmtIns = $conn->prepare($sqlInsert);
                $stmtIns->bind_param("sis", $nombre, $minutos, $prioridad);
                if ($stmtIns->execute()) $exitoCount++;
                $stmtIns->close();
            }
            $stmt->close();
        }
        
        if ($exitoCount > 0) {
            echo json_encode(['success' => true, 'message' => "Creada en $exitoCount nivel(es)."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear.']);
        }
    }
}
$conn->close();
?>