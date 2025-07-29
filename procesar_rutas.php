<?php
require 'vendor/autoload.php';
include 'conexion.php'; // Asegúrate de que conexion.php configure autocommit(FALSE)

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Para la conversión de fechas de Excel

if (isset($_FILES['archivo']['tmp_name']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $archivoExcel = $_FILES['archivo']['tmp_name'];
    $spreadsheet = IOFactory::load($archivoExcel);
    $hoja = $spreadsheet->getActiveSheet();
    $filas = $hoja->toArray();

    $codigoCarga = uniqid(); // Código único de carga para esta operación

    $conn->begin_transaction(); // Iniciar transacción para asegurar atomicidad y rollback en caso de fallo

    try {
        // Saltar el encabezado de las filas del Excel
        $firstRowSkipped = false;

        foreach ($filas as $index => $fila) {
            if (!$firstRowSkipped) {
                $firstRowSkipped = true;
                continue; // Saltar la primera fila (encabezado)
            }

            // Validar que la fila no esté vacía o tenga menos columnas de las esperadas
            if (count($fila) < 10) { // Hay 10 columnas en tu archivo de rutas
                throw new Exception("Fila incompleta en el Excel en la fila " . ($index + 1) . ". Se esperaban al menos 10 columnas.");
            }

            // Correcta asignación de variables de las columnas del Excel (índices 0-9)
            // Columnas del rutero.xlsx: id_promotor, id_pv, id_empresa, ndia, fecha_inicio, horas, bolsa, nombre_promotor, nombre_empresa, estado
            list(
                $id_promotor,
                $id_pv,
                $id_empresa,
                $ndia,
                $fecha_inicio_excel_raw, // Valor de fecha sin procesar del Excel
                $horas,
                $bolsa,
                $nombre_promotor,
                $nombre_empresa_excel, // Nombre de la empresa del Excel
                $estado_excel // Estado del Excel
            ) = $fila;

            // --- Lógica de Conversión de Fechas ---
            $fecha_inicio_db_format = null; // Variable para almacenar la fecha en formato YYYY-MM-DD para la DB

            // 1. Intentar convertir como fecha numérica de Excel
            if (is_numeric($fecha_inicio_excel_raw) && Date::isExcelDate($fecha_inicio_excel_raw)) {
                try {
                    $fecha_obj = Date::excelToDateTimeObject($fecha_inicio_excel_raw);
                    $fecha_inicio_db_format = $fecha_obj->format('Y-m-d');
                } catch (Exception $e) {
                    error_log("Error al convertir fecha numérica de Excel '{$fecha_inicio_excel_raw}': " . $e->getMessage());
                }
            }

            // 2. Si no se convirtió como numérica, intentar parsear como cadena de texto
            if ($fecha_inicio_db_format === null && is_string($fecha_inicio_excel_raw)) {
                // Intentar parsear primero el formato YYYY-MM-DD (común en CSVs exportados de Excel)
                $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_inicio_excel_raw);

                if ($fecha_obj === false) {
                    // Si YYYY-MM-DD falla, intentar formatos comunes M/D/YYYY y MM/DD/YYYY
                    $fecha_obj = DateTime::createFromFormat('n/j/Y', $fecha_inicio_excel_raw); // Ej: 7/23/2025
                }
                if ($fecha_obj === false) {
                     $fecha_obj = DateTime::createFromFormat('m/d/Y', $fecha_inicio_excel_raw); // Ej: 07/23/2025
                }

                if ($fecha_obj !== false) {
                    $fecha_inicio_db_format = $fecha_obj->format('Y-m-d');
                } else {
                    // 3. Recurrir a strtotime() para otros formatos ambiguos (menos fiable, pero puede funcionar)
                    $timestamp = strtotime($fecha_inicio_excel_raw);
                    if ($timestamp !== false) {
                        $fecha_inicio_db_format = date('Y-m-d', $timestamp);
                    }
                }

                if ($fecha_inicio_db_format === null) {
                    // Si todos los intentos de parseo fallaron para una fecha de cadena
                    throw new Exception("No se pudo procesar la fecha de inicio en la fila " . ($index + 1) . ". Valor: '" . $fecha_inicio_excel_raw . "'");
                }
            } else if ($fecha_inicio_db_format === null) {
                // Esto maneja casos donde el valor no es ni numérico ni una cadena de texto reconocida
                throw new Exception("Tipo de dato de fecha no reconocido en la fila " . ($index + 1) . ". Valor: '" . var_export($fecha_inicio_excel_raw, true) . "'");
            }
            // --- Fin de Lógica de Conversión de Fechas ---


            // Obtener nombre, ciudad y región desde la tabla puntos_venta usando id_pv
            $nombre_pv = null;
            $ciudad_pv = null;
            $departamento_pv = null;
            $stmt_pv = $conn->prepare("SELECT nombre, ciudad, region FROM puntos_venta WHERE id = ? LIMIT 1");
            if ($stmt_pv === false) {
                throw new Exception("Error al preparar la consulta de puntos_venta: " . $conn->error);
            }
            $stmt_pv->bind_param("i", $id_pv);
            $stmt_pv->execute();
            $stmt_pv->bind_result($nombre_pv, $ciudad_pv, $departamento_pv);
            $stmt_pv->fetch();
            $stmt_pv->close();

            if (!$nombre_pv) {
                throw new Exception("❌ Punto de venta con ID **$id_pv** no encontrado en la fila " . ($index + 1) . ". Asegúrate de que los puntos de venta se cargaron correctamente primero.");
            }

            // Insertar la ruta con la información completa
            $stmt_insert = $conn->prepare("INSERT INTO rutas (
                id_promotor, id_pv, id_empresa, ndia, fecha_inicio, horas, bolsa,
                nombre_promotor, nombre_punto_venta, nombre_empresa, ciudad_pv, departamento_pv, estado, codigo_carga
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt_insert === false) {
                throw new Exception("Error al preparar la inserción de rutas: " . $conn->error);
            }

            $stmt_insert->bind_param("iiiisdisssssss",
                $id_promotor,
                $id_pv,
                $id_empresa,
                $ndia,
                $fecha_inicio_db_format, // Usar la fecha convertida
                $horas,
                $bolsa,
                $nombre_promotor,
                $nombre_pv, // Obtenido de la consulta a puntos_venta
                $nombre_empresa_excel, // Obtenido directamente del Excel
                $ciudad_pv, // Obtenido de la consulta a puntos_venta
                $departamento_pv, // Obtenido de la consulta a puntos_venta
                $estado_excel, // Obtenido directamente del Excel
                $codigoCarga
            );

            $stmt_insert->execute();

            // Verificar si hubo un error en esta inserción particular
            if ($stmt_insert->error) {
                throw new Exception("Error al insertar la ruta para ID PV {$id_pv} en la fila " . ($index + 1) . ": " . $stmt_insert->error);
            }
            $stmt_insert->close(); // Cerrar el statement después de cada ejecución
        }

        $conn->commit(); // Confirmar la transacción si todas las inserciones fueron exitosas
        echo "<h3>✅ Rutas cargadas exitosamente. Código de carga: <strong>$codigoCarga</strong></h3>";
        echo "<a href='ver_rutas.php'>👀 Ver rutas</a>";
    } catch (Exception $e) {
        $conn->rollback(); // Revertir la transacción si algo falla
        echo "<h3>❌ Error al cargar rutas: </h3><p>" . $e->getMessage() . "</p>";
    }
} else {
    echo "<h3>❌ Error al subir el archivo.</h3>";
}
?>