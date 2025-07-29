<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];

// Asumiendo que 'conexion.php' está en el mismo directorio o accesible.
// include 'conexion.php'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes IPV - <?php echo ucfirst($rol); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .report-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .report-link {
            background-color: #007bff;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .report-link:hover {
            background-color: #0056b3;
        }
        .back-to-dashboard {
            display: block;
            text-align: left;
            margin-bottom: 20px;
            color: #555;
            text-decoration: none;
        }
        .back-to-dashboard:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-to-dashboard">&larr; Volver al Panel Principal</a>
        <h1>Seleccionar Reporte - <?php echo ucfirst($rol); ?></h1>

        <div class="report-links">
            <a href="ver_reporte.php?tipo=agotados" class="report-link">Reporte de Agotados</a>
            <a href="ver_reporte.php?tipo=precios" class="report-link">Reporte de Precios</a>
            <a href="ver_reporte.php?tipo=inventarios" class="report-link">Reporte de Inventarios</a>
            <a href="ver_reporte.php?tipo=devoluciones" class="report-link">Reporte de Devoluciones</a>
        </div>
    </div>
</body>
</html>