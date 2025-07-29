<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Puntos de Venta</title>
</head>
<body>
    <h2>🏪 Subir puntos de venta desde Excel</h2>

    <form action="procesar_puntos_venta.php" method="POST" enctype="multipart/form-data">
        <label>Selecciona el archivo Excel (.xlsx):</label><br>
        <input type="file" name="archivo" accept=".xlsx" required><br><br>
        <button type="submit">Subir puntos de venta</button>
    </form>

    <br><a href="dashboard.php">🔙 Volver al inicio</a>
</body>
</html>
