<?php
include 'conexion.php'; // Asegúrate de que este archivo contenga la conexión a tu base de datos

header('Content-Type: application/json'); // Indica que la respuesta es JSON

// Verificar que se haya recibido el cliente_id
if (!isset($_GET['cliente_id'])) {
    echo json_encode(["success" => false, "mensaje" => "ID de cliente no proporcionado."]);
    exit();
}

$clienteId = $_GET['cliente_id'];

// Consulta SQL para obtener los productos del cliente, incluyendo 'causal_agotado'.
// ¡IMPORTANTE! Se han eliminado 'observaciones_visibilidad' y la coma extra al final
// para que coincida exactamente con tu estructura de DB (virtual_ipv.sql).
$sql = "SELECT
            id, cliente_id, nombre_cliente, empresa, codigo_barras, marca,
            categoria, segmento, descripcion, presentacion, unidad_presentacion,
            agotados, inventarios, sugeridos, unidades_surtidas, devoluciones,
            averias, transferencias, precios, ventas, precio_producto, vigencia,
            competencia, causal_agotado
        FROM productos_cliente
        WHERE cliente_id = ?"; // Filtramos por el cliente_id

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(["success" => false, "mensaje" => "Error al preparar la consulta: " . $conn->error]);
    exit();
}

$stmt->bind_param("i", $clienteId); // 'i' indica que clienteId es un entero
$stmt->execute();
$result = $stmt->get_result();

$productos = array();
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

$stmt->close();
$conn->close();

if (!empty($productos)) {
    echo json_encode(["success" => true, "productos" => $productos]);
} else {
    echo json_encode(["success" => false, "mensaje" => "No se encontraron productos para el cliente con ID: $clienteId."]);
}

?>