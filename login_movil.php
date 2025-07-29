<?php
include 'conexion.php';

// Evita warnings por claves no definidas
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

// Validación simple
if (!$username || !$password) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Faltan credenciales"
    ]);
    exit;
}

$sql = "SELECT id, nombre, apellidos FROM usuarios WHERE username = ? AND password = ? AND tipo_usuario = 'movil'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $nombreCompleto = trim($row['nombre'] . ' ' . $row['apellidos']);
    echo json_encode([
        "success" => true,
        "id" => $row['id'],
        "nombre" => $nombreCompleto,
        "mensaje" => "Inicio de sesión exitoso"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Credenciales incorrectas"
    ]);
}
?>



