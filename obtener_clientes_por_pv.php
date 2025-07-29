<?php
// Asegúrate de que la ruta a 'conexion.php' sea correcta
require 'conexion.php';

header('Content-Type: application/json');

// Permitir solicitudes de cualquier origen (para desarrollo)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if (isset($_GET['id_pv'])) {
    $id_pv = (int)$_GET['id_pv'];

    // Consulta para obtener los clientes (id_empresa) únicos que están asociados
    // a rutas para el id_pv dado. Luego, obtener sus nombres (razon_social)
    // de la tabla 'clientes'.
    $sql = "SELECT DISTINCT c.id AS cliente_id, c.razon_social AS nombre_cliente 
            FROM rutas r
            JOIN clientes c ON r.id_empresa = c.id
            WHERE r.id_pv = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt->bind_param("i", $id_pv);
    $stmt->execute();
    $result = $stmt->get_result();

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }

    echo json_encode(['clientes' => $clientes]);

    $stmt->close();
} else {
    echo json_encode(['error' => 'ID de punto de venta no proporcionado']);
}

$conn->close();
?>