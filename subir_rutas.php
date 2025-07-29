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
    <title>Subir Rutas</title>
</head>
<body>
    <h2>📤 Subir archivo de rutas (.xlsx)</h2>

    <form action="procesar_rutas.php" method="POST" enctype="multipart/form-data">
        <label>Selecciona archivo Excel:</label><br>
        <input type="file" name="archivo" accept=".xlsx" required><br><br>

        <button type="submit">Cargar Rutas</button>
    </form>

    <br><a href="dashboard.php">🔙 Volver al inicio</a>
</body>
</html>
