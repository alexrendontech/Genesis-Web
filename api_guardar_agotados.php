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

// Obtener el JSON enviado desde la aplicación Android
$input = file_get_contents('php://input');
$data = json_decode($input, true); // Decodificar el JSON completo

// Verificar si se recibieron datos
if (empty($data) || !isset($data['reporte_data']) || !isset($data['productos_agotados'])) {
    echo json_encode(["success" => false, "mensaje" => "Datos incompletos o formato incorrecto."]);
    exit();
}

$reporteData = $data['reporte_data'];
$productosAgotados = $data['productos_agotados'];

$cliente_id = $reporteData['cliente_id'];
$punto_venta_id = $reporteData['punto_venta_id'];
$usuario_movil_id = $reporteData['usuario_movil_id'];
$fecha_reporte = $reporteData['fecha_reporte']; // Asumimos formato 'YYYY-MM-DD HH:MM:SS'
$tipo_modulo = 'agotados'; // Tipo fijo para este script

$conn->begin_transaction(); // Iniciar una transacción

try {
    // 🚨 VERIFICACIÓN ANTIDUPLICADOS para el REPORTE (ya existente y funcionando)
    $verificar_sql = "
        SELECT id FROM reportes 
        WHERE cliente_id = ? AND punto_venta_id = ? AND usuario_movil_id = ? AND tipo_modulo = ? 
        AND ABS(TIMESTAMPDIFF(SECOND, fecha_reporte, ?)) < 60
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
        echo json_encode(["success" => false, "mensaje" => "Ya existe un reporte de agotados enviado recientemente para este punto de venta y usuario."]);
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

    // 2. Insertar en la tabla 'detalle_agotados' para cada producto
    if (!empty($productosAgotados)) {
        // Prepare a statement to check for existing products in detail_agotados for this report_id
        $stmt_check_detail = $conn->prepare("SELECT id FROM detalle_agotados WHERE reporte_id = ? AND codigo_barras = ?");
        if ($stmt_check_detail === false) {
            throw new Exception("Error al preparar la consulta de verificación de detalle: " . $conn->error);
        }

        $stmt_detalle = $conn->prepare("INSERT INTO detalle_agotados (reporte_id, codigo_barras, nombre_producto, marca_producto, agotados, causal_agotado) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_detalle === false) {
            throw new Exception("Error al preparar la consulta de detalle de agotados: " . $conn->error);
        }

        foreach ($productosAgotados as $producto) {
            $codigo_barras = isset($producto['codigo_barras']) ? $producto['codigo_barras'] : null;
            $nombre_producto = isset($producto['descripcion']) ? $producto['descripcion'] : null;
            $marca_producto = isset($producto['marca']) ? $producto['marca'] : null;
            $agotados = isset($producto['agotados']) ? (int)$producto['agotados'] : 0;
            $causal_agotado = isset($producto['causal_agotado']) ? $producto['causal_agotado'] : null;

            if ($causal_agotado === '') {
                $causal_agotado = null;
            }

            // 🚨 NUEVA VERIFICACIÓN ANTIDUPLICADOS para el DETALLE
            $stmt_check_detail->bind_param("is", $reporte_id, $codigo_barras);
            $stmt_check_detail->execute();
            $stmt_check_detail->store_result();

            if ($stmt_check_detail->num_rows > 0) {
                // This product already exists for this report_id, skip insertion
                error_log("Producto con código de barras {$codigo_barras} ya existe para el reporte ID {$reporte_id}. Saltando inserción duplicada.");
                continue; // Skip to the next product
            }

            $stmt_detalle->bind_param("isssis", $reporte_id, $codigo_barras, $nombre_producto, $marca_producto, $agotados, $causal_agotado);

            if (!$stmt_detalle->execute()) {
                throw new Exception("Error al insertar detalle de agotado para producto " . $codigo_barras . ": " . $stmt_detalle->error);
            }
        }
        $stmt_detalle->close();
        $stmt_check_detail->close(); // Close the detail check statement
    }

    $conn->commit(); // Confirmar la transacción
    echo json_encode(["success" => true, "mensaje" => "Reporte de agotados guardado correctamente con ID: " . $reporte_id]);

} catch (Exception $e) {
    $conn->rollback(); // Revertir la transacción en caso de error
    error_log("Error en api_guardar_agotados.php: " . $e->getMessage()); // Para depuración
    echo json_encode(["success" => false, "mensaje" => "Error al guardar el reporte: " . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>