<?php
// db/admin/niveles/crear_nivel.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    // Ajusta la ruta a tu conexión
    if (!file_exists('../../conexion.php')) throw new Exception("No se encuentra conexion.php");
    require_once '../../conexion.php'; 
    $conn = getDatabaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Leer datos JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $nombreNivel = trim($input['nombre'] ?? '');

        if (empty($nombreNivel)) {
            throw new Exception("El nombre del nivel es obligatorio.");
        }

        // 1. Iniciar Transacción (para que si falla algo, no se guarde nada a medias)
        $conn->begin_transaction();

        try {
            // 2. Insertar en la tabla maestra 'niveles_incidencias'
            // Inicialmente insertamos el nombre visual, el nombre de la tabla lo actualizamos despues
            $stmt = $conn->prepare("INSERT INTO niveles_incidencias (nombre_mostrar, nombre_tabla_db, fecha_creacion) VALUES (?, '', NOW())");
            $stmt->bind_param("s", $nombreNivel);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al guardar el nivel: " . $stmt->error);
            }
            
            // Obtenemos el ID generado (Ej: 1)
            $nuevoId = $conn->insert_id;
            $stmt->close();

            // 3. Definir el nombre técnico de la tabla
            // Usamos un prefijo + el ID para asegurar que sea único y sin espacios
            // Ej: incidencias_cat_1
            $nombreTablaDB = "incidencias_cat_" . $nuevoId;

            // 4. Crear la tabla física dinámicamente
            // Usamos la estructura que solicitaste
            $sqlCrearTabla = "CREATE TABLE `$nombreTablaDB` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `nombre` varchar(150) NOT NULL,
                `tiempo_resolucion` int(11) NOT NULL DEFAULT 0,
                `prioridad` enum('Baja','Media','Alta','Crítica') NOT NULL DEFAULT 'Baja',
                `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            if (!$conn->query($sqlCrearTabla)) {
                throw new Exception("Error al crear la tabla física: " . $conn->error);
            }

            // 5. Actualizar el registro maestro con el nombre de la tabla creada
            $stmtUpdate = $conn->prepare("UPDATE niveles_incidencias SET nombre_tabla_db = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $nombreTablaDB, $nuevoId);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // Si todo salió bien, confirmamos los cambios
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "Nivel '$nombreNivel' creado. Tabla '$nombreTablaDB' generada."
            ]);

        } catch (Exception $e) {
            // Si algo falló, revertimos todo
            $conn->rollback();
            throw $e;
        }

    } else {
        throw new Exception("Método no permitido");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>