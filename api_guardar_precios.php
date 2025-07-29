<?php
include 'conexion.php';

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

if (empty($data) || !isset($data['reporte_data']) || !isset($data['productos_precios'])) {
    echo json_encode(["success" => false, "mensaje" => "Datos incompletos o formato incorrecto."]);
    exit();
}

$reporteData = $data['reporte_data'];
$productosPrecios = $data['productos_precios'];

$cliente_id = $reporteData['cliente_id'];
$punto_venta_id = $reporteData['punto_venta_id'];
$usuario_movil_id = $reporteData['usuario_movil_id'];
$fecha_reporte = $reporteData['fecha_reporte'];
$tipo_modulo = 'precios';

// ❌ Se elimina la generación y verificación de hash_productos.
//    Se reemplaza por una verificación basada en tiempo, similar a otros módulos.

// ❌ ELIMINAR PRODUCTOS DUPLICADOS por código de barras (dentro del mismo envío)
$productosUnicos = [];
$codigosRegistrados = [];

foreach ($productosPrecios as $producto) {
    $codigo = $producto['codigo_barras'];
    if (!in_array($codigo, $codigosRegistrados)) {
        $productosUnicos[] = $producto;
        $codigosRegistrados[] = $codigo;
    }
}

$conn->begin_transaction();

try {
    // 🚨 NUEVA VERIFICACIÓN ANTIDUPLICADOS para el REPORTE (basada en tiempo, 60 segundos)
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
        echo json_encode(["success" => false, "mensaje" => "Ya existe un reporte de precios enviado recientemente para este punto de venta y usuario (dentro de los últimos 60 segundos)."]);
        exit();
    }
    $stmt_verificar->close();

    // Insertar en 'reportes'
    // Se elimina el campo hash_productos de la inserción
    $stmt_reporte = $conn->prepare("
        INSERT INTO reportes (cliente_id, punto_venta_id, usuario_movil_id, fecha_reporte, tipo_modulo) 
        VALUES (?, ?, ?, ?, ?)
    ");
    if ($stmt_reporte === false) {
        throw new Exception("Error al preparar la consulta de reporte: " . $conn->error);
    }
    $stmt_reporte->bind_param("iiiss", $cliente_id, $punto_venta_id, $usuario_movil_id, $fecha_reporte, $tipo_modulo);
    
    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al insertar en tabla 'reportes': " . $stmt_reporte->error);
    }
    $reporte_id = $conn->insert_id;
    $stmt_reporte->close();

    // Insertar productos filtrados
    if (!empty($productosUnicos)) {
        $stmt_detalle = $conn->prepare("
            INSERT INTO detalle_precios (reporte_id, codigo_barras, nombre_producto, marca_producto, precio) 
            VALUES (?, ?, ?, ?, ?)
        ");
        if ($stmt_detalle === false) {
            throw new Exception("Error al preparar la consulta de detalle de precios: " . $conn->error);
        }

        foreach ($productosUnicos as $producto) {
            $codigo_barras = $producto['codigo_barras'] ?? null;
            $nombre_producto = $producto['descripcion'] ?? null;
            $marca_producto = $producto['marca'] ?? null;
            $precio = $producto['precio_producto'] ?? null;
            if ($precio === '') $precio = null; // Asegurar que un string vacío se convierta a NULL

            $stmt_detalle->bind_param("issss", $reporte_id, $codigo_barras, $nombre_producto, $marca_producto, $precio);
            
            if (!$stmt_detalle->execute()) {
                throw new Exception("Error al insertar detalle de precio para producto " . $codigo_barras . ": " . $stmt_detalle->error);
            }
        }
        $stmt_detalle->close();
    }

    $conn->commit();
    echo json_encode(["success" => true, "mensaje" => "Reporte de precios guardado correctamente con ID: $reporte_id"]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en api_guardar_precios.php: " . $e->getMessage());
    echo json_encode(["success" => false, "mensaje" => "Error al guardar el reporte: " . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>


