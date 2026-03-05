<?php
// guardar_incidencia.php
ini_set('display_errors', 0);
header('Content-Type: application/json');

// SEGURIDAD: Solo Administradores
session_start();
require_once '../../security/validacion.php';
verificarAcceso([1]); 

require_once '../../conexion.php'; 
$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = isset($input['id']) ? (int)$input['id'] : ''; 
    $originLevelId = isset($input['originLevelId']) ? (int)$input['originLevelId'] : ''; 
    
    $levelIds = $input['levelIds'] ?? [];
    $nombre = trim($input['nombre'] ?? '');
    $slaVal = intval($input['slaVal'] ?? 0);
    $slaUnit = $input['slaUnit'] ?? 'Min';
    $prioridad = $input['prioridad'] ?? 'Baja';

    $minutos = $slaVal;
    if ($slaUnit === 'Hrs') $minutos = $slaVal * 60;
    if ($slaUnit === 'Días') $minutos = $slaVal * 1440;

    // Solo se permite ejecutar código si superó el verificarAcceso()
    if (!empty($id) && !empty($originLevelId)) {
        $stmt = $conn->prepare("SELECT nombre_tabla_db FROM niveles_incidencias WHERE id = ?");
        $stmt->bind_param("i", $originLevelId);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            // Validar que el nombre de la tabla no tenga caracteres raros (Anti Segundo-Orden SQLi)
            $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $row['nombre_tabla_db']);
            
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
        // ... (El código de inserción múltiple que tenías se mantiene, pero sanitizando `$tabla` igual que arriba)
        $exitoCount = 0;
        foreach ($levelIds as $lvlId) {
            $lvlIdInt = (int)$lvlId;
            $stmt = $conn->prepare("SELECT nombre_tabla_db FROM niveles_incidencias WHERE id = ?");
            $stmt->bind_param("i", $lvlIdInt);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($row = $res->fetch_assoc()) {
                $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $row['nombre_tabla_db']);
                $sqlInsert = "INSERT INTO `$tabla` (nombre, tiempo_resolucion, prioridad, fecha_creacion) VALUES (?, ?, ?, NOW())";
                $stmtIns = $conn->prepare($sqlInsert);
                $stmtIns->bind_param("sis", $nombre, $minutos, $prioridad);
                if ($stmtIns->execute()) $exitoCount++;
                $stmtIns->close();
            }
            $stmt->close();
        }
        echo json_encode(['success' => ($exitoCount > 0), 'message' => $exitoCount > 0 ? "Creada en $exitoCount nivel(es)." : "Error al crear."]);
    }
}
$conn->close();
?>