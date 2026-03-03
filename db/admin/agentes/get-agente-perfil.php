<?php
// db/admin/agentes/get_agente_perfil.php
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

try {
    $id = $_GET['id'] ?? '';
    if (empty($id)) throw new Exception("ID no proporcionado");

    // 1. Obtener Datos del Agente con JOINs necesarios
    $sql = "SELECT 
                u.id, u.folio, u.firstname, u.firstapellido, u.email, u.celular, u.extension, u.avatar, 
                u.username, u.connected, u.folio, u.tipo_usuario,
                r.nombre as rol,
                p.nombre as puesto,
                s.nombre as sucursal
            FROM usuarios u
            LEFT JOIN roles r ON u.tipo_usuario = r.id
            LEFT JOIN puesto p ON u.puesto_id = p.id
            LEFT JOIN sucursales s ON u.sucursal_id = s.id
            WHERE u.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $agente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$agente) throw new Exception("Agente no encontrado");

    // 2. Simulamos datos de actividad (Ya que no tenemos tabla de tickets aún)
    // Esto es para que la gráfica y la lista no salgan vacías
    $agente['kpis'] = [12, 19, 3, 5, 2, 3, 10]; 
    $agente['actividad'] = [
        ['titulo' => 'Reinicio de Clave', 'ticket' => '#4089', 'estado' => 'resuelto'],
        ['titulo' => 'Falla en VPN', 'ticket' => '#4091', 'estado' => 'abierto']
    ];

    echo json_encode(['success' => true, 'data' => $agente]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>