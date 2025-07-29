<?php
session_start();
include 'conexion.php';

// Solo admins acceden
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

$sql = "SELECT * FROM rutas ORDER BY codigo_carga DESC, id_promotor ASC, fecha_inicio ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📍 Rutas cargadas</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }
        th {
            background: #eee;
        }
        .export-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h2>📍 Rutas registradas</h2>

    <a href="exportar_rutas_excel.php" class="export-btn">📥 Descargar Excel de rutas</a>

    <table>
        <thead>
            <tr>
                <th>ID PROMOTOR</th>
                <th>ID PV</th>
                <th>ID EMPRESA</th>
                <th>NDIA</th>
                <th>FECHA INICIO DE CICLO</th>
                <th>HORAS</th>
                <th>BOLSA</th>
                <th>NOMBRE PROMOTOR</th>
                <th>NOMBRE PUNTO DE VENTA</th>
                <th>NOMBRE EMPRESA</th>
                <th>CIUDAD PV</th>
                <th>DEPARTAMENTO PV</th>
                <th>ESTADO</th>
                <th>CODIGO DE CARGA</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id_promotor'] ?></td>
                    <td><?= $row['id_pv'] ?></td>
                    <td><?= $row['id_empresa'] ?></td>
                    <td><?= $row['ndia'] ?></td>
                    <td><?= date('d/m/Y', strtotime($row['fecha_inicio'])) ?></td>
                    <td><?= $row['horas'] ?></td>
                    <td><?= $row['bolsa'] ?></td>
                    <td><?= htmlspecialchars($row['nombre_promotor']) ?></td>
                    <td><?= htmlspecialchars($row['nombre_punto_venta']) ?></td>
                    <td><?= htmlspecialchars($row['nombre_empresa']) ?></td>
                    <td><?= $row['ciudad_pv'] ?></td>
                    <td><?= $row['departamento_pv'] ?></td>
                    <td><?= $row['estado'] ?></td>
                    <td><?= $row['codigo_carga'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <br><a href="dashboard.php">🔙 Volver</a>
</body>
</html>

