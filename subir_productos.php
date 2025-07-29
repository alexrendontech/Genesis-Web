<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($cliente_id === 0) {
    echo "ID de cliente no válido.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Productos</title>
</head>
<body>
    <h2>📦 Subir productos para cliente ID: <?= htmlspecialchars($cliente_id) ?></h2>

    <form action="procesar_productos.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
        <label>Selecciona el archivo Excel (.xlsx):</label><br>
        <input type="file" name="archivo" accept=".xlsx" required><br><br>

        <button type="submit">Subir productos</button>
    </form>

    <br><a href="listar_clientes.php">🔙 Volver a clientes</a>
</body>
</html>

