<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$cliente_id_sesion = $_SESSION['cliente_id'] ?? null;

$report_type = $_GET['tipo'] ?? '';

include 'conexion.php';

// Función auxiliar para bind_param con arrays
function refValues($arr){
    $refs = [];
    foreach($arr as $key => $value){
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Función para formatear la fecha y hora a 12 horas con AM/PM
function formatDateTime12Hour($datetime_str) {
    if (empty($datetime_str) || $datetime_str === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    // Convertir la cadena de fecha/hora a un timestamp y luego formatear
    return date('Y-m-d h:i:s A', strtotime($datetime_str));
}

// Paginación
$itemsPorPagina = 10;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $itemsPorPagina;

// Filtros
$busqueda = trim($_GET['buscar'] ?? '');
$filtro_ciudad = $_GET['filtro_ciudad'] ?? '';
$filtro_punto_venta = $_GET['filtro_punto_venta'] ?? '';

$report_data = [];
$total_registros = 0;
$report_title = "Reportes";
$table_headers = [];
$sql_select_columns = "";
$sql_join_detail = "";
$error_message = "";

try {
    // --- BLOQUE COMÚN: columnas y joins
    $common_select_columns = "
        r.fecha_reporte,
        pv.nombre AS nombre_punto_venta,
        pv.ciudad AS ciudad_punto_venta,
        u.nombre AS nombre_usuario_movil,
        r.id AS id_reporte
    ";

    $common_joins = "
        FROM reportes r
        JOIN puntos_venta pv ON r.punto_venta_id = pv.id
        JOIN usuarios u ON r.usuario_movil_id = u.id
    ";

    // Si el rol NO es cliente, unir tabla clientes para mostrar su nombre
    if ($rol != 'cliente') {
        $common_select_columns = "
            r.fecha_reporte,
            c.razon_social AS nombre_cliente,
            pv.nombre AS nombre_punto_venta,
            pv.ciudad AS ciudad_punto_venta,
            u.nombre AS nombre_usuario_movil,
            r.id AS id_reporte
        ";
        $common_joins = "
            FROM reportes r
            JOIN clientes c ON r.cliente_id = c.id
            JOIN puntos_venta pv ON r.punto_venta_id = pv.id
            JOIN usuarios u ON r.usuario_movil_id = u.id
        ";
    }

    // BEGIN filtros y búsqueda
    $where_sql = "1";
    $params = [];
    $param_types = "";

    if ($rol === 'cliente') {
        if ($cliente_id_sesion === null) {
            throw new Exception("Error: El ID del cliente no está disponible para su sesión.");
        }
        $where_sql .= " AND r.cliente_id = ?";
        $params[] = $cliente_id_sesion;
        $param_types .= "i";
        $report_title = "Mis Reportes";
    } elseif ($rol === 'administrador' || $rol === 'supervisor') {
        $report_title = ($rol === 'administrador') ? "Reportes Totales (Administrador)" : "Reportes Totales (Supervisor)";
    } else {
        throw new Exception("Rol de usuario no autorizado para ver reportes.");
    }

    if ($filtro_ciudad !== '') {
        $where_sql .= " AND pv.ciudad = ?";
        $params[] = $filtro_ciudad;
        $param_types .= "s";
    }
    if ($filtro_punto_venta !== '') {
        $where_sql .= " AND pv.nombre = ?";
        $params[] = $filtro_punto_venta;
        $param_types .= "s";
    }

    $busqueda_sql = '';
    if ($busqueda !== '') {
        $like = "%$busqueda%";
        $campos_a_buscar = ['pv.nombre', 'pv.ciudad', 'u.nombre'];
        if ($rol != 'cliente') $campos_a_buscar[] = 'c.razon_social';
        if (in_array($report_type, ['agotados','precios','inventarios','devoluciones'])) {
            $campos_a_buscar = array_merge($campos_a_buscar, [
                'detalle.codigo_barras', 'detalle.nombre_producto', 'detalle.marca_producto'
            ]);
        }
        // La búsqueda para actividades y duplicados se maneja dentro del case
        if (!in_array($report_type, ['actividades', 'duplicados'])) {
            $search_clauses = [];
            foreach ($campos_a_buscar as $campo) {
                if ($campo) {
                    $search_clauses[] = "$campo LIKE ?";
                    $params[] = $like;
                    $param_types .= "s";
                }
            }
            if ($search_clauses) {
                $busqueda_sql = " AND (" . implode(" OR ", $search_clauses) . ")";
            }
        }
    }
    // END filtros y búsqueda

    // --- Switch módulos ---
    switch ($report_type) {
        case 'agotados':
            $report_title .= " - Agotados";
            $table_headers = ['Fecha Reporte'];
            if ($rol != 'cliente') $table_headers[] = 'Cliente';
            $table_headers = array_merge($table_headers, [
                'Punto de Venta', 'Ciudad', 'Usuario Móvil', 'Código Barras', 'Descripción', 'Marca', 'Presentación', 'Cantidad Agotados', 'Causal Agotado'
            ]);
            $sql_select_columns = "
                detalle.codigo_barras, detalle.nombre_producto AS descripcion, detalle.marca_producto AS marca,
                pc.presentacion, detalle.agotados, detalle.causal_agotado
            ";
            $sql_join_detail = "
                JOIN detalle_agotados detalle ON r.id = detalle.reporte_id
                LEFT JOIN productos_cliente pc ON detalle.codigo_barras = pc.codigo_barras AND r.cliente_id = pc.cliente_id
            ";
            break;

        case 'precios':
            $report_title .= " - Precios";
            $table_headers = ['Fecha Reporte'];
            if ($rol != 'cliente') $table_headers[] = 'Cliente';
            $table_headers = array_merge($table_headers, [
                'Punto de Venta', 'Ciudad', 'Usuario Móvil', 'Código Barras', 'Descripción', 'Marca', 'Precio Nuevo', 'Justificación'
            ]);
            $sql_select_columns = "
                detalle.codigo_barras, detalle.nombre_producto AS descripcion, detalle.marca_producto AS marca,
                detalle.precio AS precio_nuevo, '' AS justificacion_cambio
            ";
            $sql_join_detail = "JOIN detalle_precios detalle ON r.id = detalle.reporte_id";
            break;

        case 'inventarios':
            $report_title .= " - Inventarios";
            $table_headers = ['Fecha Reporte'];
            if ($rol != 'cliente') $table_headers[] = 'Cliente';
            $table_headers = array_merge($table_headers, [
                'Punto de Venta', 'Ciudad', 'Usuario Móvil', 'Código Barras', 'Descripción', 'Marca', 'Inventarios', 'Sugeridos', 'Unidades Surtidas'
            ]);
            $sql_select_columns = "
                detalle.codigo_barras, detalle.nombre_producto AS descripcion, detalle.marca_producto AS marca,
                detalle.inventarios, detalle.sugeridos, detalle.unidades_surtidas
            ";
            $sql_join_detail = "JOIN detalle_inventarios detalle ON r.id = detalle.reporte_id";
            break;

        case 'devoluciones':
            $report_title .= " - Devoluciones";
            $table_headers = ['Fecha Reporte'];
            if ($rol != 'cliente') $table_headers[] = 'Cliente';
            $table_headers = array_merge($table_headers, [
                'Punto de Venta', 'Ciudad', 'Usuario Móvil', 'Código Barras', 'Descripción', 'Marca', 'Devoluciones (Cantidad - Fecha)', 'Causal Devolución'
            ]);

            // Conteo total para paginación
            $count_sql = "
                SELECT COUNT(DISTINCT dd.id) AS total
                $common_joins
                JOIN detalle_devoluciones dd ON r.id = dd.reporte_id
                WHERE $where_sql $busqueda_sql
            ";
            $stmt_count = $conn->prepare($count_sql);
            if (!$stmt_count) throw new Exception("Error preparando conteo devoluciones: " . $conn->error);
            if (!empty($params)) call_user_func_array([$stmt_count, 'bind_param'], refValues(array_merge([$param_types], $params)));
            $stmt_count->execute();
            $res_count = $stmt_count->get_result()->fetch_assoc();
            $total_registros = $res_count['total'] ?? 0;
            $stmt_count->close();

            $report_data = [];
            if ($total_registros > 0) {
                $sql_ids = "
                    SELECT DISTINCT dd.id
                    $common_joins
                    JOIN detalle_devoluciones dd ON r.id = dd.reporte_id
                    WHERE $where_sql $busqueda_sql
                    ORDER BY r.fecha_reporte DESC LIMIT ?, ?
                ";
                $stmt_ids = $conn->prepare($sql_ids);
                if (!$stmt_ids) throw new Exception("Error preparando consulta ids devoluciones: " . $conn->error);

                $all_params = array_merge($params, [$inicio, $itemsPorPagina]);
                $all_types = $param_types . "ii";
                call_user_func_array([$stmt_ids, 'bind_param'], refValues(array_merge([$all_types], $all_params)));
                $stmt_ids->execute();
                $res_ids = $stmt_ids->get_result();

                $ids = [];
                while ($row = $res_ids->fetch_assoc()) {
                    $ids[] = $row['id'];
                }
                $stmt_ids->close();

                if (count($ids) > 0) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $sql_detalle = "
                        SELECT
                            r.fecha_reporte,
                            " . ($rol != 'cliente' ? "c.razon_social AS nombre_cliente," : "") . "
                            pv.nombre AS nombre_punto_venta,
                            pv.ciudad AS ciudad_punto_venta,
                            u.nombre AS nombre_usuario_movil,
                            dd.id AS detalle_id,
                            dd.codigo_barras,
                            dd.nombre_producto,
                            dd.marca_producto,
                            dd.causal_devolucion,
                            de.cantidad,
                            de.fecha
                        $common_joins
                        JOIN detalle_devoluciones dd ON r.id = dd.reporte_id
                        LEFT JOIN devoluciones_entradas de ON dd.id = de.detalle_devolucion_id
                        WHERE dd.id IN ($placeholders) AND $where_sql
                        ORDER BY r.fecha_reporte DESC, dd.codigo_barras, de.fecha DESC
                    ";
                    $stmt_detalle = $conn->prepare($sql_detalle);
                    if (!$stmt_detalle) throw new Exception("Error preparando detalle devoluciones: " . $conn->error);

                    $types_ids = str_repeat('i', count($ids));
                    $bind_params = array_merge($ids, $params);
                    $types_all = $types_ids . $param_types;
                    call_user_func_array([$stmt_detalle, 'bind_param'], refValues(array_merge([$types_all], $bind_params)));

                    $stmt_detalle->execute();
                    $res_detalle = $stmt_detalle->get_result();

                    $productos = [];
                    while ($row = $res_detalle->fetch_assoc()) {
                        $detalle_id = $row['detalle_id'];
                        if (!isset($productos[$detalle_id])) {
                            $productos[$detalle_id] = [
                                'fecha_reporte' => $row['fecha_reporte'],
                                'nombre_cliente' => ($rol != 'cliente' ? $row['nombre_cliente'] : ''),
                                'nombre_punto_venta' => $row['nombre_punto_venta'],
                                'ciudad_punto_venta' => $row['ciudad_punto_venta'],
                                'nombre_usuario_movil' => $row['nombre_usuario_movil'],
                                'codigo_barras' => $row['codigo_barras'],
                                'descripcion' => $row['nombre_producto'],
                                'marca' => $row['marca_producto'],
                                'causal_devolucion' => $row['causal_devolucion'],
                                'devoluciones' => []
                            ];
                        }
                        if ($row['cantidad'] !== null && $row['fecha'] !== null) {
                            $productos[$detalle_id]['devoluciones'][] = [
                                'cantidad' => $row['cantidad'],
                                'fecha' => $row['fecha']
                            ];
                        }
                    }
                    $stmt_detalle->close();
                    $report_data = $productos;
                }
            }
            break;

        case 'actividades':
            $report_title .= " - Actividades (Fotos)";
            $table_headers = ['Fecha Captura'];
            if ($rol != 'cliente') $table_headers[] = 'Cliente';
            $table_headers = array_merge($table_headers, [
                'Punto de Venta', 'Ciudad', 'Usuario Móvil', 'Tipo Actividad', 'Descripción', 'Foto'
            ]);
            
            // Para actividades, consultamos directamente la tabla reporte_fotos_actividades
            // sin depender de la tabla reportes principal
            $common_select_columns = "
                rfa.fecha_hora_captura as fecha_reporte,
                " . ($rol != 'cliente' ? "c.razon_social AS nombre_cliente," : "") . "
                pv.nombre AS nombre_punto_venta,
                pv.ciudad AS ciudad_punto_venta,
                u.nombre AS nombre_usuario_movil,
                rfa.id AS id_reporte
            ";
            
            $common_joins = "
                FROM reporte_fotos_actividades rfa
                " . ($rol != 'cliente' ? "JOIN clientes c ON rfa.cliente_id = c.id" : "") . "
                JOIN puntos_venta pv ON rfa.punto_venta_id = pv.id
                JOIN usuarios u ON rfa.usuario_movil_id = u.id
            ";
            
            $sql_select_columns = "
                rfa.tipo_actividad, rfa.descripcion_actividad,
                rfa.nombre_archivo_foto, rfa.ruta_servidor_foto
            ";
            $sql_join_detail = ""; // No necesitamos JOIN adicional
            
            // Actualizamos los filtros para usar rfa en lugar de r
            $where_sql = "1";
            $params = [];
            $param_types = "";

            if ($rol === 'cliente') {
                if ($cliente_id_sesion === null) {
                    throw new Exception("Error: El ID del cliente no está disponible para su sesión.");
                }
                $where_sql .= " AND rfa.cliente_id = ?";
                $params[] = $cliente_id_sesion;
                $param_types .= "i";
            }

            if ($filtro_ciudad !== '') {
                $where_sql .= " AND pv.ciudad = ?";
                $params[] = $filtro_ciudad;
                $param_types .= "s";
            }
            if ($filtro_punto_venta !== '') {
                $where_sql .= " AND pv.nombre = ?";
                $params[] = $filtro_punto_venta;
                $param_types .= "s";
            }

            $busqueda_sql = '';
            if ($busqueda !== '') {
                $like = "%$busqueda%";
                $campos_a_buscar = ['pv.nombre', 'pv.ciudad', 'u.nombre', 'rfa.tipo_actividad', 'rfa.descripcion_actividad'];
                if ($rol != 'cliente') $campos_a_buscar[] = 'c.razon_social';
                
                $search_clauses = [];
                foreach ($campos_a_buscar as $campo) {
                    if ($campo) {
                        $search_clauses[] = "$campo LIKE ?";
                        $params[] = $like;
                        $param_types .= "s";
                    }
                }
                if ($search_clauses) {
                    $busqueda_sql = " AND (" . implode(" OR ", $search_clauses) . ")";
                }
            }
            break;

        // --- NUEVO CASO: REPORTE DE FOTOS DUPLICADAS (SOLO PARA ADMINISTRADOR) ---
        case 'duplicados':
            if ($rol !== 'administrador') { // Restringir acceso solo a administradores
                $error_message = "Acceso denegado. Este reporte solo está disponible para administradores.";
                break; // Salir del switch si no es administrador
            }
            $report_title .= " - Fotos Duplicadas";
            // AÑADIDO: IDs a los encabezados de la tabla
            $table_headers = [
                'Mercaderista', 
                'Punto de Venta', 
                'Foto Re-subida', 
                'Foto Original'
            ];
            if ($rol != 'cliente') $table_headers = array_merge(['Cliente'], $table_headers); // Añadir Cliente si no es rol cliente

            // Consulta para fotos marcadas como reusadas
            $sql_duplicados_select = "
                rfa.id AS reusada_id,
                rfa.fecha_hora_captura AS reusada_fecha,
                rfa.ruta_servidor_foto AS reusada_ruta,
                rfa.descripcion_actividad AS reusada_descripcion,
                rfa.tipo_actividad AS reusada_tipo,
                rfa.perceptual_hash AS reusada_hash,
                
                original_rfa.id AS original_id,
                original_rfa.fecha_hora_captura AS original_fecha,
                original_rfa.ruta_servidor_foto AS original_ruta,
                original_rfa.descripcion_actividad AS original_descripcion,
                original_rfa.tipo_actividad AS original_tipo,
                original_rfa.perceptual_hash AS original_hash,

                u.nombre AS nombre_usuario_movil,
                pv.nombre AS nombre_punto_venta,
                pv.ciudad AS ciudad_punto_venta
            ";

            $sql_duplicados_joins = "
                FROM reporte_fotos_actividades rfa
                JOIN reporte_fotos_actividades original_rfa ON rfa.id_foto_original = original_rfa.id
                JOIN usuarios u ON rfa.usuario_movil_id = u.id
                JOIN puntos_venta pv ON rfa.punto_venta_id = pv.id
            ";

            $duplicados_where_sql = "rfa.es_reusada = TRUE";
            $duplicados_params = [];
            $duplicados_param_types = "";

            if ($rol === 'cliente') { // Aunque el reporte es para admin, esta lógica es para el filtro general de cliente
                if ($cliente_id_sesion === null) {
                    throw new Exception("Error: El ID del cliente no está disponible para su sesión.");
                }
                $duplicados_where_sql .= " AND rfa.cliente_id = ?";
                $duplicados_params[] = $cliente_id_sesion;
                $duplicados_param_types .= "i";
            }
            if ($rol != 'cliente') {
                $sql_duplicados_select .= ", c.razon_social AS nombre_cliente";
                $sql_duplicados_joins .= " JOIN clientes c ON rfa.cliente_id = c.id";
            }

            // Aplicar filtros de búsqueda a campos relevantes para duplicados
            if ($busqueda !== '') {
                $like = "%$busqueda%";
                $search_clauses_duplicados = [];
                $search_fields_duplicados = [
                    'u.nombre', 'pv.nombre', 'pv.ciudad',
                    'rfa.descripcion_actividad', 'rfa.tipo_actividad', 'rfa.perceptual_hash',
                    'original_rfa.descripcion_actividad', 'original_rfa.tipo_actividad', 'original_rfa.perceptual_hash'
                ];
                if ($rol != 'cliente') $search_fields_duplicados[] = 'c.razon_social';

                foreach ($search_fields_duplicados as $field) {
                    $search_clauses_duplicados[] = "$field LIKE ?";
                    $duplicados_params[] = $like;
                    $duplicados_param_types .= "s";
                }
                if ($search_clauses_duplicados) {
                    $duplicados_where_sql .= " AND (" . implode(" OR ", $search_clauses_duplicados) . ")";
                }
            }
            
            if ($filtro_ciudad !== '') {
                $duplicados_where_sql .= " AND pv.ciudad = ?";
                $duplicados_params[] = $filtro_ciudad;
                $duplicados_param_types .= "s";
            }
            if ($filtro_punto_venta !== '') {
                $duplicados_where_sql .= " AND pv.nombre = ?";
                $duplicados_params[] = $filtro_punto_venta;
                $duplicados_param_types .= "s";
            }

            // Conteo total para paginación
            $count_sql_duplicados = "SELECT COUNT(*) AS total $sql_duplicados_joins WHERE $duplicados_where_sql";
            $stmt_count_duplicados = $conn->prepare($count_sql_duplicados);
            if (!$stmt_count_duplicados) throw new Exception("Error preparando conteo duplicados: " . $conn->error);
            if (!empty($duplicados_params)) call_user_func_array([$stmt_count_duplicados, 'bind_param'], refValues(array_merge([$duplicados_param_types], $duplicados_params)));
            $stmt_count_duplicados->execute();
            $res_count_duplicados = $stmt_count_duplicados->get_result()->fetch_assoc();
            $total_registros = $res_count_duplicados['total'] ?? 0;
            $stmt_count_duplicados->close();

            // Consulta de datos
            $sql_duplicados = "SELECT $sql_duplicados_select $sql_duplicados_joins WHERE $duplicados_where_sql ORDER BY rfa.fecha_hora_captura DESC LIMIT ?, ?";
            $stmt_duplicados = $conn->prepare($sql_duplicados);
            if (!$stmt_duplicados) throw new Exception("Error preparando consulta duplicados: " . $conn->error);

            $all_params_duplicados = array_merge($duplicados_params, [$inicio, $itemsPorPagina]);
            $all_types_duplicados = $duplicados_param_types . "ii";
            call_user_func_array([$stmt_duplicados, 'bind_param'], refValues(array_merge([$all_types_duplicados], $all_params_duplicados)));
            
            $stmt_duplicados->execute();
            $result_duplicados = $stmt_duplicados->get_result();

            while ($row = $result_duplicados->fetch_assoc()) {
                $report_data[] = $row;
            }
            $stmt_duplicados->close();
            break;
        // --- FIN NUEVO CASO ---

        default:
            $report_title = "Seleccione un Reporte";
            $error_message = "Seleccione un tipo de reporte para ver sus datos.";
            break;
    }

    // Consulta para otros módulos (sin devoluciones ni actividades ni duplicados), igual que antes
    if (!in_array($report_type, ['devoluciones', 'actividades', 'duplicados']) && !empty($sql_select_columns)) {
        $sql_count = "SELECT COUNT(*) AS total $common_joins $sql_join_detail WHERE $where_sql $busqueda_sql";
        $stmt = $conn->prepare($sql_count);
        if (!$stmt) throw new Exception("Error en conteo: " . $conn->error);
        if (!empty($params)) call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$param_types], $params)));
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $total_registros = $res['total'] ?? 0;
        $stmt->close();

        $sql = "SELECT $common_select_columns, $sql_select_columns $common_joins $sql_join_detail WHERE $where_sql $busqueda_sql ORDER BY r.fecha_reporte DESC LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error en consulta datos: " . $conn->error);

        $all_params = array_merge($params, [$inicio, $itemsPorPagina]);
        $all_types = $param_types . "ii";
        call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$all_types], $all_params)));

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        $stmt->close();
    }
    
    // Consulta específica para actividades
    if ($report_type === 'actividades' && !empty($sql_select_columns)) {
        $sql_count = "SELECT COUNT(*) AS total $common_joins WHERE $where_sql $busqueda_sql";
        $stmt = $conn->prepare($sql_count);
        if (!$stmt) throw new Exception("Error en conteo actividades: " . $conn->error);
        if (!empty($params)) call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$param_types], $params)));
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $total_registros = $res['total'] ?? 0;
        $stmt->close();

        $sql = "SELECT $common_select_columns, $sql_select_columns $common_joins WHERE $where_sql $busqueda_sql ORDER BY rfa.fecha_hora_captura DESC LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error en consulta datos actividades: " . $conn->error);

        $all_params = array_merge($params, [$inicio, $itemsPorPagina]);
        $all_types = $param_types . "ii";
        call_user_func_array([$stmt, 'bind_param'], refValues(array_merge([$all_types], $all_params)));

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        $stmt->close();
    }

} catch (Exception $e) {
    $error_message = "Error al cargar el reporte: " . $e->getMessage();
} finally {
    if ($conn) $conn->close();
}

// Filtros para la vista
$puntos_venta = [];
$ciudades = [];
// Re-incluir conexion.php para obtener filtros si la conexión se cerró en el bloque try/catch
// Esto es necesario porque el bloque try/catch cierra la conexión en 'finally'.
// Para evitar errores si la conexión ya está abierta, podemos añadir un chequeo.
$conn = null; // Reiniciar $conn para asegurar una nueva conexión
@include 'conexion.php'; // Usar @ para suprimir warnings si el archivo no existe o tiene problemas
if ($conn && !$conn->connect_error) { // Solo si la conexión es exitosa
    $rs = $conn->query("SELECT DISTINCT ciudad FROM puntos_venta WHERE ciudad IS NOT NULL AND ciudad != '' ORDER BY ciudad");
    if ($rs) { // Verificar que la consulta fue exitosa
        while ($rw = $rs->fetch_assoc()) $ciudades[] = $rw['ciudad'];
    }
    $rs = $conn->query("SELECT DISTINCT nombre FROM puntos_venta WHERE nombre IS NOT NULL AND nombre != '' ORDER BY nombre");
    if ($rs) { // Verificar que la consulta fue exitosa
        while ($rw = $rs->fetch_assoc()) $puntos_venta[] = $rw['nombre'];
    }
    $conn->close();
}
$total_paginas = ceil(max(1, $total_registros) / $itemsPorPagina);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?=htmlspecialchars($report_title)?> - IPV</title>
    <style>
        body {font-family: Arial, sans-serif; margin:0; padding:0;}
        .container {width: 98%; margin: 20px auto;}
        table {width: 100%; border-collapse: collapse;}
        th, td {border: 1px solid #ccc; padding: 8px; font-size:14px; vertical-align: top;}
        th {background: #e4e4e4;}
        .pagination {text-align: center; margin: 10px;}
        .pagination a, .pagination span {margin: 0 3px; text-decoration:none; border:1px solid #aaa; padding: 3px 8px;}
        .pagination .active {background: #007bff; color:#fff; border-color: #007bff;}
        .filter-box {padding: 10px; background: #fafafa; margin-bottom: 10px;}
        .filter-box select, .filter-box input {margin-right: 10px;}
        .devolucion-list {font-size: 13px;}
        .devolucion-list div {margin-bottom: 4px;}
        /* Estilos para las imágenes en el reporte de duplicados */
        .report-image-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            align-items: center;
        }
        .report-image {
            width: 100px; /* Tamaño fijo para la miniatura */
            height: 100px;
            object-fit: cover; /* Asegura que la imagen cubra el área sin distorsionarse */
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .image-label {
            font-size: 0.8em;
            color: #555;
            text-align: center;
        }
        .image-pair {
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            gap: 10px;
        }
        .image-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        /* Nuevos estilos para organizar el detalle de las fotos duplicadas */
        .photo-detail-cell {
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 5px;
            border: 1px solid #eee; /* Borde sutil para agrupar */
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .photo-detail-cell strong {
            font-size: 0.9em;
            color: #333;
        }
        .photo-detail-cell span {
            font-size: 0.85em;
            color: #666;
        }
    </style>
    <script>
    function filtrarTabla() {
        var url = new URL(window.location.href);
        var busqueda = document.getElementById("filtro-buscar").value;
        var ciudad = document.querySelector('select[name="filtro_ciudad"]').value;
        var puntoVenta = document.querySelector('select[name="filtro_punto_venta"]').value;

        url.searchParams.set('buscar', busqueda);
        url.searchParams.set('filtro_ciudad', ciudad);
        url.searchParams.set('filtro_punto_venta', puntoVenta);
        url.searchParams.set('pagina', 1); // Resetear a la primera página al aplicar filtros

        window.location.href = url.toString();
    }

    // Mantener los filtros al cambiar de página
    window.onload = function() {
        var url = new URL(window.location.href);
        var busqueda = url.searchParams.get('buscar');
        var ciudad = url.searchParams.get('filtro_ciudad');
        var puntoVenta = url.searchParams.get('filtro_punto_venta');

        if (busqueda !== null) document.getElementById("filtro-buscar").value = busqueda;
        if (ciudad !== null) document.querySelector('select[name="filtro_ciudad"]').value = ciudad;
        if (puntoVenta !== null) document.querySelector('select[name="filtro_punto_venta"]').value = puntoVenta;
    };
    </script>
</head>
<body>
<div class="container">
    <a href="dashboard.php">&larr; Volver al Panel</a>
    <h1><?=htmlspecialchars($report_title)?></h1>

    <div>
        <a href="?tipo=agotados">Agotados</a> |
        <a href="?tipo=precios">Precios</a> |
        <a href="?tipo=inventarios">Inventarios</a> |
        <a href="?tipo=devoluciones">Devoluciones</a> |
        <a href="?tipo=actividades">Actividades</a>
        <?php if ($rol === 'administrador' || $rol === 'supervisor'): // Mostrar si el rol es administrador o supervisor ?>
        | <a href="?tipo=duplicados">Fotos Duplicadas</a>
        <?php endif; ?>
        <?php if (in_array($rol, ['administrador', 'supervisor', 'cliente'])): // Mostrar botón para análisis detallado a estos roles ?>
        | <a href="analisis_reportes.php">Análisis Detallados</a>
        <?php endif; ?>
</div>

    <form method="GET" class="filter-box" style="margin-top:10px;" onsubmit="event.preventDefault(); filtrarTabla();">
        <input type="hidden" name="tipo" value="<?=htmlspecialchars($report_type)?>" />
        <input type="text" name="buscar" id="filtro-buscar" value="<?=htmlspecialchars($busqueda)?>" placeholder="Buscar en todo (inclusive código, ciudad, producto, punto de venta...)" />
        <select name="filtro_ciudad">
            <option value="">-- Filtrar Ciudad --</option>
            <?php foreach ($ciudades as $c): ?>
                <option value="<?=htmlspecialchars($c)?>" <?= $filtro_ciudad == $c ? "selected" : "" ?>><?=htmlspecialchars($c)?></option>
            <?php endforeach; ?>
        </select>
        <select name="filtro_punto_venta">
            <option value="">-- Filtrar Punto de Venta --</option>
            <?php foreach ($puntos_venta as $pv): ?>
                <option value="<?=htmlspecialchars($pv)?>" <?= $filtro_punto_venta == $pv ? "selected" : "" ?>><?=htmlspecialchars($pv)?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Aplicar Filtros</button>
    </form>

<?php if ($error_message): ?>
    <p style="color:red;"><?=htmlspecialchars($error_message)?></p>
<?php elseif (empty($report_data) && $report_type !== '' && $report_type !== 'duplicados'): // Si no hay datos y no es el reporte de duplicados (para evitar el mensaje si el admin no tiene duplicados) ?>
    <p>No hay datos registrados para este reporte actualmente.</p>
<?php elseif (empty($report_data) && $report_type === 'duplicados' && $rol === 'administrador'): ?>
    <p>No hay fotos duplicadas registradas actualmente.</p>
<?php elseif (empty($report_type)): ?>
    <p>Por favor, seleccione un tipo de reporte para visualizar sus datos.</p>
<?php else: ?>
    <div style="margin-bottom:6px; font-size:13px;">
        Mostrando <?=$inicio + 1?> - <?=min($inicio + $itemsPorPagina, $total_registros)?> de <?=$total_registros?> registros
    </div>
    <table id="tabla-resultados">
        <thead>
            <tr>
                <?php foreach ($table_headers as $th): ?>
                    <th><?=htmlspecialchars($th)?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($report_type == 'devoluciones'): ?>
                <?php foreach ($report_data as $item): ?>
                    <tr>
                        <td><?=formatDateTime12Hour($item['fecha_reporte'])?></td>
                        <?php if ($rol != 'cliente'): ?>
                            <td><?=htmlspecialchars($item['nombre_cliente'])?></td>
                        <?php endif; ?>
                        <td><?=htmlspecialchars($item['nombre_punto_venta'])?></td>
                        <td><?=htmlspecialchars($item['ciudad_punto_venta'])?></td>
                        <td><?=htmlspecialchars($item['nombre_usuario_movil'])?></td>
                        <td><?=htmlspecialchars($item['codigo_barras'])?></td>
                        <td><?=htmlspecialchars($item['descripcion'])?></td>
                        <td><?=htmlspecialchars($item['marca'])?></td>
                        <td class="devolucion-list">
                            <?php if (!empty($item['devoluciones'])): ?>
                                <?php foreach ($item['devoluciones'] as $dev): ?>
                                    <div><?=htmlspecialchars($dev['cantidad'])?> unidades - <?=formatDateTime12Hour($dev['fecha'])?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <em>No hay devoluciones</em>
                            <?php endif; ?>
                        </td>
                        <td><?=htmlspecialchars($item['causal_devolucion'] ?? '')?></td>
                    </tr>
                <?php endforeach; ?>
            <?php elseif ($report_type == 'actividades'): ?>
                <?php foreach ($report_data as $row): ?>
                    <tr>
                        <td><?=formatDateTime12Hour($row['fecha_reporte'])?></td>
                        <?php if ($rol != 'cliente'): ?>
                            <td><?=htmlspecialchars($row['nombre_cliente'] ?? '')?></td>
                        <?php endif; ?>
                        <td><?=htmlspecialchars($row['nombre_punto_venta'])?></td>
                        <td><?=htmlspecialchars($row['ciudad_punto_venta'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['nombre_usuario_movil'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['tipo_actividad'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['descripcion_actividad'] ?? '')?></td>
                        <td>
                            <?php if (!empty($row['ruta_servidor_foto'])): ?>
                                <a href="<?=htmlspecialchars($row['ruta_servidor_foto'])?>" target="_blank">
                                    <img src="<?=htmlspecialchars($row['ruta_servidor_foto'])?>" alt="Foto de Actividad" style="width: 100px; height: auto; border-radius: 8px; object-fit: cover;">
                                </a>
                            <?php else: ?>
                                No disponible
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php elseif ($report_type == 'duplicados' && $rol === 'administrador'): // Solo renderizar si es el reporte de duplicados Y el rol es administrador ?>
                <?php foreach ($report_data as $row): ?>
                    <tr>
                        <?php if ($rol != 'cliente'): ?>
                            <td><?=htmlspecialchars($row['nombre_cliente'] ?? 'N/A')?></td>
                        <?php endif; ?>
                        <td><?=htmlspecialchars($row['nombre_usuario_movil'] ?? 'N/A')?></td>
                        <td><?=htmlspecialchars($row['nombre_punto_venta'] ?? 'N/A')?> (<?=htmlspecialchars($row['ciudad_punto_venta'] ?? 'N/A')?>)</td>
                        
                        <!-- Columna de Detalle Foto Re-subida -->
                        <td>
                            <div class="photo-detail-cell">
                                <strong>ID de Registro:</strong> <span><?=htmlspecialchars($row['reusada_id'])?></span>
                                <strong>Fecha de Registro:</strong> <span><?=formatDateTime12Hour($row['reusada_fecha'])?></span>
                                <strong>Tipo de Actividad:</strong> <span><?=htmlspecialchars($row['reusada_tipo'] ?? '')?></span>
                                <strong>Descripción:</strong> <span><?=htmlspecialchars($row['reusada_descripcion'] ?? '')?></span>
                                <strong>Hash Perceptual:</strong> <span><?=htmlspecialchars($row['reusada_hash'] ?? 'N/A')?></span>
                                <?php if (!empty($row['reusada_ruta'])): ?>
                                    <div class="report-image-container">
                                        <a href="<?=htmlspecialchars($row['reusada_ruta'])?>" target="_blank">
                                            <img src="<?=htmlspecialchars($row['reusada_ruta'])?>" alt="Foto Re-subida" class="report-image">
                                        </a>
                                        <span class="image-label">Foto Re-subida</span>
                                    </div>
                                <?php else: ?>
                                    <span>Imagen no disponible</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Columna de Detalle Foto Original -->
                        <td>
                            <div class="photo-detail-cell">
                                <strong>ID de Registro:</strong> <span><?=htmlspecialchars($row['original_id'] ?? 'N/A')?></span>
                                <strong>Fecha de Registro:</strong> <span><?=formatDateTime12Hour($row['original_fecha'] ?? 'N/A')?></span>
                                <strong>Tipo de Actividad:</strong> <span><?=htmlspecialchars($row['original_tipo'] ?? '')?></span>
                                <strong>Descripción:</strong> <span><?=htmlspecialchars($row['original_descripcion'] ?? '')?></span>
                                <strong>Hash Perceptual:</strong> <span><?=htmlspecialchars($row['original_hash'] ?? 'N/A')?></span>
                                <?php if (!empty($row['original_ruta'])): ?>
                                    <div class="report-image-container">
                                        <a href="<?=htmlspecialchars($row['original_ruta'])?>" target="_blank">
                                            <img src="<?=htmlspecialchars($row['original_ruta'])?>" alt="Foto Original" class="report-image">
                                        </a>
                                        <span class="image-label">Foto Original</span>
                                    </div>
                                <?php else: ?>
                                    <span>Imagen no disponible</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: // Otros reportes (agotados, precios, inventarios) ?>
                <?php foreach ($report_data as $row): ?>
                    <tr>
                        <td><?=formatDateTime12Hour($row['fecha_reporte'])?></td>
                        <?php if ($rol != 'cliente'): ?>
                            <td><?=htmlspecialchars($row['nombre_cliente'] ?? '')?></td>
                        <?php endif; ?>
                        <td><?=htmlspecialchars($row['nombre_punto_venta'])?></td>
                        <td><?=htmlspecialchars($row['ciudad_punto_venta'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['nombre_usuario_movil'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['codigo_barras'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['descripcion'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['marca'] ?? '')?></td>
                        <!-- campos específicos por módulo -->
                        <?php if ($report_type == 'agotados'): ?>
                            <td><?=htmlspecialchars($row['presentacion'] ?? '')?></td>
                            <td><?=htmlspecialchars($row['agotados'] ?? '')?></td>
                            <td><?=htmlspecialchars($row['causal_agotado'] ?? '')?></td>
                        <?php elseif ($report_type == 'precios'): ?>
                            <td>$ <?=number_format((float)$row['precio_nuevo'], 0, ',', '.')?></td>
                            <td><?=htmlspecialchars($row['justificacion_cambio'] ?? '')?></td>
                        <?php elseif ($report_type == 'inventarios'): ?>
                            <td><?=htmlspecialchars($row['inventarios'])?></td>
                            <td><?=htmlspecialchars($row['sugeridos'])?></td>
                            <td><?=htmlspecialchars($row['unidades_surtidas'])?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php for ($i=1; $i<=$total_paginas; $i++): ?>
            <?php if ($i == $pagina): ?>
                <span class="active"><?=$i?></span>
            <?php else: ?>
                <a href="?<?=http_build_query(array_merge($_GET, ['pagina' => $i]))?>"><?=$i?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>

</div>
</body>
</html>



