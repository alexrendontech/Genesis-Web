<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$cliente_id_sesion = $_SESSION['cliente_id'] ?? null;

include 'conexion.php';

$analysis_data = [];
$error_message = "";

// Función auxiliar para bind_param con arrays
function refValues($arr){
    $refs = [];
    foreach($arr as $key => $value){
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

try {
    if (!$conn) {
        throw new Exception("No se pudo establecer conexión con la base de datos.");
    }

    // Determine if we need to filter by client_id
    $filter_by_client = ($rol === 'cliente');
    $client_condition_sql = $filter_by_client ? " AND r.cliente_id = ?" : "";
    $client_condition_sql_rfa = $filter_by_client ? " AND rfa.cliente_id = ?" : "";
    $client_param_type = $filter_by_client ? "i" : "";
    $client_param = $filter_by_client ? [$cliente_id_sesion] : [];

    if ($filter_by_client && $cliente_id_sesion === null) {
        throw new Exception("Error: El ID del cliente no está disponible para su sesión.");
    }
    
    // Arrays para almacenar los resultados del análisis
    $analysis_results = [
        'agotados' => [],
        'precios' => [],
        'inventarios' => [],
        'devoluciones' => [],
        'actividades' => [],
        'duplicados' => []
    ];

    // --- ANÁLISIS DE AGOTADOS ---
    // Top 5 Productos más agotados
    $sql_agotados_productos = "
        SELECT da.nombre_producto, COUNT(da.id) AS veces_agotado
        FROM detalle_agotados da
        JOIN reportes r ON da.reporte_id = r.id
        WHERE 1=1 $client_condition_sql
        GROUP BY da.nombre_producto
        ORDER BY veces_agotado DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_agotados_productos);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['agotados']['top_productos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Top 5 Puntos de Venta con más agotados
    $sql_agotados_pv = "
        SELECT pv.nombre AS punto_venta, COUNT(da.id) AS total_agotados
        FROM detalle_agotados da
        JOIN reportes r ON da.reporte_id = r.id
        JOIN puntos_venta pv ON r.punto_venta_id = pv.id
        WHERE 1=1 $client_condition_sql
        GROUP BY pv.nombre
        ORDER BY total_agotados DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_agotados_pv);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['agotados']['top_puntos_venta'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // --- ANÁLISIS DE PRECIOS ---
    // Top 5 Productos con más cambios de precio
    $sql_precios_productos = "
        SELECT dp.nombre_producto, COUNT(dp.id) AS total_cambios
        FROM detalle_precios dp
        JOIN reportes r ON dp.reporte_id = r.id
        WHERE 1=1 $client_condition_sql
        GROUP BY dp.nombre_producto
        ORDER BY total_cambios DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_precios_productos);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['precios']['top_productos_cambios'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // --- ANÁLISIS DE INVENTARIOS ---
    // Top 5 Puntos de Venta con menor promedio de inventario (posibles problemas de stock)
    $sql_inventarios_pv_menor = "
        SELECT pv.nombre AS punto_venta, AVG(di.inventarios) AS promedio_inventario
        FROM detalle_inventarios di
        JOIN reportes r ON di.reporte_id = r.id
        JOIN puntos_venta pv ON r.punto_venta_id = pv.id
        WHERE 1=1 $client_condition_sql
        GROUP BY pv.nombre
        ORDER BY promedio_inventario ASC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_inventarios_pv_menor);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['inventarios']['top_pv_menor_inventario'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Top 5 Productos con mayor inventario (posible sobrestock)
    $sql_inventarios_productos_mayor = "
        SELECT di.nombre_producto, SUM(di.inventarios) AS total_inventario
        FROM detalle_inventarios di
        JOIN reportes r ON di.reporte_id = r.id
        WHERE 1=1 $client_condition_sql
        GROUP BY di.nombre_producto
        ORDER BY total_inventario DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_inventarios_productos_mayor);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['inventarios']['top_productos_mayor_inventario'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // --- ANÁLISIS DE DEVOLUCIONES ---
    // Top 5 Causales de devolución más frecuentes
    $sql_devoluciones_causales = "
        SELECT dd.causal_devolucion, COUNT(dd.id) AS total_devoluciones
        FROM detalle_devoluciones dd
        JOIN reportes r ON dd.reporte_id = r.id
        WHERE 1=1 $client_condition_sql
        GROUP BY dd.causal_devolucion
        ORDER BY total_devoluciones DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_devoluciones_causales);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['devoluciones']['top_causales'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Top 5 Productos más devueltos (por cantidad de unidades)
    $sql_devoluciones_productos = "
        SELECT dd.nombre_producto, SUM(de.cantidad) AS total_unidades_devueltas
        FROM detalle_devoluciones dd
        JOIN devoluciones_entradas de ON dd.id = de.detalle_devolucion_id
        JOIN reportes r ON dd.reporte_id = r.id
        WHERE 1=1 $client_condition_sql
        GROUP BY dd.nombre_producto
        ORDER BY total_unidades_devueltas DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_devoluciones_productos);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['devoluciones']['top_productos_devueltos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // --- ANÁLISIS DE ACTIVIDADES (FOTOS) ---
    // Top 5 Usuarios que más fotos han subido
    $sql_actividades_usuarios = "
        SELECT u.nombre AS usuario, COUNT(rfa.id) AS total_fotos
        FROM reporte_fotos_actividades rfa
        JOIN usuarios u ON rfa.usuario_movil_id = u.id
        WHERE 1=1 $client_condition_sql_rfa
        GROUP BY u.nombre
        ORDER BY total_fotos DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_actividades_usuarios);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['actividades']['top_usuarios_fotos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Top 5 Puntos de Venta con más fotos subidas
    $sql_actividades_pv = "
        SELECT pv.nombre AS punto_venta, COUNT(rfa.id) AS total_fotos
        FROM reporte_fotos_actividades rfa
        JOIN puntos_venta pv ON rfa.punto_venta_id = pv.id
        WHERE 1=1 $client_condition_sql_rfa
        GROUP BY pv.nombre
        ORDER BY total_fotos DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_actividades_pv);
    if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
    $stmt->execute();
    $analysis_results['actividades']['top_pv_fotos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // --- ANÁLISIS DE FOTOS DUPLICADAS (PARA ADMINISTRADOR Y SUPERVISOR) ---
    // The previous bug was here: this block was only for 'administrador'.
    // Now it checks for 'administrador' OR 'supervisor'.
    if ($rol === 'administrador' || $rol === 'supervisor') {
        // Conteo total de fotos duplicadas
        $sql_total_duplicados = "SELECT COUNT(id) AS total_duplicados FROM reporte_fotos_actividades WHERE es_reusada = TRUE";
        // If client filter is applicable, add it to the total count query
        if ($filter_by_client) {
            $sql_total_duplicados .= $client_condition_sql_rfa; // Assuming rfa table for client_id
            $stmt = $conn->prepare($sql_total_duplicados);
            call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param)));
            $stmt->execute();
            $result = $stmt->get_result();
            $analysis_results['duplicados']['total_duplicados'] = $result ? $result->fetch_assoc()['total_duplicados'] : 0;
            $stmt->close();
        } else {
            $result = $conn->query($sql_total_duplicados);
            $analysis_results['duplicados']['total_duplicados'] = $result ? $result->fetch_assoc()['total_duplicados'] : 0;
        }

        // Top 5 Usuarios que más fotos duplicadas han subido
        $sql_duplicados_usuarios = "
            SELECT u.nombre AS usuario, COUNT(rfa.id) AS fotos_duplicadas
            FROM reporte_fotos_actividades rfa
            JOIN usuarios u ON rfa.usuario_movil_id = u.id
            WHERE rfa.es_reusada = TRUE $client_condition_sql_rfa
            GROUP BY u.nombre
            ORDER BY fotos_duplicadas DESC
            LIMIT 5
        ";
        $stmt = $conn->prepare($sql_duplicados_usuarios);
        if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
        $stmt->execute();
        $analysis_results['duplicados']['top_usuarios'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Top 5 Puntos de Venta con más fotos duplicadas subidas
        $sql_duplicados_pv = "
            SELECT pv.nombre AS punto_venta, COUNT(rfa.id) AS fotos_duplicadas
            FROM reporte_fotos_actividades rfa
            JOIN puntos_venta pv ON rfa.punto_venta_id = pv.id
            WHERE rfa.es_reusada = TRUE $client_condition_sql_rfa
            GROUP BY pv.nombre
            ORDER BY fotos_duplicadas DESC
            LIMIT 5
        ";
        $stmt = $conn->prepare($sql_duplicados_pv);
        if ($filter_by_client) { call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$client_param_type], $client_param))); }
        $stmt->execute();
        $analysis_results['duplicados']['top_puntos_venta'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

} catch (Exception $e) {
    $error_message = "Error al cargar los análisis: " . $e->getMessage();
} finally {
    if ($conn) $conn->close();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis Detallado de Reportes - IPV</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { width: 90%; max-width: 1200px; margin: 20px auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #333; }
        h2 { border-bottom: 2px solid #eee; padding-bottom: 5px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 14px; }
        th { background-color: #f2f2f2; }
        .section-separator { border-top: 1px dashed #ccc; margin: 30px 0; }
        .message { padding: 10px; background-color: #ffe0b2; border: 1px solid #ffcc80; border-radius: 4px; color: #333; }
        .error-message { background-color: #ffcdd2; border: 1px solid #ef9a9a; }
        ul { list-style-type: none; padding: 0; margin: 0; }
        li { background-color: #f9f9f9; padding: 8px; margin-bottom: 5px; border-left: 3px solid #007bff; }
        li:nth-child(even) { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php">&larr; Volver al Panel</a> |
        <a href="ver_reporte.php">Volver a Reportes Tabulares</a>
        <h1>Análisis Detallado de Reportes</h1>

        <?php if ($error_message): ?>
            <p class="message error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php elseif ($rol === 'cliente' && $cliente_id_sesion === null): ?>
            <p class="message error-message">No se pudo cargar los análisis para su cliente. Contacte al administrador.</p>
        <?php elseif ($rol === 'cliente' && empty($analysis_results['agotados']['top_productos']) && empty($analysis_results['precios']['top_productos_cambios']) && empty($analysis_results['inventarios']['top_pv_menor_inventario']) && empty($analysis_results['devoluciones']['top_causales']) && empty($analysis_results['actividades']['top_usuarios_fotos'])): ?>
            <p class="message">No hay suficientes datos para generar análisis detallados para su cliente.</p>
        <?php elseif (($rol === 'administrador' || $rol === 'supervisor') && empty($analysis_results['agotados']['top_productos']) && empty($analysis_results['precios']['top_productos_cambios']) && empty($analysis_results['inventarios']['top_pv_menor_inventario']) && empty($analysis_results['devoluciones']['top_causales']) && empty($analysis_results['actividades']['top_usuarios_fotos']) && (($rol === 'administrador' || $rol === 'supervisor') && empty($analysis_results['duplicados']['total_duplicados']))): ?>
            <p class="message">No hay suficientes datos para generar análisis detallados en este momento.</p>
        <?php else: ?>

            <h2>Análisis de Agotados</h2>
            <?php if (!empty($analysis_results['agotados']['top_productos'])): ?>
                <h3>Top 5 Productos más frecuentemente agotados:</h3>
                <table>
                    <thead>
                        <tr><th>Producto</th><th>Veces Agotado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['agotados']['top_productos'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['nombre_producto']) ?></td><td><?= htmlspecialchars($row['veces_agotado']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay datos suficientes para el análisis de agotados.</p>
            <?php endif; ?>

            <?php if (!empty($analysis_results['agotados']['top_puntos_venta'])): ?>
                <h3>Top 5 Puntos de Venta con más agotados:</h3>
                <table>
                    <thead>
                        <tr><th>Punto de Venta</th><th>Total Agotados</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['agotados']['top_puntos_venta'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['punto_venta']) ?></td><td><?= htmlspecialchars($row['total_agotados']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="section-separator"></div>

            <h2>Análisis de Precios</h2>
            <?php if (!empty($analysis_results['precios']['top_productos_cambios'])): ?>
                <h3>Top 5 Productos con más cambios de precio:</h3>
                <table>
                    <thead>
                        <tr><th>Producto</th><th>Total Cambios</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['precios']['top_productos_cambios'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['nombre_producto']) ?></td><td><?= htmlspecialchars($row['total_cambios']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay datos suficientes para el análisis de precios.</p>
            <?php endif; ?>

            <div class="section-separator"></div>

            <h2>Análisis de Inventarios</h2>
            <?php if (!empty($analysis_results['inventarios']['top_pv_menor_inventario'])): ?>
                <h3>Top 5 Puntos de Venta con menor promedio de inventario (posibles problemas de stock):</h3>
                <table>
                    <thead>
                        <tr><th>Punto de Venta</th><th>Promedio Inventario</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['inventarios']['top_pv_menor_inventario'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['punto_venta']) ?></td><td><?= number_format($row['promedio_inventario'], 2) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay datos suficientes para el análisis de inventarios.</p>
            <?php endif; ?>

            <?php if (!empty($analysis_results['inventarios']['top_productos_mayor_inventario'])): ?>
                <h3>Top 5 Productos con mayor inventario (posible sobrestock):</h3>
                <table>
                    <thead>
                        <tr><th>Producto</th><th>Total Inventario</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['inventarios']['top_productos_mayor_inventario'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['nombre_producto']) ?></td><td><?= htmlspecialchars($row['total_inventario']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="section-separator"></div>

            <h2>Análisis de Devoluciones</h2>
            <?php if (!empty($analysis_results['devoluciones']['top_causales'])): ?>
                <h3>Top 5 Causales de devolución más frecuentes:</h3>
                <table>
                    <thead>
                        <tr><th>Causal de Devolución</th><th>Total Devoluciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['devoluciones']['top_causales'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['causal_devolucion']) ?></td><td><?= htmlspecialchars($row['total_devoluciones']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay datos suficientes para el análisis de devoluciones.</p>
            <?php endif; ?>

            <?php if (!empty($analysis_results['devoluciones']['top_productos_devueltos'])): ?>
                <h3>Top 5 Productos más devueltos (por unidades):</h3>
                <table>
                    <thead>
                        <tr><th>Producto</th><th>Total Unidades Devueltas</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['devoluciones']['top_productos_devueltos'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['nombre_producto']) ?></td><td><?= htmlspecialchars($row['total_unidades_devueltas']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="section-separator"></div>

            <h2>Análisis de Actividades (Fotos)</h2>
            <?php if (!empty($analysis_results['actividades']['top_usuarios_fotos'])): ?>
                <h3>Top 5 Usuarios que más fotos han subido:</h3>
                <table>
                    <thead>
                        <tr><th>Usuario</th><th>Total Fotos Subidas</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['actividades']['top_usuarios_fotos'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['usuario']) ?></td><td><?= htmlspecialchars($row['total_fotos']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay datos suficientes para el análisis de actividades (fotos).</p>
            <?php endif; ?>

            <?php if (!empty($analysis_results['actividades']['top_pv_fotos'])): ?>
                <h3>Top 5 Puntos de Venta con más fotos subidas:</h3>
                <table>
                    <thead>
                        <tr><th>Punto de Venta</th><th>Total Fotos Subidas</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis_results['actividades']['top_pv_fotos'] as $row): ?>
                            <tr><td><?= htmlspecialchars($row['punto_venta']) ?></td><td><?= htmlspecialchars($row['total_fotos']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($rol === 'administrador' || $rol === 'supervisor'): // Changed condition here ?>
                <div class="section-separator"></div>
                <h2>Análisis de Fotos Duplicadas</h2>
                <?php if ($analysis_results['duplicados']['total_duplicados'] > 0): ?>
                    <h3>Total de Fotos Marcadas como Duplicadas: <?= htmlspecialchars($analysis_results['duplicados']['total_duplicados']) ?></h3>
                    <?php if (!empty($analysis_results['duplicados']['top_usuarios'])): ?>
                        <h3>Top 5 Usuarios con más fotos duplicadas subidas:</h3>
                        <table>
                            <thead>
                                <tr><th>Usuario</th><th>Fotos Duplicadas</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analysis_results['duplicados']['top_usuarios'] as $row): ?>
                                    <tr><td><?= htmlspecialchars($row['usuario']) ?></td><td><?= htmlspecialchars($row['fotos_duplicadas']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if (!empty($analysis_results['duplicados']['top_puntos_venta'])): ?>
                        <h3>Top 5 Puntos de Venta con más fotos duplicadas:</h3>
                        <table>
                            <thead>
                                <tr><th>Punto de Venta</th><th>Fotos Duplicadas</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analysis_results['duplicados']['top_puntos_venta'] as $row): ?>
                                    <tr><td><?= htmlspecialchars($row['punto_venta']) ?></td><td><?= htmlspecialchars($row['fotos_duplicadas']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No hay fotos duplicadas registradas actualmente para análisis.</p>
                <?php endif; ?>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>