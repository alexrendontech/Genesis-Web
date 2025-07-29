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
    <title>Actualizar Rutas por Código de Carga</title>
</head>
<body>
    <h2>🛠️ Actualizar rutas existentes</h2>
    <p>Este formulario reemplazará todas las rutas asociadas al mismo <strong>código de carga</strong> que esté en el archivo Excel.</p>

    <form action="procesar_edicion_rutas.php" method="POST" enctype="multipart/form-data">
        <label>Selecciona el archivo Excel (.xlsx):</label><br>
        <input type="file" name="archivo" accept=".xlsx" required><br><br>

        <button type="submit">Actualizar rutas</button>
    </form>

    <br><a href="dashboard.php">🔙 Volver al inicio</a>
</body>
</html>
