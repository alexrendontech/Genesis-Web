<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

include 'conexion.php';

$cliente_id = $_GET['id'] ?? null;

if (!$cliente_id) {
    echo "ID de cliente no válido.";
    exit;
}

// Obtener nombre del cliente
$cliente = mysqli_fetch_assoc(mysqli_query($conn, "SELECT razon_social FROM clientes WHERE id = $cliente_id"));
$nombre_cliente = $cliente ? $cliente['razon_social'] : 'Desconocido';

// Obtener productos de ese cliente
$sql = "SELECT * FROM productos_cliente WHERE cliente_id = $cliente_id ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos de <?= htmlspecialchars($nombre_cliente) ?></title>
    <style>
        table { border-collapse: collapse; width: 100%; font-size: 13px; }
        th, td { padding: 6px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>📦 Productos de <?= htmlspecialchars($nombre_cliente) ?> (ID <?= $cliente_id ?>)</h2>

    <table>
        <thead>
            <tr>
                <th>Código de Barras</th>
                <th>Marca</th>
                <th>Categoría</th>
                <th>Segmento</th>
                <th>Descripción</th>
                <th>Presentación</th>
                <th>Unidad</th>
                <th>Agotados</th>
                <th>Inventarios</th>
                <th>Sugeridos</th>
                <th>Unidades Surtidas</th>
                <th>Devoluciones</th>
                <th>Averías</th>
                <th>Transferencias</th>
                <th>Precios</th>
                <th>Ventas</th>
                <th>Precio Producto</th>
                <th>Vigencia</th>
                <th>Competencia</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['codigo_barras']) ?></td>
                    <td><?= htmlspecialchars($row['marca']) ?></td>
                    <td><?= htmlspecialchars($row['categoria']) ?></td>
                    <td><?= htmlspecialchars($row['segmento']) ?></td>
                    <td><?= htmlspecialchars($row['descripcion']) ?></td>
                    <td><?= htmlspecialchars($row['presentacion']) ?></td>
                    <td><?= htmlspecialchars($row['unidad_presentacion']) ?></td>
                    <td><?= $row['agotados'] ? '✅' : '❌' ?></td>
                    <td><?= $row['inventarios'] ? '✅' : '❌' ?></td>
                    <td><?= $row['sugeridos'] ? '✅' : '❌' ?></td>
                    <td><?= $row['unidades_surtidas'] ? '✅' : '❌' ?></td>
                    <td><?= $row['devoluciones'] ? '✅' : '❌' ?></td>
                    <td><?= $row['averias'] ? '✅' : '❌' ?></td>
                    <td><?= $row['transferencias'] ? '✅' : '❌' ?></td>
                    <td><?= $row['precios'] ? '✅' : '❌' ?></td>
                    <td><?= $row['ventas'] ? '✅' : '❌' ?></td>
                    <td><?= $row['precio_producto'] ? '✅' : '❌' ?></td>
                    <td><?= $row['vigencia'] ? '✅' : '❌' ?></td>
                    <td><?= $row['competencia'] ? '✅' : '❌' ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <br><a href="listar_clientes.php">🔙 Volver a clientes</a>
</body>
</html>
