<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../conexion.php');

// 1. Validar sesión
if (!isset($_SESSION['id_usuario']) || !is_numeric($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado. La sesión expiró.']);
    exit;
}

$userId = intval($_SESSION['id_usuario']);

try {
    // 2. Obtener la sucursal del usuario
    $stmtUser = mysqli_prepare($conn, "SELECT sucursal_id FROM usuarios WHERE id = ?");
    mysqli_stmt_bind_param($stmtUser, "i", $userId);
    mysqli_stmt_execute($stmtUser);
    $userData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));
    mysqli_stmt_close($stmtUser);

    if (!$userData || empty($userData['sucursal_id'])) {
        throw new Exception("Tu usuario no tiene una sucursal asignada.");
    }
    $sucursal_id = $userData['sucursal_id'];

    // 3. Recibir los datos del formulario (POST)
    $nivel_incidencia_id = $_POST['nivel_incidencia_id'] ?? null;
    $prioridad = $_POST['prioridad'] ?? 'Baja';
    $area_id = $_POST['area_id'] ?? null; 
    $descripcion = $_POST['descripcion'] ?? '';
    $titulo = $_POST['titulo'] ?? 'Sin título'; 

    if (empty($nivel_incidencia_id) || empty($area_id) || empty($descripcion)) {
        throw new Exception("Por favor, completa todos los campos obligatorios.");
    }

    // ==========================================
    // 4. LÓGICA DE SUBIDA DE ARCHIVOS MÚLTIPLES
    // ==========================================
    $nombres_guardados = []; // Aquí almacenaremos los nombres finales de los archivos
    
    // Verificamos si se enviaron archivos y si no están vacíos
    if (isset($_FILES['evidencia']) && !empty($_FILES['evidencia']['name'][0])) {
        
        // Ruta absoluta a la carpeta helpdesk/uploads/ (ajusta los ../ según donde esté este script)
        $uploadDir = __DIR__ . '/../../../uploads/'; 
        
        // Si la carpeta no existe, la creamos con permisos
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $cantidad_archivos = count($_FILES['evidencia']['name']);
        
        for ($i = 0; $i < $cantidad_archivos; $i++) {
            if ($_FILES['evidencia']['error'][$i] === UPLOAD_ERR_OK) {
                
                $nombreOriginal = $_FILES['evidencia']['name'][$i];
                $tmpName = $_FILES['evidencia']['tmp_name'][$i];
                
                // Extraer la extensión del archivo original (ej: pdf, jpg)
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                
                // Generar un nombre ÚNICO: "evidencia_timestamp_idAleatorio.extensión"
                $nuevoNombre = 'evidencia_' . time() . '_' . uniqid() . '.' . $extension;
                
                $rutaDestino = $uploadDir . $nuevoNombre;

                // Mover el archivo de la memoria temporal a la carpeta final
                if (move_uploaded_file($tmpName, $rutaDestino)) {
                    // Si se subió con éxito, guardamos solo el nombre del archivo en nuestro arreglo
                    $nombres_guardados[] = $nuevoNombre;
                }
            }
        }
    }

    // Convertimos el arreglo de nombres a un formato JSON ["archivo1.jpg", "archivo2.pdf"] 
    // Si no subió nada, lo dejamos en NULL
    $evidence_json = empty($nombres_guardados) ? null : json_encode($nombres_guardados);


    // 5. Iniciar Transacción en Base de Datos
    mysqli_begin_transaction($conn);

    // 6. Insertar el Ticket (Añadimos la nueva columna 'evidence')
    $sqlInsert = "INSERT INTO ticket (usuario_creador_id, sucursal_id, area_id, titulo, descripcion, prioridad, estado, nivel_incidencia_id, evidence) VALUES (?, ?, ?, ?, ?, ?, 'Abierto', ?, ?)";
    $stmtInsert = mysqli_prepare($conn, $sqlInsert);
    
    // Bind Param (Añadimos una 's' al final para el json de evidencia)
    mysqli_stmt_bind_param($stmtInsert, "iiisssis", $userId, $sucursal_id, $area_id, $titulo, $descripcion, $prioridad, $nivel_incidencia_id, $evidence_json);
    
    if (!mysqli_stmt_execute($stmtInsert)) {
        throw new Exception("Error al guardar el ticket: " . mysqli_error($conn));
    }
    
    $ticketId = mysqli_insert_id($conn); 
    mysqli_stmt_close($stmtInsert);

    // 7. Obtener el folio generado por el Trigger
    $resFolio = mysqli_query($conn, "SELECT folio FROM ticket WHERE id = $ticketId");
    $folioRow = mysqli_fetch_assoc($resFolio);
    $folioGenerado = $folioRow['folio'];

    // 8. Insertar en el Historial del Ticket
    $descHistorial = "Nuevo de ticket. Prioridad: $prioridad. Área destino ID: $area_id.";
    $sqlHistorial = "INSERT INTO ticket_historial (ticket_id, usuario_responsable_id, tipo_movimiento, descripcion_evento) VALUES (?, ?, 'Creación', ?)";
    $stmtHist = mysqli_prepare($conn, $sqlHistorial);
    mysqli_stmt_bind_param($stmtHist, "iis", $ticketId, $userId, $descHistorial);
    mysqli_stmt_execute($stmtHist);
    mysqli_stmt_close($stmtHist);

    // Confirmar transacción
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Ticket creado con éxito.',
        'ticket_id' => $ticketId,
        'folio' => $folioGenerado
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>