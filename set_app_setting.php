<?php
// set_app_setting.php - Versión mejorada con mejor manejo de transacciones

// No mostrar errores PHP en la salida para evitar romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers básicos para JSON y CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Función para escribir logs
function writeDebugLog($message) {
    error_log(date('Y-m-d H:i:s') . " - SET_DEBUG: " . $message);
}

// Respuesta por defecto
$response = ['success' => false, 'message' => 'Error desconocido', 'debug' => []];

try {
    writeDebugLog("Iniciando set_app_setting.php");
    
    // Solo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Solo se permiten solicitudes POST';
        $response['debug'][] = 'Método incorrecto: ' . $_SERVER['REQUEST_METHOD'];
        writeDebugLog("Método incorrecto: " . $_SERVER['REQUEST_METHOD']);
        echo json_encode($response);
        exit;
    }
    
    writeDebugLog("Método POST verificado");
    
    // Verificar si el archivo conexion.php existe
    if (!file_exists('conexion.php')) {
        throw new Exception('Archivo conexion.php no encontrado');
    }
    
    writeDebugLog("Archivo conexion.php encontrado");
    
    // Conexión a BD
    require_once 'conexion.php';
    $response['debug'][] = 'Archivo conexion.php incluido';
    
    // Verificar conexión
    if (!isset($conn)) {
        throw new Exception('Variable $conn no definida en conexion.php');
    }
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión BD: ' . $conn->connect_error);
    }
    
    writeDebugLog("Conexión BD exitosa");
    $response['debug'][] = 'Conexión BD exitosa';
    
    // Leer datos JSON
    $input = file_get_contents('php://input');
    writeDebugLog("Input recibido: " . $input);
    $response['debug'][] = 'Input: ' . $input;
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error JSON: ' . json_last_error_msg());
    }
    
    writeDebugLog("JSON decodificado correctamente");
    $response['debug'][] = 'JSON decodificado: ' . print_r($data, true);
    
    // Validar datos básicos
    if (!$data || !isset($data['setting_key']) || !isset($data['setting_value'])) {
        throw new Exception('Datos inválidos - faltan setting_key o setting_value');
    }
    
    $setting_key = $data['setting_key'];
    $setting_value = $data['setting_value'];
    
    writeDebugLog("Datos extraídos - Key: $setting_key, Value: $setting_value");
    $response['debug'][] = "Key: $setting_key, Value: $setting_value";
    
    // Validar valores específicos
    if ($setting_key !== 'date_restriction_enabled' || !in_array($setting_value, ['0', '1'])) {
        throw new Exception('Valores no permitidos - Key: ' . $setting_key . ', Value: ' . $setting_value);
    }
    
    writeDebugLog("Validación de datos exitosa");
    
    // Verificar si la tabla existe
    $tableCheck = $conn->query("SHOW TABLES LIKE 'app_settings'");
    if ($tableCheck->num_rows === 0) {
        throw new Exception('Tabla app_settings no existe');
    }
    
    writeDebugLog("Tabla app_settings existe");
    $response['debug'][] = 'Tabla app_settings verificada';
    
    // NUEVO ENFOQUE: Usar INSERT ... ON DUPLICATE KEY UPDATE directamente
    // Esto es más confiable que intentar UPDATE primero
    writeDebugLog("Preparando INSERT ON DUPLICATE KEY UPDATE");
    
    $description = 'Habilita o deshabilita la restricción de subir fotos de galería tomadas en días anteriores. 1 para habilitado, 0 para deshabilitado.';
    
    $stmt = $conn->prepare("
        INSERT INTO app_settings (setting_key, setting_value, description) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value), 
        description = VALUES(description)
    ");
    
    if ($stmt === false) {
        throw new Exception('Error preparando statement: ' . $conn->error);
    }
    
    $stmt->bind_param("sss", $setting_key, $setting_value, $description);
    
    writeDebugLog("Statement preparado, ejecutando...");
    $response['debug'][] = "Ejecutando INSERT ON DUPLICATE KEY UPDATE con key='$setting_key', value='$setting_value'";
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        writeDebugLog("Statement ejecutado exitosamente. Filas afectadas: " . $affected_rows);
        $response['debug'][] = 'Filas afectadas: ' . $affected_rows;
        
        // Verificar que realmente se guardó
        $stmt_verify = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt_verify->bind_param("s", $setting_key);
        $stmt_verify->execute();
        $result_verify = $stmt_verify->get_result();
        $row_verify = $result_verify->fetch_assoc();
        $stmt_verify->close();
        
        if ($row_verify && $row_verify['setting_value'] === $setting_value) {
            $response = [
                'success' => true, 
                'message' => 'Configuración guardada exitosamente', 
                'debug' => $response['debug'],
                'verified_value' => $row_verify['setting_value'],
                'affected_rows' => $affected_rows
            ];
            writeDebugLog("Verificación exitosa: valor guardado = " . $row_verify['setting_value']);
        } else {
            throw new Exception('Error en verificación: el valor no se guardó correctamente. Esperado: ' . $setting_value . ', Encontrado: ' . ($row_verify ? $row_verify['setting_value'] : 'NULL'));
        }
        
    } else {
        throw new Exception('Error ejecutando statement: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
    writeDebugLog("Proceso completado exitosamente");
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    $response['message'] = $errorMsg;
    $response['debug'][] = 'ERROR: ' . $errorMsg;
    writeDebugLog("ERROR: " . $errorMsg);
    
    // Cerrar conexión si existe
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

// Asegurar que solo se devuelve JSON válido
echo json_encode($response);
?>

