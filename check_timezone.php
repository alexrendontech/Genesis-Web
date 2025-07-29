<?php
// check_timezone.php
// Este script muestra la zona horaria actual de PHP y la fecha/hora del servidor.

echo "Zona horaria configurada en PHP: " . date_default_timezone_get() . "<br>";
echo "Fecha y hora actual del servidor: " . date('Y-m-d H:i:s A') . "<br>";
?>
