<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

include 'conexion.php';

$sql = "SELECT * FROM puntos_venta ORDER BY id ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Puntos de Venta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 15px;
        }

        h2 {
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 14px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        a {
            display: inline-block;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <h2>🏪 Puntos de Venta Registrados</h2>

    <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>País</th>
            <th>Región</th>
            <th>Ciudad</th>
            <th>Nombre</th>
            <th>Canal</th>
            <th>Sub Canal</th>
            <th>Cadena</th>
            <th>Formato</th>
            <th>SAP</th>
            <th>Barrio</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Administrador</th>
            <th>Contacto Bodega</th>
            <th>M²</th>
            <th>Circuito Nielsen</th>
            <th>Tipología</th>
            <th>Cajas</th>
            <th>Dependientes</th>
            <th>Latitud</th>
            <th>Longitud</th>
            <th>GeoRef</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['id'] ?></td> <!-- Mostrando ID real -->
                <td><?= htmlspecialchars($row['pais']) ?></td>
                <td><?= htmlspecialchars($row['region']) ?></td>
                <td><?= htmlspecialchars($row['ciudad']) ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['canal']) ?></td>
                <td><?= htmlspecialchars($row['sub_canal']) ?></td>
                <td><?= htmlspecialchars($row['nombre_cadena']) ?></td>
                <td><?= htmlspecialchars($row['nombre_formato']) ?></td>
                <td><?= htmlspecialchars($row['cod_sap']) ?></td>
                <td><?= htmlspecialchars($row['barrio']) ?></td>
                <td><?= htmlspecialchars($row['direccion']) ?></td>
                <td><?= htmlspecialchars($row['telefono']) ?></td>
                <td><?= htmlspecialchars($row['nombre_administrador']) ?></td>
                <td><?= htmlspecialchars($row['contacto_bodega']) ?></td>
                <td><?= htmlspecialchars($row['metros_cuadrados']) ?></td>
                <td><?= htmlspecialchars($row['circuito_nielsen']) ?></td>
                <td><?= htmlspecialchars($row['tipologia_punto_venta']) ?></td>
                <td><?= htmlspecialchars($row['num_cajas_registradoras']) ?></td>
                <td><?= htmlspecialchars($row['num_dependientes']) ?></td>
                <td><?= htmlspecialchars($row['latitud']) ?></td>
                <td><?= htmlspecialchars($row['longitud']) ?></td>
                <td><?= htmlspecialchars($row['validar_georreferencia']) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>


    <a href="dashboard.php">🔙 Volver al inicio</a>
</body>
</html>
