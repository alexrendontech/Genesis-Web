<?php
// conexion.php - Versión corregida con autocommit habilitado

$host = "localhost";
$user = "root";
$pass = "";
$db   = "virtual_ipv";

$conn = new mysqli($host, $user, $pass, $db);

// Validar conexión
if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "mensaje" => "❌ Error de conexión a la base de datos: " . $conn->connect_error
    ]));
}

// IMPORTANTE: Mantener autocommit habilitado para operaciones simples
// Solo deshabilitar cuando necesites transacciones complejas
$conn->autocommit(TRUE); // <--- CAMBIADO A TRUE

// Establecer codificación UTF-8
$conn->set_charset("utf8");

// Log de conexión exitosa
error_log(date('Y-m-d H:i:s') . " - CONEXION: Base de datos conectada exitosamente");
?>

