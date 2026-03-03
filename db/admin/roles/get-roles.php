<?php
// obtener_roles.php
header('Content-Type: application/json');

// Ajusta la ruta a tu archivo de conexión según tu estructura
require_once '../../conexion.php'; 

$conn = getDatabaseConnection();

// Consulta SQL Mágica:
// 1. Selecciona los datos del rol.
// 2. Hace un LEFT JOIN con la tabla usuarios (comparando roles.id = usuarios.tipo_usuario).
// 3. Agrupa por rol y cuenta cuantos usuarios hay.
$sql = "SELECT 
            r.id, 
            r.clave, 
            r.nombre, 
            r.fecha_creacion,
            COUNT(u.id) as total_usuarios
        FROM roles r
        LEFT JOIN usuarios u ON r.id = u.tipo_usuario
        GROUP BY r.id
        ORDER BY r.id ASC";

$result = $conn->query($sql);

$roles = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Agregamos descripciones visuales basadas en la clave (ya que no están en la BD)
        // Esto es solo para que se vea bonito en el front
        $meta = getRoleMetadata($row['clave']);
        
        $row['descripcion'] = $meta['desc'];
        $row['icono'] = $meta['icon'];
        $row['color'] = $meta['color'];
        
        $roles[] = $row;
    }
}

echo json_encode($roles);
$conn->close();

// Función auxiliar para decorar los roles según su CLAVE
function getRoleMetadata($clave) {
    switch($clave) {
        case 'ADMIN':
            return ['desc' => 'Acceso total al sistema.', 'icon' => 'fa-crown', 'color' => 'purple'];
        case 'SUP':
            return ['desc' => 'Supervisión y reportes.', 'icon' => 'fa-user-tie', 'color' => 'orange'];
        case 'AGT':
            return ['desc' => 'Gestión de tickets.', 'icon' => 'fa-headset', 'color' => 'blue'];
        case 'CORP':
            return ['desc' => 'Acceso corporativo.', 'icon' => 'fa-building', 'color' => 'gray'];
        case 'OPER':
            return ['desc' => 'Personal operativo.', 'icon' => 'fa-tools', 'color' => 'green'];
        default:
            return ['desc' => 'Rol del sistema.', 'icon' => 'fa-user-shield', 'color' => 'slate'];
    }
}
?>