<?php
// get_app_setting.php
// Este script lee la configuración de 'date_restriction_enabled' desde la tabla 'app_settings'.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Función para escribir logs de debug
function writeDebugLog($message) {
    error_log(date('Y-m-d H:i:s') . " - GET_DEBUG: " . $message);
}

$response = array(
    'success' => false,
    'message' => 'Error desconocido al obtener configuración.',
    'is_date_restriction_enabled' => true, // Valor por defecto de seguridad
    'debug' => []
);

try {
    writeDebugLog("Iniciando get_app_setting.php");
    
    // Verificar método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $response['message'] = 'Método de solicitud no permitido. Solo se aceptan solicitudes GET.';
        $response['debug'][] = 'Método incorrecto: ' . $_SERVER['REQUEST_METHOD'];
        writeDebugLog('Método incorrecto: ' . $_SERVER['REQUEST_METHOD']);
        echo json_encode($response);
        exit();
    }
    
    writeDebugLog("Método GET verificado");
    
    // Verificar si el archivo conexion.php existe
    if (!file_exists('conexion.php')) {
        throw new Exception('Archivo conexion.php no encontrado');
    }
    
    writeDebugLog("Archivo conexion.php encontrado");
    
    // Incluir conexión
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
    
    // Verificar si la tabla existe
    $tableCheck = $conn->query("SHOW TABLES LIKE 'app_settings'");
    if ($tableCheck->num_rows === 0) {
        throw new Exception('Tabla app_settings no existe');
    }
    
    writeDebugLog("Tabla app_settings existe");
    $response['debug'][] = 'Tabla app_settings verificada';
    
    // Preparar consulta para obtener el valor
    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
    
    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $setting_key = 'date_restriction_enabled';
    $stmt->bind_param("s", $setting_key);
    
    writeDebugLog("Ejecutando consulta SELECT");
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        writeDebugLog("Consulta ejecutada. Filas encontradas: " . ($row ? '1' : '0'));
        $response['debug'][] = 'Consulta ejecutada. Resultado: ' . ($row ? 'Encontrado' : 'No encontrado');
        
        if ($row) {
            // Convertir a booleano
            $setting_value = $row['setting_value'];
            $is_enabled = ($setting_value === '1' || $setting_value === 1);
            
            $response['is_date_restriction_enabled'] = $is_enabled;
            $response['success'] = true;
            $response['message'] = 'Configuración obtenida exitosamente.';
            
            writeDebugLog("Configuración encontrada: " . $setting_value . " (convertido a: " . ($is_enabled ? 'true' : 'false') . ")");
            $response['debug'][] = "Valor BD: '$setting_value', Convertido: " . ($is_enabled ? 'true' : 'false');
        } else {
            // No se encontró la configuración, usar valor por defecto
            $response['is_date_restriction_enabled'] = true;
            $response['success'] = true;
            $response['message'] = 'Configuración no encontrada, usando valor por defecto (habilitada).';
            
            writeDebugLog("Configuración no encontrada, usando default: true");
            $response['debug'][] = 'Configuración no encontrada, usando default: true';
        }
    } else {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
    writeDebugLog("Proceso GET completado exitosamente");
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    $response['message'] = $errorMsg;
    $response['debug'][] = 'ERROR: ' . $errorMsg;
    $response['is_date_restriction_enabled'] = true; // Valor por defecto en caso de error
    
    writeDebugLog("ERROR: " . $errorMsg);
    
    // Cerrar conexión si existe
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

echo json_encode($response);
?>




