<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

// Configuración de errores y logs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require 'conexion.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Archivo de log personalizado
$log_file = __DIR__ . '/productos_import.log';
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Inicio del proceso\n", FILE_APPEND);

function log_message($message) {
    global $log_file;
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo']) && isset($_POST['cliente_id'])) {
    try {
        // Configuración de la conexión
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn->autocommit(FALSE);
        
        $cliente_id = (int)$_POST['cliente_id'];
        log_message("Cliente ID recibido: $cliente_id");

        // Verificar existencia del cliente
        $check_cliente = $conn->prepare("SELECT razon_social FROM clientes WHERE id = ?");
        $check_cliente->bind_param('i', $cliente_id);
        $check_cliente->execute();
        $result = $check_cliente->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("El cliente con ID $cliente_id no existe en la base de datos");
        }
        
        $cliente_data = $result->fetch_assoc();
        log_message("Cliente encontrado: " . $cliente_data['razon_social']);

        // Procesar archivo Excel
        $archivo = $_FILES['archivo']['tmp_name'];
        log_message("Archivo recibido: " . $_FILES['archivo']['name']);
        
        $documento = IOFactory::load($archivo);
        $hoja = $documento->getActiveSheet()->toArray();
        log_message("Filas en el archivo: " . count($hoja));

        // Preparar consultas SQL
        $sql_insert = "INSERT INTO productos_cliente (
            cliente_id, nombre_cliente, empresa, codigo_barras, marca, categoria, segmento,
            descripcion, presentacion, unidad_presentacion,
            agotados, inventarios, sugeridos, unidades_surtidas, devoluciones,
            averias, transferencias, precios, ventas, precio_producto, vigencia, competencia, actividades
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $sql_check = "SELECT id FROM productos_cliente WHERE cliente_id = ? AND codigo_barras = ?";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_check = $conn->prepare($sql_check);
        
        if (!$stmt_insert || !$stmt_check) {
            throw new Exception("Error al preparar consultas: " . $conn->error);
        }

        $insertados = 0;
        $omitidos = 0;
        $errores = 0;

        foreach ($hoja as $i => $fila) {
            if ($i === 0) {
                log_message("Encabezados: " . implode(", ", $fila));
                continue; // Saltar encabezado
            }

            log_message("Procesando fila $i: " . json_encode($fila));

            // Validar estructura básica
            if (count($fila) < 23) {
                log_message("Fila $i omitida: estructura incompleta");
                $omitidos++;
                continue;
            }

            // Asignar variables individuales
            $nombre_cliente = trim($fila[0]);
            $empresa = trim($fila[1]);
            $codigo_barras = trim($fila[2]);
            $marca = trim($fila[4]);
            $categoria = trim($fila[5]);
            $segmento = trim($fila[6]);
            $descripcion = trim($fila[7]);
            $presentacion = trim($fila[8]);
            $unidad_presentacion = trim($fila[9]);
            $agotados = (int)$fila[10];
            $inventarios = (int)$fila[11];
            $sugeridos = (int)$fila[12];
            $unidades_surtidas = (int)$fila[13];
            $devoluciones = (int)$fila[14];
            $averias = (int)$fila[15];
            $transferencias = (int)$fila[16];
            $precios = (int)$fila[17];
            $ventas = (int)$fila[18];
            $precio_producto = (int)$fila[19];
            $vigencia = (int)$fila[20];
            $competencia = (int)$fila[21];
            $actividades = (int)$fila[22];

            // Validaciones
            if (empty($codigo_barras)) {
                log_message("Fila $i omitida: código de barras vacío");
                $omitidos++;
                continue;
            }

            if (empty($marca)) {
                log_message("Fila $i omitida: marca vacía");
                $omitidos++;
                continue;
            }

            // Verificar si el producto ya existe
            $stmt_check->bind_param('is', $cliente_id, $codigo_barras);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows > 0) {
                log_message("Fila $i omitida: producto $codigo_barras ya existe para este cliente");
                $omitidos++;
                continue;
            }

            // Insertar producto
            try {
                $stmt_insert->bind_param(
                    'isssssssssiiiiiiiiiiiii',
                    $cliente_id,
                    $nombre_cliente,
                    $empresa,
                    $codigo_barras,
                    $marca,
                    $categoria,
                    $segmento,
                    $descripcion,
                    $presentacion,
                    $unidad_presentacion,
                    $agotados,
                    $inventarios,
                    $sugeridos,
                    $unidades_surtidas,
                    $devoluciones,
                    $averias,
                    $transferencias,
                    $precios,
                    $ventas,
                    $precio_producto,
                    $vigencia,
                    $competencia,
                    $actividades
                );

                $stmt_insert->execute();
                $insertados++;
                log_message("Fila $i insertada: $codigo_barras - $marca");
                
            } catch (mysqli_sql_exception $e) {
                $errores++;
                log_message("ERROR en fila $i: " . $e->getMessage());
                // Continuar con las siguientes filas
            }
        }

        $conn->commit();
        log_message("Proceso completado. Insertados: $insertados, Omitidos: $omitidos, Errores: $errores");
        
        // Mostrar resumen al usuario
        echo "<div class='resultado'>";
        echo "<h3>Resultado de la importación</h3>";
        echo "<p><strong>Productos insertados:</strong> $insertados</p>";
        echo "<p><strong>Registros omitidos:</strong> $omitidos</p>";
        echo "<p><strong>Errores encontrados:</strong> $errores</p>";
        
        if ($insertados > 0) {
            echo "<p class='success'>✅ La importación se completó con éxito.</p>";
        } else {
            echo "<p class='warning'>⚠️ No se insertaron nuevos productos. Verifique los logs.</p>";
        }
        
        echo '<a href="listar_clientes.php" class="btn">🔙 Volver a clientes</a>';
        echo "</div>";

    } catch (Exception $e) {
        $conn->rollback();
        log_message("ERROR GENERAL: " . $e->getMessage());
        
        echo "<div class='error'>";
        echo "<h3>Error en la importación</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Consulte el archivo de log para más detalles.</p>";
        echo '<a href="listar_clientes.php" class="btn">🔙 Volver a clientes</a>';
        echo "</div>";
    } finally {
        $conn->autocommit(TRUE);
        log_message("Conexión cerrada\n");
    }
} else {
    log_message("Acceso inválido al script");
    echo "<div class='error'>";
    echo "<p>Error: No se recibieron los datos necesarios.</p>";
    echo '<a href="listar_clientes.php" class="btn">🔙 Volver a clientes</a>';
    echo "</div>";
}
?>

<style>
    .resultado { background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px; }
    .error { background: #ffebee; padding: 20px; border-radius: 5px; margin: 20px; color: #d32f2f; }
    .success { color: #388e3c; }
    .warning { color: #ffa000; }
    .btn { 
        display: inline-block; 
        margin-top: 15px; 
        padding: 8px 15px; 
        background: #2196f3; 
        color: white; 
        text-decoration: none; 
        border-radius: 4px; 
    }
    .btn:hover { background: #0d8aee; }
</style>
