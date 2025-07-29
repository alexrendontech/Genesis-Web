<?php
include 'conexion.php';

// Validar que las claves existen en $_POST antes de usarlas
// Esto evita las advertencias "Undefined array key"
$razon_social = isset($_POST['razon_social']) ? $_POST['razon_social'] : '';
$nit = isset($_POST['nit']) ? $_POST['nit'] : '';
$categoria = isset($_POST['categoria_cliente']) ? $_POST['categoria_cliente'] : '';
$ciudad = isset($_POST['ciudad_base']) ? $_POST['ciudad_base'] : '';
$marca = isset($_POST['marca_participacion']) ? $_POST['marca_participacion'] : '';
$caras = isset($_POST['caras_unidades']) ? $_POST['caras_unidades'] : '';


// Generar username y password para el cliente
$username = strtolower(str_replace(' ', '', $razon_social)); // Quitar espacios y minúsculas
$username = substr($username, 0, 10); // Limitar a 10 caracteres
$password = substr($nit, 0, 4); // Primeros 4 dígitos del NIT

// Iniciar transacción
mysqli_begin_transaction($conn);

try {
    // PASO 1: Insertar en la tabla 'usuarios'.
    $sql_usuario = "INSERT INTO usuarios (nombre, apellidos, cedula, zona, tipo_usuario, username, password)
                    VALUES (?, '', ?, ?, 'cliente', ?, ?)"; // 5 placeholders
    
    // Preparar la declaración para usuarios
    $stmt_usuario = mysqli_prepare($conn, $sql_usuario);
    if (!$stmt_usuario) {
        throw new Exception("Error al preparar la consulta de usuario: " . mysqli_error($conn));
    }
    
    // CORRECCIÓN AQUÍ: Cambiar "ssssss" a "sssss" para 5 variables
    mysqli_stmt_bind_param($stmt_usuario, "sssss", $razon_social, $nit, $ciudad, $username, $password); // 5 's' por 5 variables
    
    if (!mysqli_stmt_execute($stmt_usuario)) {
        throw new Exception("Error al crear usuario: " . mysqli_stmt_error($stmt_usuario));
    }
    
    // Obtener el ID que MySQL auto-generó para el nuevo usuario
    $usuario_id = mysqli_insert_id($conn);
    
    // PASO 2: Insertar en la tabla 'clientes'.
    // Esta parte ya estaba correcta
    $sql_cliente = "INSERT INTO clientes (usuario_id, razon_social, nit, categoria_cliente, ciudad_base, marca_participacion, caras_unidades)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"; // 7 placeholders
    
    // Preparar la declaración para clientes
    $stmt_cliente = mysqli_prepare($conn, $sql_cliente);
    if (!$stmt_cliente) {
        throw new Exception("Error al preparar la consulta de cliente: " . mysqli_error($conn));
    }

    // 1 entero ($usuario_id) y 6 strings para los demás campos
    mysqli_stmt_bind_param($stmt_cliente, "issssss", $usuario_id, $razon_social, $nit, $categoria, $ciudad, $marca, $caras);
    if (!mysqli_stmt_execute($stmt_cliente)) {
        throw new Exception("Error al crear cliente: " . mysqli_stmt_error($stmt_cliente));
    }
    
    // Obtener el ID que MySQL auto-generó para el nuevo cliente
    $cliente_id = mysqli_insert_id($conn);
    
    // Confirmar la transacción si todo fue bien
    mysqli_commit($conn);
    
    echo "✅ Cliente registrado correctamente.<br>";
    echo "ID Usuario: $usuario_id<br>";
    echo "ID Cliente: $cliente_id<br>";
    echo "Usuario: " . htmlspecialchars($username) . "<br>";
    echo "Contraseña: " . htmlspecialchars($password) . "<br>";
    echo "<a href='crear_usuario.php'>🔙 Volver</a>";
    
} catch (Exception $e) {
    // Revertir la transacción en caso de cualquier error
    mysqli_rollback($conn);
    echo "❌ Error: " . $e->getMessage();
} finally {
    // Cerrar las declaraciones preparadas si se crearon
    if (isset($stmt_usuario)) {
        mysqli_stmt_close($stmt_usuario);
    }
    if (isset($stmt_cliente)) {
        mysqli_stmt_close($stmt_cliente);
    }
}
?>