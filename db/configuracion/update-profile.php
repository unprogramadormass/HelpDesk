<?php
// db/configuracion/update_profile.php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php'; 

if (!isset($_SESSION['user_id'])) exit;
$userId = $_SESSION['user_id'];
$conn = getDatabaseConnection();

$input = json_decode(file_get_contents('php://input'), true);

// Si viene 'seccion' == 'notificaciones', actualizamos solo checkboxes
if (isset($input['seccion']) && $input['seccion'] === 'notificaciones') {
    $wa = $input['noti_whatsapp'] ? 1 : 0;
    $em = $input['noti_email'] ? 1 : 0;
    $nu = $input['noti_nuevo'] ? 1 : 0;
    $si = $input['noti_sistema'] ? 1 : 0;

    $sql = "UPDATE usuarios SET noti_whatsapp=?, noti_email=?, noti_nuevo=?, noti_sistema=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $wa, $em, $nu, $si, $userId);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Preferencias guardadas']);
    exit;
}

// Si no, es actualización de Perfil (Datos personales)
$fname = $input['firstname'];
$sname = $input['secondname'];
$fape = $input['firstapellido'];
$sape = $input['secondapellido'];
$email = $input['email'];
$cel = $input['celular'];
$ext = $input['extension'];

$sql = "UPDATE usuarios SET firstname=?, secondname=?, firstapellido=?, secondapellido=?, email=?, celular=?, extension=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssii", $fname, $sname, $fape, $sape, $email, $cel, $ext, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Perfil actualizado']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar']);
}
$conn->close();
?>