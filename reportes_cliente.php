<?php
session_start();

// Redirigir si el usuario no está logueado o no es un cliente
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];
$cliente_id_sesion = $_SESSION['cliente_id']; // ID del cliente logueado

// Verificar si el tipo de reporte está definido en la URL
$report_type = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Incluir el archivo de conexión a la base de datos
include 'conexion.php'; 

$report_data = [];
$report_title = "Mis Reportes"; // Título más personalizado para el cliente
$table_headers = [];
$sql = "";
$error_message = "";

try {
    // Es crucial usar sentencias preparadas para la seguridad (SQL Injection)
    // Especialmente al usar datos de la sesión como $cliente_id_sesion
    $stmt = null; // Inicializar $stmt a null

    switch ($report_type) {
        case 'agotados':
            $report_title = "Mis Productos Agotados";
            $table_headers = ['ID Producto', 'Descripción', 'Marca', 'Presentación', 'Cantidad Agotados', 'Causal Agotado'];
            // FILTRAR POR EL ID DEL CLIENTE EN LA SESIÓN
            $sql = "SELECT pc.id, pc.descripcion, pc.marca, pc.presentacion, pc.agotados, pc.causal_agotado
                    FROM productos_cliente pc
                    WHERE pc.cliente_id = ? AND pc.agotados > 0 AND pc.agotados IS NOT NULL
                    ORDER BY pc.descripcion";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta de agotados: " . $conn->error);
            }
            $stmt->bind_param("i", $cliente_id_sesion); // "i" para integer
            break;

        case 'precios':
            $report_title = "Mis Precios de Productos";
            $table_headers = ['ID Producto', 'Descripción', 'Marca', 'Presentación', 'Precio (COP)'];
            // FILTRAR POR EL ID DEL CLIENTE EN LA SESIÓN
            $sql = "SELECT pc.id, pc.descripcion, pc.marca, pc.presentacion, pc.precio_producto
                    FROM productos_cliente pc
                    WHERE pc.cliente_id = ? AND pc.precio_producto IS NOT NULL AND pc.precio_producto != '' AND pc.precio_producto != 0
                    ORDER BY pc.descripcion";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta de precios: " . $conn->error);
            }
            $stmt->bind_param("i", $cliente_id_sesion);
            break;

        case 'inventarios':
            $report_title = "Mis Inventarios de Productos";
            $table_headers = ['ID Producto', 'Descripción', 'Marca', 'Presentación', 'Cantidad en Inventario'];
            // FILTRAR POR EL ID DEL CLIENTE EN LA SESIÓN
            $sql = "SELECT pc.id, pc.descripcion, pc.marca, pc.presentacion, pc.inventarios
                    FROM productos_cliente pc
                    WHERE pc.cliente_id = ? AND pc.inventarios IS NOT NULL AND pc.inventarios > 0
                    ORDER BY pc.descripcion";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta de inventarios: " . $conn->error);
            }
            $stmt->bind_param("i", $cliente_id_sesion);
            break;

        case 'devoluciones':
            $report_title = "Mis Productos en Devolución";
            $table_headers = ['ID Producto', 'Descripción', 'Marca', 'Presentación', 'Cantidad Devoluciones', 'Fecha Devolución'];
            // FILTRAR POR EL ID DEL CLIENTE EN LA SESIÓN
            $sql = "SELECT pc.id, pc.descripcion, pc.marca, pc.presentacion, pc.devoluciones, pc.fecha_devolucion
                    FROM productos_cliente pc
                    WHERE pc.cliente_id = ? AND pc.devoluciones IS NOT NULL AND pc.devoluciones > 0
                    ORDER BY pc.descripcion";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta de devoluciones: " . $conn->error);
            }
            $stmt->bind_param("i", $cliente_id_sesion);
            break;

        default:
            $report_title = "Seleccione un Reporte";
            $error_message = "Seleccione un tipo de reporte para ver sus datos.";
            break;
    }

    if ($stmt) { // Solo ejecutar si se preparó una sentencia
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
    if ($conn) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $report_title; ?> - IPV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .report-links {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 15px;
            background-color: #28a745; /* Color verde para los botones de cliente */
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .report-links a:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .no-data {
            text-align: center;
            color: #777;
            padding: 20px;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">&larr; Volver al Panel</a>
        <h1><?php echo $report_title; ?></h1>

        <div class="report-links">
            <a href="?tipo=agotados">Agotados</a>
            <a href="?tipo=precios">Precios</a>
            <a href="?tipo=inventarios">Inventarios</a>
            <a href="?tipo=devoluciones">Devoluciones</a>
        </div>

        <?php if (isset($error_message) && !empty($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php elseif (empty($report_data) && $report_type !== ''): // Mostrar "No data" solo si se seleccionó un tipo de reporte ?>
            <p class="no-data">No hay datos registrados para este reporte actualmente para su punto de venta.</p>
        <?php elseif (empty($report_type)): ?>
            <p class="no-data">Por favor, seleccione un tipo de reporte para visualizar sus datos.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($table_headers as $header): ?>
                            <th><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $row): ?>
                        <tr>
                            <?php 
                            // Renderizar las celdas dinámicamente según el tipo de reporte
                            switch ($report_type) {
                                case 'agotados':
                                    echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['descripcion']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['marca']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['presentacion']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['agotados']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['causal_agotado'] ?: 'N/A') . '</td>';
                                    break;
                                case 'precios':
                                    echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['descripcion']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['marca']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['presentacion']) . '</td>';
                                    echo '<td>$ ' . number_format(htmlspecialchars($row['precio_producto']), 0, ',', '.') . '</td>';
                                    break;
                                case 'inventarios':
                                    echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['descripcion']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['marca']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['presentacion']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['inventarios']) . '</td>';
                                    break;
                                case 'devoluciones':
                                    echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['descripcion']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['marca']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['presentacion']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['devoluciones']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['fecha_devolucion'] ?: 'N/A') . '</td>';
                                    break;
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>