<?php
// db/configuracion/upload_avatar.php
session_start();
header('Content-Type: application/json');

// 1. Verificar autenticación básica
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'No autorizado.']));
}

require_once '../conexion.php'; 
$userId = (int)$_SESSION['user_id'];
$conn = getDatabaseConnection();

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    
    // 2. LISTA BLANCA DE SEGURIDAD (Solo imágenes permitidas)
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
    $mimesPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($mimeInfo, $file['tmp_name']);
    finfo_close($mimeInfo);

    // 3. Validar extensión Y contenido real (MIME type)
    if (!in_array($ext, $extensionesPermitidas) || !in_array($mimeReal, $mimesPermitidos)) {
        die(json_encode(['success' => false, 'message' => 'Archivo no permitido. Solo JPG, PNG o GIF.']));
    }

    // 4. Limitar tamaño de archivo (Ej. Max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        die(json_encode(['success' => false, 'message' => 'La imagen pesa demasiado (Max 2MB).']));
    }

    $filename = "user_" . $userId . "_" . time() . "." . $ext;
    $target = "../../assets/img/avatars/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // 5. Inyección SQL segura (Prepared Statement)
        $stmt = $conn->prepare("UPDATE usuarios SET avatar=? WHERE id=?");
        $stmt->bind_param("si", $filename, $userId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true, 'filename' => $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al subir imagen.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen válida.']);
}
$conn->close();
?>