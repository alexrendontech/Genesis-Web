<?php
// subir_foto_actividad.php - Script para subir fotos de actividades con marca de agua,
// restricción de fecha y detección de fotos reusadas (dHash).

// IMPORTANTE: Suprimir la visualización de errores PHP para asegurar una salida JSON limpia.
ini_set('display_errors', 0);
error_reporting(E_ALL); // Sigue registrando todos los errores en el log, pero no los muestra.

header('Content-Type: application/json');

// Solución 2: Seguridad CORS. Para producción, cambia '*' a tu dominio específico.
// header('Access-Control-Allow-Origin: *'); // COMENTAR O ELIMINAR PARA PRODUCCIÓN
header('Access-Control-Allow-Origin: https://tu-dominio-frontend.com'); // REEMPLAZA ESTO CON EL DOMINIO DE TU APLICACIÓN FRONTAL EN PRODUCCIÓN

header('Access-Control-Allow-Methods: POST, OPTIONS'); // Permitir POST y OPTIONS
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Permitir Content-Type y Authorization

// Manejar solicitudes OPTIONS (pre-flight requests)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solución 4: Definir el umbral de Hamming como una constante.
const HAMMING_DISTANCE_THRESHOLD = 10; // Umbral para la distancia de Hamming para detección de reuso (64 bits, ~8x8 dHash)


// Función para generar el dHash de una imagen
// Requiere la extensión GD de PHP
function generateDHash($imagePath, $hashSize = 8) {
    error_log("DEBUG_DHASH: Iniciando generateDHash para: " . $imagePath);

    // 1. Verificar existencia del archivo
    if (!file_exists($imagePath)) {
        error_log("ERROR_DHASH: Archivo de imagen no existe: " . $imagePath);
        return null;
    }
    error_log("DEBUG_DHASH: Archivo existe.");

    // 2. Verificar que la extensión GD esté cargada
    if (!extension_loaded('gd')) {
        error_log("ERROR_DHASH: GD extension is not loaded. Cannot generate dHash.");
        return null;
    }
    error_log("DEBUG_DHASH: GD extension is loaded.");

    $image = null;
    // 3. Intentar determinar el tipo de imagen y crear el recurso
    // Solución 3: Eliminar @
    $imageInfo = getimagesize($imagePath); 
    if ($imageInfo === false) {
        error_log("ERROR_DHASH: No se pudo determinar el tipo de imagen o archivo corrupto: " . $imagePath);
        return null;
    }
    error_log("DEBUG_DHASH: Image size obtained. Mime: " . $imageInfo['mime']);

    $mime = $imageInfo['mime'];

    switch ($mime) {
        case 'image/jpeg':
            // Solución 3: Eliminar @
            $image = imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            // Solución 3: Eliminar @
            $image = imagecreatefrompng($imagePath);
            // Añadir manejo de transparencia para PNG
            if ($image) {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }
            break;
        case 'image/gif':
            // Solución 3: Eliminar @
            $image = imagecreatefromgif($imagePath);
            break;
        default:
            error_log("ERROR_DHASH: Formato de imagen no soportado: " . $mime . " para " . $imagePath);
            return null;
    }

    if ($image === false) {
        error_log("ERROR_DHASH: No se pudo crear recurso de imagen para: " . $imagePath . ". Verificar corrupción de imagen o soporte GD para este formato.");
        return null;
    }
    error_log("DEBUG_DHASH: Recurso de imagen creado. Dimensiones originales: " . imagesx($image) . "x" . imagesy($image));

    // --- MEJORA IMPLEMENTADA: Preprocesamiento para Normalización de Brillo y Contraste ---
    // Esto hace que el sistema sea más resistente a modificaciones visuales antes de generar el hash.
    imagefilter($image, IMG_FILTER_BRIGHTNESS, 0); // Ajuste neutro de brillo
    imagefilter($image, IMG_FILTER_CONTRAST, -10); // Ligeramente menos contraste para mayor robustez

    // 4. Redimensionar la imagen a (hashSize + 1) x hashSize
    $width = $hashSize + 1;
    $height = $hashSize;
    $resizedImage = imagecreatetruecolor($width, $height);
    
    // Asegurar fondo blanco para evitar problemas de transparencia al redimensionar a truecolor
    $white = imagecolorallocate($resizedImage, 255, 255, 255);
    imagefill($resizedImage, 0, 0, $white);

    if (!imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image))) {
        error_log("ERROR_DHASH: Fallo al redimensionar la imagen.");
        imagedestroy($image);
        imagedestroy($resizedImage);
        return null;
    }
    imagedestroy($image);
    error_log("DEBUG_DHASH: Imagen redimensionada a " . $width . "x" . $height);

    // 5. Generar hash binario
    $hash = '';
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $hashSize; $x++) {
            // Obtener el color de los píxeles adyacentes
            $pixel1 = imagecolorat($resizedImage, $x, $y);
            $pixel2 = imagecolorat($resizedImage, $x + 1, $y);
            
            // Convertir a escala de grises usando la fórmula de luminancia (más precisa)
            $left_rgb = imagecolorsforindex($resizedImage, $pixel1);
            $right_rgb = imagecolorsforindex($resizedImage, $pixel2);

            $left_gray = $left_rgb['red'] * 0.299 + $left_rgb['green'] * 0.587 + $left_rgb['blue'] * 0.114;
            $right_gray = $right_rgb['red'] * 0.299 + $right_rgb['green'] * 0.587 + $right_rgb['blue'] * 0.114;
            
            // Comparar y añadir al hash
            $hash .= ($left_gray > $right_gray) ? '1' : '0';
        }
    }
    imagedestroy($resizedImage);

    error_log("DEBUG_DHASH: Hash binario generado. Longitud: " . strlen($hash) . ". Primeros 10 bits: " . substr($hash, 0, 10));

    // Solución 1: Convertir el hash binario a hexadecimal de forma segura
    // Un hash de 64 bits (8x8) se convierte a 16 caracteres hexadecimales
    $hexHash = '';
    for ($i = 0; $i < strlen($hash); $i += 4) {
        $nibble = substr($hash, $i, 4);
        // bindec() convierte una cadena binaria a entero, dechex() convierte a hexadecimal
        // Para 4 bits, esto es seguro ya que 1111 (binario) es 15 (decimal), lo cual está dentro de los límites de entero.
        $hexHash .= dechex(bindec($nibble));
    }
    // Asegurarse de que el hash hexadecimal tenga la longitud esperada (64 bits = 16 caracteres hex)
    $hexHash = str_pad($hexHash, 16, '0', STR_PAD_LEFT);
    
    error_log("DEBUG_DHASH: Hash hexadecimal final (solución): " . $hexHash . ". Longitud: " . strlen($hexHash));
    
    return $hexHash;
}

// Función para calcular la distancia de Hamming entre dos hashes dHash
function hammingDistance($hash1, $hash2) {
    if (strlen($hash1) !== strlen($hash2)) {
        error_log("ERROR_HAMMING: Hashes tienen diferentes longitudes. Hash1: " . strlen($hash1) . ", Hash2: " . strlen($hash2));
        return -1;
    }

    $distance = 0;
    $length = strlen($hash1);

    // Solución 1: Mapeo de hexadecimal a binario de 4 bits para comparación segura
    $hexToBin = [
        '0' => '0000', '1' => '0001', '2' => '0010', '3' => '0011',
        '4' => '0100', '5' => '0101', '6' => '0110', '7' => '0111',
        '8' => '1000', '9' => '1001', 'a' => '1010', 'b' => '1011',
        'c' => '1100', 'd' => '1101', 'e' => '1110', 'f' => '1111'
    ];

    // Iterar sobre cada carácter hexadecimal
    for ($i = 0; $i < $length; $i++) {
        // Asegurarse de que los caracteres sean minúsculas para el mapeo
        $binChar1 = $hexToBin[strtolower($hash1[$i])];
        $binChar2 = $hexToBin[strtolower($hash2[$i])];

        // Comparar bit a bit dentro de cada grupo de 4 bits (nibble)
        for ($j = 0; $j < 4; $j++) {
            if ($binChar1[$j] !== $binChar2[$j]) {
                $distance++;
            }
        }
    }
    return $distance;
}


// Respuesta por defecto
$response = array(
    'success' => false,
    'message' => 'Error desconocido.'
);

// --- Manejo de la conexión a la base de datos ---
$conn = null; // Inicializar $conn a null
try {
    // Incluir el archivo de conexión a la base de datos.
    // Se espera que 'conexion.php' establezca la variable $conn o la deje como null en caso de error.
    require_once 'conexion.php'; 

    // Verificar si la conexión se estableció correctamente.
    // Si $conn es null, significa que hubo un error en conexion.php.
    // Si $conn no es null, pero connect_error no está vacío, también hubo un error.
    if ($conn === null || ($conn instanceof mysqli && $conn->connect_error)) { 
        $error_msg = ($conn !== null && $conn instanceof mysqli) ? $conn->connect_error : 'Variable $conn no definida o conexión fallida en conexion.php';
        throw new Exception("Error de conexión a la base de datos: " . $error_msg);
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("ERROR_DB_CONNECTION: " . $e->getMessage());
    echo json_encode($response);
    exit();
}


// Obtener y validar datos del formulario
// Usar filter_var para sanitizar y validar entradas
$cliente_id = filter_var($_POST['cliente_id'] ?? null, FILTER_VALIDATE_INT);
$punto_venta_id = filter_var($_POST['punto_venta_id'] ?? null, FILTER_VALIDATE_INT);
$usuario_movil_id = filter_var($_POST['usuario_movil_id'] ?? null, FILTER_VALIDATE_INT); // <-- Aquí se define $usuario_movil_id

// Usar FILTER_UNSAFE_RAW para permitir caracteres especiales en la descripción,
// pero siempre usar sentencias preparadas para la inserción en DB.
$tipo_actividad = filter_var($_POST['tipo_actividad'] ?? null, FILTER_UNSAFE_RAW);
$descripcion_actividad = filter_var($_POST['descripcion_actividad'] ?? null, FILTER_UNSAFE_RAW);

// Validar que los IDs sean enteros positivos y que los strings no estén vacíos
if ($cliente_id === false || $cliente_id <= 0 || 
    $punto_venta_id === false || $punto_venta_id <= 0 || 
    $usuario_movil_id === false || $usuario_movil_id <= 0 || 
    empty($tipo_actividad) || !isset($_FILES['file'])) 
{
    $response['message'] = 'Faltan datos requeridos o son inválidos (cliente_id, punto_venta_id, usuario_movil_id, tipo_actividad, archivo).';
    error_log("ERROR_INPUT_VALIDATION: " . $response['message'] . " POST data: " . print_r($_POST, true));
    echo json_encode($response);
    exit();
}

$file = $_FILES['file'];

// Validar subida del archivo
if ($file['error'] !== UPLOAD_ERR_OK) {
    $phpFileUploadErrors = array(
        UPLOAD_ERR_OK           => 'No hay error, el archivo se subió con éxito.',
        UPLOAD_ERR_INI_SIZE     => 'El archivo subido excede la directiva upload_max_filesize en php.ini.',
        UPLOAD_ERR_FORM_SIZE    => 'El archivo subido excede la directiva MAX_FILE_SIZE especificada en el formulario HTML.',
        UPLOAD_ERR_PARTIAL      => 'El archivo subido fue solo parcialmente subido.',
        UPLOAD_ERR_NO_FILE      => 'No se subió ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR   => 'Falta una carpeta temporal.',
        UPLOAD_ERR_CANT_WRITE   => 'Fallo al escribir el archivo en el disco.',
        UPLOAD_ERR_EXTENSION    => 'Una extensión de PHP detuvo la subida del archivo.'
    );
    $response['message'] = 'Error al subir el archivo: ' . ($phpFileUploadErrors[$file['error']] ?? 'Error desconocido');
    error_log("ERROR_FILE_UPLOAD: " . $response['message'] . " File error code: " . $file['error']);
    echo json_encode($response);
    exit();
}

// --- INICIO DEL CÓDIGO PARA ORGANIZAR CARPETAS ---

// Obtener año y mes actual
$year = date('Y');
$month = date('m');
$user_dir = 'user_' . $usuario_movil_id; // Puedes usar solo $usuario_movil_id si prefieres

$uploadBaseDir = 'uploads/actividades/';
$uploadDir = $uploadBaseDir . $year . '/' . $month . '/' . $user_dir . '/'; // Construir la ruta completa

// Crear el directorio si no existe
if (!is_dir($uploadDir)) {
    // Usar 0755 para mayor seguridad, true para crear directorios anidados
    if (!mkdir($uploadDir, 0755, true)) {
        $response['message'] = 'Error: No se pudo crear el directorio de subida: ' . $uploadDir;
        error_log("ERROR_DIR_CREATE: No se pudo crear el directorio: " . $uploadDir);
        echo json_encode($response);
        exit();
    }
    error_log("DEBUG_DIR_CREATE: Directorio creado: " . $uploadDir);
} elseif (!is_writable($uploadDir)) {
    $response['message'] = 'Error: El directorio de subida no tiene permisos de escritura: ' . $uploadDir;
    error_log("ERROR_DIR_PERMISSIONS: Directorio no escribible: " . $uploadDir);
    echo json_encode($response);
    exit();
}

// Generar nombre único para el archivo basado en un ID único y un timestamp
$fileName = uniqid('foto_') . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$filePath = $uploadDir . $fileName; // <-- $filePath ahora usa la nueva $uploadDir

// --- FIN DEL CÓDIGO PARA ORGANIZAR CARPETAS ---

// Mover el archivo subido al directorio final
if (!move_uploaded_file($file['tmp_name'], $filePath)) { // <-- Esta línea usará la nueva $filePath
    $response['message'] = 'Error al mover el archivo subido al destino final.';
    error_log("ERROR_MOVE_UPLOADED_FILE: Fallo al mover el archivo de " . $file['tmp_name'] . " a " . $filePath);
    echo json_encode($response);
    exit();
}

// --- GENERAR Y DETECTAR HASH PERCEPTUAL ---
$perceptual_hash = generateDHash($filePath);
$es_reusada = FALSE;
$id_foto_original = NULL;
$detection_message = '';

// Validar la longitud del hash generado
if ($perceptual_hash !== null && strlen($perceptual_hash) !== 16) { // Un dHash de 8x8 bits es 16 caracteres hexadecimales.
    error_log("ERROR_MAIN: Hash perceptual generado tiene longitud inesperada: " . $perceptual_hash . " (longitud " . strlen($perceptual_hash) . ") para " . $filePath);
    $perceptual_hash = null; // Si la longitud es incorrecta, lo invalidamos
}

if ($perceptual_hash) {
    // Solución 4: Usar la constante para el umbral de Hamming.
    $hamming_threshold = HAMMING_DISTANCE_THRESHOLD; 

    // Buscar fotos existentes del mismo usuario y punto de venta con hash perceptual
    // Excluir fotos que ya fueron marcadas como reusadas para evitar comparaciones redundantes
    // Limitar la búsqueda a las últimas 50 fotos para eficiencia
    $stmt_check = $conn->prepare("SELECT id, perceptual_hash, fecha_hora_captura FROM reporte_fotos_actividades WHERE usuario_movil_id = ? AND punto_venta_id = ? AND perceptual_hash IS NOT NULL AND es_reusada = FALSE ORDER BY fecha_hora_captura DESC LIMIT 50"); 
    
    if ($stmt_check === false) {
        error_log("ERROR_DB: Error al preparar la consulta de verificación de hash: " . $conn->error);
    } else {
        $stmt_check->bind_param("ii", $usuario_movil_id, $punto_venta_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        while ($row = $result_check->fetch_assoc()) {
            // Solo intentar comparar si el hash almacenado tiene la longitud esperada (16 caracteres)
            if ($row['perceptual_hash'] !== null && strlen($row['perceptual_hash']) === 16) {
                $distance = hammingDistance($perceptual_hash, $row['perceptual_hash']);
                
                if ($distance !== -1 && $distance <= $hamming_threshold) {
                    $es_reusada = TRUE;
                    $id_foto_original = $row['id'];
                    $detection_message = "Detectada como posible reutilización de la foto ID " . $row['id'] . " (subida el " . $row['fecha_hora_captura'] . "). Distancia Hamming: " . $distance;
                    error_log("Detección de reuso: " . $detection_message . " para usuario " . $usuario_movil_id . " en PV " . $punto_venta_id . " con nueva foto " . $fileName);
                    break; // Salir del bucle una vez que se encuentra una coincidencia
                }
            } else {
                error_log("DEBUG_DHASH_CHECK: Ignorando hash existente inválido (no 16 chars): " . ($row['perceptual_hash'] ?? 'NULL') . " para foto ID: " . $row['id']);
            }
        }
        $stmt_check->close();
    }
} else {
    error_log("ERROR_MAIN: No se pudo generar un hash perceptual válido para la imagen: " . $filePath);
}


// --- GUARDAR EN LA BASE DE DATOS ---
// Obtener la fecha y hora actual del servidor para el registro en la base de datos
$fecha_hora_registro_db = date('Y-m-d H:i:s');

error_log("DEBUG_DB_INSERT: Datos a insertar:");
error_log("DEBUG_DB_INSERT: Cliente ID: " . $cliente_id);
error_log("DEBUG_DB_INSERT: PV ID: " . $punto_venta_id);
error_log("DEBUG_DB_INSERT: Usuario ID: " . $usuario_movil_id);
error_log("DEBUG_DB_INSERT: Tipo actividad: " . $tipo_actividad);
error_log("DEBUG_DB_INSERT: Descripción: " . ($descripcion_actividad ?? 'NULL'));
error_log("DEBUG_DB_INSERT: Archivo: " . $fileName);
error_log("DEBUG_DB_INSERT: Ruta: " . $filePath);
error_log("DEBUG_DB_INSERT: Fecha Hora DB: " . $fecha_hora_registro_db);
error_log("DEBUG_DB_INSERT: Hash: " . ($perceptual_hash ?? 'NULL'));
error_log("DEBUG_DB_INSERT: Es reusada: " . ($es_reusada ? '1' : '0'));
error_log("DEBUG_DB_INSERT: ID original: " . ($id_foto_original ?? 'NULL'));


$stmt = $conn->prepare("INSERT INTO reporte_fotos_actividades (
    cliente_id, 
    punto_venta_id, 
    usuario_movil_id, 
    tipo_actividad, 
    descripcion_actividad, 
    nombre_archivo_foto, 
    ruta_servidor_foto, 
    fecha_hora_captura,
    perceptual_hash,
    es_reusada,
    id_foto_original
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if ($stmt === false) {
    error_log("ERROR_DB: Error al preparar INSERT en reporte_fotos_actividades: " . $conn->error);
    $response['message'] = "Error interno del servidor al preparar la inserción en la base de datos.";
    if (file_exists($filePath)) { // Asegurarse de que el archivo exista antes de intentar borrarlo
        unlink($filePath); 
    }
    echo json_encode($response);
    exit();
}

// "iiissssssii" -> 11 tipos para 11 variables:
// cliente_id (i), punto_venta_id (i), usuario_movil_id (i), tipo_actividad (s),
// descripcion_actividad (s), nombre_archivo_foto (s), ruta_servidor_foto (s),
// fecha_hora_captura (s), perceptual_hash (s), es_reusada (i), id_foto_original (i)
$stmt->bind_param(
    "iiissssssii",
    $cliente_id,
    $punto_venta_id,
    $usuario_movil_id,
    $tipo_actividad,
    $descripcion_actividad,
    $fileName,
    $filePath,
    $fecha_hora_registro_db,
    $perceptual_hash,
    $es_reusada,
    $id_foto_original
);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Foto subida y registrada con éxito.';
    if ($es_reusada) {
        $response['message'] .= " " . $detection_message;
    }
} else {
    error_log("ERROR_DB: Error al ejecutar INSERT en reporte_fotos_actividades: " . $stmt->error);
    $response['message'] = 'Error al registrar la foto en la base de datos: ' . $stmt->error;
    if (file_exists($filePath)) { // Asegurarse de que el archivo exista antes de intentar borrarlo
        unlink($filePath); 
    }
}

$stmt->close();
// Asegurarse de que $conn no sea null y que la conexión esté activa antes de intentar cerrarla
if ($conn && $conn->ping()) { 
    $conn->close();
}

echo json_encode($response);
?>

    







    






