<?php
include 'conexion.php';

$nombre = $_POST['nombre'];
$apellidos = $_POST['apellidos'];
$cedula = $_POST['cedula'];
$zona = $_POST['zona'];
$rol = $_POST['rol'];

// Generar username y password
$primer_nombre = explode(" ", $nombre)[0];
$primer_apellido = explode(" ", $apellidos)[0];
$username = strtolower(substr($primer_nombre, 0, 1) . $primer_apellido);
$password = substr($cedula, 0, 4); // sin cifrar por ahora

// Iniciar transacción
mysqli_begin_transaction($conn);

try {
    // Ya no necesitas generar un ID manual, la tabla 'usuarios' es AUTO_INCREMENT
    // Elimina las líneas que intentan insertar en id_master

    // 1. Insertar usuario permitiendo que 'id' se auto-incremente
    $sql = "INSERT INTO usuarios (nombre, apellidos, cedula, zona, tipo_usuario, username, password)
            VALUES ('$nombre', '$apellidos', '$cedula', '$zona', '$rol', '$username', '$password')";

    if (!mysqli_query($conn, $sql)) {
        throw new Exception("Error al crear usuario: " . mysqli_error($conn));
    }
    
    // Obtener el ID que se auto-generó para el nuevo usuario
    $usuario_id = mysqli_insert_id($conn); // Esto aún es útil para mostrar el ID al usuario
    
    // Confirmar transacción
    mysqli_commit($conn);
    
    echo "✅ Usuario registrado correctamente.<br>";
    echo "ID: $usuario_id<br>";
    echo "Usuario: $username<br>";
    echo "Contraseña: $password<br>";
    echo "<a href='crear_usuario.php'>🔙 Volver</a>";
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($conn);
    echo "❌ Error: " . $e->getMessage();
}
?>
