<?php
require 'vendor/autoload.php';
include 'conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $archivoExcel = $_FILES['archivo']['tmp_name'];
    $spreadsheet = IOFactory::load($archivoExcel);
    $hoja = $spreadsheet->getActiveSheet();
    $filas = $hoja->toArray();

    $conn->begin_transaction();

    try {
        $codigoCarga = null;

        foreach ($filas as $index => $fila) {
            if ($index === 0) continue; // Saltar encabezado

            list(
                $id_promotor, $id_pv, $id_empresa, $ndia, $fecha_inicio,
                $horas, $bolsa, $nombre_promotor, $nombre_pv, $nombre_empresa,
                $ciudad, $departamento, $estado, $codigo
            ) = $fila;

            if ($index === 1) {
                // Guardamos el código de carga para verificarlo
                $codigoCarga = $codigo;

                // Validamos que exista
                $check = $conn->prepare("SELECT COUNT(*) FROM rutas WHERE codigo_carga = ?");
                $check->bind_param("s", $codigoCarga);
                $check->execute();
                $check->bind_result($existe);
                $check->fetch();
                $check->close();

                if ($existe === 0) {
                    throw new Exception("❌ El código de carga '$codigoCarga' no existe.");
                }

                // Eliminamos rutas anteriores con ese código
                $del = $conn->prepare("DELETE FROM rutas WHERE codigo_carga = ?");
                $del->bind_param("s", $codigoCarga);
                $del->execute();
                $del->close();
            }

            $fechaInicio = date('Y-m-d', strtotime($fecha_inicio));

            $stmt = $conn->prepare("INSERT INTO rutas 
                (id_promotor, id_pv, id_empresa, ndia, fecha_inicio, horas, bolsa,
                nombre_promotor, nombre_punto_venta, nombre_empresa, ciudad_pv, departamento_pv, estado, codigo_carga)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("iiiisdisssssss",
                $id_promotor, $id_pv, $id_empresa, $ndia, $fechaInicio, $horas, $bolsa,
                $nombre_promotor, $nombre_pv, $nombre_empresa, $ciudad, $departamento, $estado, $codigoCarga
            );
            $stmt->execute();
        }

        $conn->commit();
        echo "✅ Rutas actualizadas correctamente para el código de carga: <strong>$codigoCarga</strong><br>";
        echo "<a href='ver_rutas.php'>👀 Ver rutas</a>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "❌ Error: " . $e->getMessage();
    }
} else {
    echo "❌ Error al subir el archivo.";
}
