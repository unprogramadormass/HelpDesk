<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../conexion.php');

if (!isset($_SESSION['id_usuario']) || !is_numeric($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

$userId = intval($_SESSION['id_usuario']);

try {
    $stmtUser = mysqli_prepare($conn, "SELECT sucursal_id FROM usuarios WHERE id = ?");
    mysqli_stmt_bind_param($stmtUser, "i", $userId);
    mysqli_stmt_execute($stmtUser);
    $userData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));
    mysqli_stmt_close($stmtUser);

    if (!$userData || empty($userData['sucursal_id'])) {
        throw new Exception("Tu usuario no tiene una sucursal asignada.");
    }
    $sucursal_id = $userData['sucursal_id'];

    $nivel_incidencia_id = $_POST['nivel_incidencia_id'] ?? null;
    $prioridad = $_POST['prioridad'] ?? 'Baja';
    $area_id = $_POST['area_id'] ?? null; 
    $descripcion = $_POST['descripcion'] ?? '';
    $titulo = $_POST['titulo'] ?? 'Sin título'; 

    $nombres_guardados = []; 
    if (isset($_FILES['evidencia']) && !empty($_FILES['evidencia']['name'][0])) {
        $uploadDir = __DIR__ . '/../../../uploads/'; 
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $extPermitidas = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx', 'csv'];
        // Validación MIME para evitar RCE
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        
        for ($i = 0; $i < count($_FILES['evidencia']['name']); $i++) {
            if ($_FILES['evidencia']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['evidencia']['tmp_name'][$i];
                $extension = strtolower(pathinfo($_FILES['evidencia']['name'][$i], PATHINFO_EXTENSION));
                $mimeReal = finfo_file($finfo, $tmpName);

                if (!in_array($extension, $extPermitidas)) throw new Exception("Formato no permitido.");

                $nuevoNombre = 'evidencia_' . time() . '_' . uniqid() . '.' . $extension;
                if (move_uploaded_file($tmpName, $uploadDir . $nuevoNombre)) {
                    $nombres_guardados[] = $nuevoNombre;
                }
            }
        }
        finfo_close($finfo);
    }

    $evidence_json = empty($nombres_guardados) ? null : json_encode($nombres_guardados);

    mysqli_begin_transaction($conn);

    $sqlInsert = "INSERT INTO ticket (usuario_creador_id, sucursal_id, area_id, titulo, descripcion, prioridad, estado, nivel_incidencia_id, evidence) VALUES (?, ?, ?, ?, ?, ?, 'Abierto', ?, ?)";
    $stmtInsert = mysqli_prepare($conn, $sqlInsert);
    mysqli_stmt_bind_param($stmtInsert, "iiisssis", $userId, $sucursal_id, $area_id, $titulo, $descripcion, $prioridad, $nivel_incidencia_id, $evidence_json);
    mysqli_stmt_execute($stmtInsert);
    $ticketId = mysqli_insert_id($conn); 
    mysqli_stmt_close($stmtInsert);

    // CORRECCIÓN: Sentencia preparada para obtener el folio (Evita Inyección SQL)
    $stmtFolio = mysqli_prepare($conn, "SELECT folio FROM ticket WHERE id = ?");
    mysqli_stmt_bind_param($stmtFolio, "i", $ticketId);
    mysqli_stmt_execute($stmtFolio);
    $folioRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtFolio));
    $folioGenerado = $folioRow['folio'];
    mysqli_stmt_close($stmtFolio);

    $descHistorial = "Nuevo ticket creado.";
    $stmtHist = mysqli_prepare($conn, "INSERT INTO ticket_historial (ticket_id, usuario_responsable_id, tipo_movimiento, descripcion_evento) VALUES (?, ?, 'Creación', ?)");
    mysqli_stmt_bind_param($stmtHist, "iis", $ticketId, $userId, $descHistorial);
    mysqli_stmt_execute($stmtHist);

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'folio' => $folioGenerado]);

} catch (Exception $e) {
    if(isset($conn)) mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>