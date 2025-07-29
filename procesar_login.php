<?php
session_start();
include 'conexion.php';

// Validar datos recibidos
if (empty($_POST['username']) || empty($_POST['password'])) {
    header("Location: login.php?error=Debe ingresar usuario y contraseña");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password']; // En producción, maneja hash, aquí para ejemplo claro

// Usar consulta preparada para evitar SQL Injection
$sql = "SELECT u.*, c.id AS cliente_id
        FROM usuarios u
        LEFT JOIN clientes c ON u.id = c.usuario_id
        WHERE u.username = ?
        LIMIT 1";

if (!$stmt = $conn->prepare($sql)) {
    // Error en preparar la consulta
    error_log("Error preparando consulta login: " . $conn->error);
    header("Location: login.php?error=Error en el servidor");
    exit;
}

$stmt->bind_param("s", $username);

if (!$stmt->execute()) {
    // Error en ejecución
    error_log("Error ejecutando consulta login: " . $stmt->error);
    header("Location: login.php?error=Error en el servidor");
    exit;
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: login.php?error=Usuario no encontrado");
    exit;
}

// Password: si en la base tienes texto plano, comparas directamente.
// Idealmente usa password_hash() y password_verify() para seguridad.
if ($user['password'] !== $password) {
    header("Location: login.php?error=Contraseña incorrecta");
    exit;
}

// Bloquear usuarios tipo movil
if ($user['tipo_usuario'] === 'movil') {
    header("Location: login.php?error=Acceso denegado para usuarios móviles");
    exit;
}

// Ahora almacenamos datos seguros en sesión
$_SESSION['usuario']    = $user['username'];
$_SESSION['rol']        = $user['tipo_usuario'];
$_SESSION['nombre'] = trim($user['nombre'] . ' ' . $user['apellidos']);
$_SESSION['usuario_id'] = (int)$user['id'];
// cliente_id puede ser NULL si no existe cliente para ese usuario
$_SESSION['cliente_id'] = isset($user['cliente_id']) ? (int)$user['cliente_id'] : null;



$stmt->close();
$conn->close();

// Redirigir a dashboard
header("Location: dashboard.php");
exit;
?>
