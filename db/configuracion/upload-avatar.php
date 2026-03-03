<?php
// db/configuracion/upload_avatar.php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php'; 

$userId = $_SESSION['user_id'];
$conn = getDatabaseConnection();

if (isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "user_" . $userId . "_" . time() . "." . $ext;
    $target = "../../assets/img/avatars/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Actualizar BD
        $conn->query("UPDATE usuarios SET avatar='$filename' WHERE id=$userId");
        echo json_encode(['success' => true, 'filename' => $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al subir imagen']);
    }
}
$conn->close();
?>