<?php
require 'vendor/autoload.php';
include 'conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['archivo']['tmp_name'])) {
    $archivoExcel = $_FILES['archivo']['tmp_name'];
    $documento = IOFactory::load($archivoExcel);
    $hoja = $documento->getActiveSheet();
    $filas = $hoja->toArray();

    array_shift($filas); // Saltar encabezado

    foreach ($filas as $fila) {
        list(
            $pais, $region, $ciudad, $nombre, $canal, $sub_canal, $nombre_cadena, $nombre_formato,
            $cod_sap, $barrio, $direccion, $telefono, $nombre_administrador, $contacto_bodega,
            $metros_cuadrados, $circuito_nielsen, $tipologia, $num_cajas, $num_dependientes,
            $latitud, $longitud, $validar_geo
        ) = $fila;

        // Validaciones de tipo
        $num_cajas = is_numeric($num_cajas) ? (int)$num_cajas : 0;
        $num_dependientes = is_numeric($num_dependientes) ? (int)$num_dependientes : 0;
        $latitud = $latitud ?: '';
        $longitud = $longitud ?: '';
        $validar_geo = $validar_geo ?: '';

        $stmt = $conn->prepare("INSERT INTO puntos_venta (
            pais, region, ciudad, nombre, canal, sub_canal, nombre_cadena, nombre_formato,
            cod_sap, barrio, direccion, telefono, nombre_administrador, contacto_bodega,
            metros_cuadrados, circuito_nielsen, tipologia_punto_venta,
            num_cajas_registradoras, num_dependientes, latitud, longitud, validar_georreferencia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssssssssssssissssss",
            $pais, $region, $ciudad, $nombre, $canal, $sub_canal, $nombre_cadena, $nombre_formato,
            $cod_sap, $barrio, $direccion, $telefono, $nombre_administrador, $contacto_bodega,
            $metros_cuadrados, $circuito_nielsen, $tipologia, $num_cajas, $num_dependientes,
            $latitud, $longitud, $validar_geo
        );

        $stmt->execute();

        // Verifica si hubo error
        if ($stmt->error) {
            echo "❌ Error al insertar fila: " . $stmt->error . "<br>";
        }
    }

    echo "<h3>✅ Puntos de venta cargados correctamente.</h3>";
    echo "<a href='dashboard.php'>🔙 Volver al inicio</a>";
} else {
    echo "⚠️ Error al subir el archivo.";
}
?>



