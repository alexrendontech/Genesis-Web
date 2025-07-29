<?php
include 'conexion.php'; // Asegúrate de que este archivo contenga la conexión a tu base de datos

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data) || !isset($data['reporte_data']) || !isset($data['productos_inventarios'])) {
    echo json_encode(["success" => false, "mensaje" => "Datos incompletos o formato incorrecto."]);
    exit();
}

$reporteData = $data['reporte_data'];
$productosInventarios = $data['productos_inventarios'];

$cliente_id = $reporteData['cliente_id'];
$punto_venta_id = $reporteData['punto_venta_id'];
$usuario_movil_id = $reporteData['usuario_movil_id'];
$fecha_reporte = $reporteData['fecha_reporte'];
$tipo_modulo = 'inventario'; // Tipo fijo para este script

$conn->begin_transaction(); // Iniciar una transacción

try {
    // 🚨 VERIFICACIÓN ANTIDUPLICADOS para el REPORTE: revisar si ya existe un reporte reciente
    // Se ha aumentado la ventana de tiempo de 60 a 300 segundos (5 minutos)
    $verificar_sql = "
        SELECT id FROM reportes
        WHERE cliente_id = ? AND punto_venta_id = ? AND usuario_movil_id = ? AND tipo_modulo = ?
        AND ABS(TIMESTAMPDIFF(SECOND, fecha_reporte, ?)) < 300
    ";
    $stmt_verificar = $conn->prepare($verificar_sql);
    if ($stmt_verificar === false) {
        throw new Exception("Error al preparar la consulta de verificación de reporte: " . $conn->error);
    }
    
    $stmt_verificar->bind_param("iiiss", $cliente_id, $punto_venta_id, $usuario_movil_id, $tipo_modulo, $fecha_reporte);
    $stmt_verificar->execute();
    $stmt_verificar->store_result();

    if ($stmt_verificar->num_rows > 0) {
        $stmt_verificar->close();
        $conn->rollback();
        echo json_encode(["success" => false, "mensaje" => "Ya existe un reporte de inventario enviado recientemente para este punto de venta y usuario (dentro de los últimos 5 minutos)."]);
        exit();
    }
    $stmt_verificar->close();

    // 1. Insertar en la tabla 'reportes' (Encabezado del Reporte)
    $stmt_reporte = $conn->prepare("INSERT INTO reportes (cliente_id, punto_venta_id, usuario_movil_id, fecha_reporte, tipo_modulo) VALUES (?, ?, ?, ?, ?)");
    if ($stmt_reporte === false) {
        throw new Exception("Error al preparar la consulta de reporte: " . $conn->error);
    }

    $stmt_reporte->bind_param("iiiss", $cliente_id, $punto_venta_id, $usuario_movil_id, $fecha_reporte, $tipo_modulo);

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al insertar en tabla 'reportes': " . $stmt_reporte->error);
    }

    $reporte_id = $conn->insert_id; // Obtener el ID del reporte recién insertado
    $stmt_reporte->close();

    // 2. Insertar en la tabla 'detalle_inventarios' para cada producto
    if (!empty($productosInventarios)) {
        // Preparamos una consulta para verificar productos existentes en detalle_inventarios para este reporte_id
        $stmt_check_detail = $conn->prepare("SELECT id FROM detalle_inventarios WHERE reporte_id = ? AND codigo_barras = ?");
        if ($stmt_check_detail === false) {
            throw new Exception("Error al preparar la consulta de verificación de detalle: " . $conn->error);
        }

        $stmt_detalle = $conn->prepare("INSERT INTO detalle_inventarios (reporte_id, codigo_barras, nombre_producto, marca_producto, inventarios, sugeridos, unidades_surtidas) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_detalle === false) {
            throw new Exception("Error al preparar la consulta de detalle de inventarios: " . $conn->error);
        }

        foreach ($productosInventarios as $producto) {
            $codigo_barras = isset($producto['codigo_barras']) ? $producto['codigo_barras'] : null;
            $nombre_producto = isset($producto['descripcion']) ? $producto['descripcion'] : null;
            $marca_producto = isset($producto['marca']) ? $producto['marca'] : null;
            $inventarios = isset($producto['inventarios']) ? (int)$producto['inventarios'] : 0;
            $sugeridos = isset($producto['sugeridos']) ? (int)$producto['sugeridos'] : 0;
            $unidades_surtidas = isset($producto['unidades_surtidas']) ? (int)$producto['unidades_surtidas'] : 0;

            // 🚨 NUEVA VERIFICACIÓN ANTIDUPLICADOS para el DETALLE
            $stmt_check_detail->bind_param("is", $reporte_id, $codigo_barras);
            $stmt_check_detail->execute();
            $stmt_check_detail->store_result();

            if ($stmt_check_detail->num_rows > 0) {
                // Este producto ya existe para este reporte_id, omitimos la inserción duplicada
                error_log("Producto con código de barras {$codigo_barras} ya existe para el reporte ID {$reporte_id}. Saltando inserción duplicada.");
                continue; // Saltar al siguiente producto
            }

            // 'isssiii' significa: int, string, string, string, int, int, int
            $stmt_detalle->bind_param("isssiii", $reporte_id, $codigo_barras, $nombre_producto, $marca_producto, $inventarios, $sugeridos, $unidades_surtidas);

            if (!$stmt_detalle->execute()) {
                throw new Exception("Error al insertar detalle de inventario para producto " . $codigo_barras . ": " . $stmt_detalle->error);
            }
        }
        $stmt_detalle->close();
        $stmt_check_detail->close(); // Cerrar el statement de verificación de detalle
    }

    $conn->commit(); // Confirmar la transacción
    echo json_encode(["success" => true, "mensaje" => "Reporte de inventarios guardado correctamente con ID: " . $reporte_id]);

} catch (Exception $e) {
    $conn->rollback(); // Revertir la transacción en caso de error
    error_log("Error en api_guardar_inventarios.php: " . $e->getMessage()); // Para depuración
    echo json_encode(["success" => false, "mensaje" => "Error al guardar el reporte: " . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
