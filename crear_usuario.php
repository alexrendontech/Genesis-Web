<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <script>
        function mostrarFormulario() {
            var rol = document.getElementById("rol").value;
            document.getElementById("form_usuario").style.display = (rol !== "cliente" && rol !== "") ? "block" : "none";
            document.getElementById("form_cliente").style.display = (rol === "cliente") ? "block" : "none";
            document.getElementById("rol_usuario_hidden").value = rol;
        }
    </script>
</head>
<body>
    <h2>➕ Crear nuevo usuario</h2>

    <label>Selecciona el rol:</label><br>
    <select id="rol" onchange="mostrarFormulario()" required>
        <option value="">Selecciona...</option>
        <option value="administrador">Administrador</option>
        <option value="supervisor">Supervisor</option>
        <option value="movil">Móvil</option>
        <option value="cliente">Cliente</option>
    </select>

    <br><br>

    <!-- FORMULARIO NORMAL (ADMIN, SUPERVISOR, MOVIL) -->
    <form id="form_usuario" action="guardar_usuario.php" method="POST" style="display: none;">
        <input type="hidden" name="rol" id="rol_usuario_hidden" value="">

        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Apellidos:</label><br>
        <input type="text" name="apellidos" required><br><br>

        <label>Cédula:</label><br>
        <input type="text" name="cedula" required><br><br>

        <label>Zona:</label><br>
        <select name="zona" required>
            <option value="">Seleccionar zona</option>
            <option value="Antioquia">Antioquia</option>
            <option value="Centro">Centro</option>
            <option value="Eje cafetero">Eje cafetero</option>
            <option value="Norte">Norte</option>
            <option value="Occidente">Occidente</option>
            <option value="Oriente">Oriente</option>
            <option value="Tolima">Tolima</option>
        </select><br>

        <button type="submit">Crear usuario</button>
    </form>

    <!-- FORMULARIO CLIENTE -->
    <form id="form_cliente" action="crear_cliente.php" method="POST" style="display: none;">
        <h2>➕ Crear nuevo cliente</h2>

        <!-- Razón social -->
        <label>Razón social (Nombre del cliente):</label><br>
        <input type="text" name="razon_social" required><br><br>

        <!-- NIT -->
        <label>NIT:</label><br>
        <input type="text" name="nit" required><br><br>

        <!-- Categoría del cliente -->
        <label>Categoría del cliente:</label><br>
        <input type="text" name="categoria_cliente" required><br><br>

        <!-- Ciudad base -->
        <label>Ciudad base:</label><br>
        <input type="text" name="ciudad_base" required><br><br>

        <!-- Mide participación por -->
        <label>Mide participación por:</label><br>
        <select name="marca_participacion" required>
            <option value="">Seleccione una opción</option>
            <option value="Marca A">Marcas</option>
            <option value="Marca B">Empresa</option>
        </select><br><br>

        <!-- Unidades de participación -->
        <label>Unidades de participación:</label><br>
        <select name="caras_unidades" required>
            <option value="">Seleccione una opción</option>
            <option value="Cara A">Caras</option>
        </select><br><br>

        <button type="submit">Crear cliente</button>
    </form>

    <br><a href="dashboard.php">🔙 Volver al panel</a>
</body>
</html>




