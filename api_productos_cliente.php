<?php
// api_productos_cliente.php

require 'conexion.php'; // Asegúrate de que la ruta a tu archivo de conexión sea correcta

header('Content-Type: application/json');

// Permitir solicitudes de cualquier origen (para desarrollo).
// En un entorno de producción, considera restringir esto a dominios específicos.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST'); // Permite GET y POST
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With'); // Headers permitidos

// Manejar la solicitud OPTIONS previa al CORS, si es necesario
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (isset($_GET['cliente_id'])) {
    $cliente_id = (int)$_GET['cliente_id'];

    // Consulta SQL corregida para seleccionar SOLO las columnas que existen en productos_cliente
    // según el schema de virtual_ipv.sql y que son relevantes como indicadores/módulos.
    $sql = "SELECT
                id, cliente_id, nombre_cliente, empresa, codigo_barras, marca, categoria,
                segmento, descripcion, presentacion, unidad_presentacion,
                agotados, inventarios, sugeridos, unidades_surtidas, devoluciones,
                averias, transferencias, precios, ventas, precio_producto, vigencia, competencia
                competencia, actividades
            FROM productos_cliente
            WHERE cliente_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // En un entorno de producción, evita mostrar detalles internos del error.
        error_log('Error al preparar la consulta: ' . $conn->error); // Log el error
        echo json_encode(['error' => 'Error interno del servidor al procesar la solicitud.']);
        $conn->close();
        exit();
    }

    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        // Asegurarse de que los booleanos se devuelvan como enteros (0 o 1)
        // Castear TODOS los campos tinyint(1) a (int)
        $row['agotados'] = (int)$row['agotados'];
        $row['inventarios'] = (int)$row['inventarios'];
        $row['sugeridos'] = (int)$row['sugeridos'];
        $row['unidades_surtidas'] = (int)$row['unidades_surtidas'];
        $row['devoluciones'] = (int)$row['devoluciones'];
        $row['averias'] = (int)$row['averias'];
        $row['transferencias'] = (int)$row['transferencias'];
        $row['precios'] = (int)$row['precios'];
        $row['ventas'] = (int)$row['ventas'];
        $row['precio_producto'] = (int)$row['precio_producto'];
        $row['vigencia'] = (int)$row['vigencia'];
        $row['competencia'] = (int)$row['competencia'];
         $row['actividades'] = (int)$row['actividades']; // AQUÍ SE CONVIERTE EL NUEVO CAMPO

        $productos[] = $row;
    }

    echo json_encode(['productos' => $productos]);

    $stmt->close();

} else {
    // Si no se proporciona el ID del cliente
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de cliente no proporcionado.']);
}

$conn->close();
?>