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

if (empty($data) || !isset($data['reporte_data']) || !isset($data['productos_devoluciones'])) {
    echo json_encode(["success" => false, "mensaje" => "Datos incompletos o formato incorrecto."]);
    exit();
}

$reporteData = $data['reporte_data'];
$productosDevoluciones = $data['productos_devoluciones'];

$cliente_id = $reporteData['cliente_id'];
$punto_venta_id = $reporteData['punto_venta_id'];
$usuario_movil_id = $reporteData['usuario_movil_id'];
$fecha_reporte = $reporteData['fecha_reporte'];
$tipo_modulo = 'devoluciones'; // Tipo fijo para este script

$conn->begin_transaction(); // Iniciar una transacción

try {
    // 🚨 VERIFICACIÓN ANTIDUPLICADOS para el REPORTE: revisar si ya existe un reporte reciente
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
        echo json_encode(["success" => false, "mensaje" => "Ya existe un reporte de devoluciones enviado recientemente para este punto de venta y usuario."]);
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

    // 2. Insertar en la tabla 'detalle_devoluciones' y luego en 'devoluciones_entradas' para cada producto
    if (!empty($productosDevoluciones)) {
        // Preparamos los statements una sola vez fuera del bucle de productos
        $stmt_detalle = $conn->prepare("INSERT INTO detalle_devoluciones (reporte_id, codigo_barras, nombre_producto, marca_producto, causal_devolucion) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_detalle === false) {
            throw new Exception("Error al preparar la consulta de detalle de devoluciones: " . $conn->error);
        }

        // Este statement se usará para la inserción, pero haremos una verificación antes.
        $stmt_entradas_insert = $conn->prepare("INSERT INTO devoluciones_entradas (detalle_devolucion_id, cantidad, fecha) VALUES (?, ?, ?)");
        if ($stmt_entradas_insert === false) {
            throw new Exception("Error al preparar la consulta de inserción de devoluciones_entradas: " . $conn->error);
        }

        foreach ($productosDevoluciones as $producto) {
            $codigo_barras = isset($producto['codigo_barras']) ? $producto['codigo_barras'] : null;
            $nombre_producto = isset($producto['descripcion']) ? $producto['descripcion'] : null;
            $marca_producto = isset($producto['marca']) ? $producto['marca'] : null;
            $causal_devolucion = isset($producto['causal_devolucion']) ? $producto['causal_devolucion'] : null;

            if ($causal_devolucion === '') {
                $causal_devolucion = null;
            }

            // Opcional: Verificar si el producto ya existe en detalle_devoluciones para este reporte_id
            $stmt_check_detail_exist = $conn->prepare("SELECT id FROM detalle_devoluciones WHERE reporte_id = ? AND codigo_barras = ?");
            $stmt_check_detail_exist->bind_param("is", $reporte_id, $codigo_barras);
            $stmt_check_detail_exist->execute();
            $stmt_check_detail_exist->store_result();
            
            $detalle_devolucion_id = null;
            if ($stmt_check_detail_exist->num_rows > 0) {
                $stmt_check_detail_exist->bind_result($existing_id);
                $stmt_check_detail_exist->fetch();
                $detalle_devolucion_id = $existing_id;
                error_log("Producto con código de barras {$codigo_barras} ya existe en detalle_devoluciones para el reporte ID {$reporte_id}. Usando ID existente: {$detalle_devolucion_id}.");
            } else {
                // Insertar en detalle_devoluciones
                $stmt_detalle->bind_param("issss", $reporte_id, $codigo_barras, $nombre_producto, $marca_producto, $causal_devolucion);
                if (!$stmt_detalle->execute()) {
                    throw new Exception("Error al insertar en detalle_devoluciones para producto " . $codigo_barras . ": " . $stmt_detalle->error);
                }
                $detalle_devolucion_id = $conn->insert_id; // Obtener el ID de la fila recién insertada
            }
            $stmt_check_detail_exist->close();

            // Verificar si hay entradas de devolución (entradas_devolucion)
            if (isset($producto['entradas_devolucion']) && is_array($producto['entradas_devolucion']) && !empty($producto['entradas_devolucion'])) {
                foreach ($producto['entradas_devolucion'] as $entry) {
                    $cantidad_entrada = isset($entry['cantidad']) ? (int)$entry['cantidad'] : 0;
                    $fecha_entrada = isset($entry['fecha']) && !empty($entry['fecha']) ? $entry['fecha'] : null;

                    if ($cantidad_entrada > 0) { // Solo guarda si la cantidad es mayor que 0
                        // **** INICIO DE LA NUEVA LÓGICA PARA EVITAR DUPLICADOS ****
                        $stmt_check_entry_duplicate = $conn->prepare("SELECT id FROM devoluciones_entradas WHERE detalle_devolucion_id = ? AND cantidad = ? AND fecha = ?");
                        if ($stmt_check_entry_duplicate === false) {
                            throw new Exception("Error al preparar la consulta de verificación de entrada de devolución duplicada: " . $conn->error);
                        }
                        $stmt_check_entry_duplicate->bind_param("iis", $detalle_devolucion_id, $cantidad_entrada, $fecha_entrada);
                        $stmt_check_entry_duplicate->execute();
                        $stmt_check_entry_duplicate->store_result();

                        if ($stmt_check_entry_duplicate->num_rows == 0) {
                            // Si NO existe una entrada idéntica, entonces inserta
                            $stmt_entradas_insert->bind_param("iis", $detalle_devolucion_id, $cantidad_entrada, $fecha_entrada);
                            if (!$stmt_entradas_insert->execute()) {
                                throw new Exception("Error al insertar entrada de devolución para producto " . $codigo_barras . " (cantidad: " . $cantidad_entrada . ", fecha: " . $fecha_entrada . "): " . $stmt_entradas_insert->error);
                            }
                        } else {
                            // Mensaje de log si se detecta un duplicado y se omite
                            error_log("Entrada de devolución duplicada detectada y omitida para producto {$codigo_barras} (detalle_devolucion_id: {$detalle_devolucion_id}, cantidad: {$cantidad_entrada}, fecha: {$fecha_entrada}).");
                        }
                        $stmt_check_entry_duplicate->close();
                        // **** FIN DE LA NUEVA LÓGICA ****
                    } else {
                        error_log("Saltando entrada de devolución para producto {$codigo_barras} con cantidad 0 o inválida.");
                    }
                }
            } else {
                error_log("Producto {$codigo_barras} no tiene 'entradas_devolucion' o está vacío.");
            }
        }
        $stmt_detalle->close();
        $stmt_entradas_insert->close(); // Cerrar el statement de inserción
    }

    $conn->commit(); // Confirmar la transacción
    echo json_encode(["success" => true, "mensaje" => "Reporte de devoluciones guardado correctamente con ID: " . $reporte_id]);

} catch (Exception $e) {
    $conn->rollback(); // Revertir la transacción en caso de error
    error_log("Error en api_guardar_devoluciones.php: " . $e->getMessage()); // Para depuración
    echo json_encode(["success" => false, "mensaje" => "Error al guardar el reporte: " . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>