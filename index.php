<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuarios</title>
    <script>
        function mostrarFormulario() {
            const tipo = document.getElementById("tipo_usuario").value;
            document.getElementById("form_cliente").style.display = tipo === "cliente" ? "block" : "none";
            document.getElementById("form_usuario").style.display = tipo !== "cliente" ? "block" : "none";
        }
    </script>
</head>
<body>

<h2>➕ Crear nuevo registro</h2>

<select id="tipo_usuario" name="tipo_usuario" onchange="mostrarFormulario()" required>
    <option value="">Seleccionar tipo de usuario</option>
    <option value="administrador">Administrador</option>
    <option value="movil">Móvil</option>
    <option value="supervisor">Supervisor</option>
    <option value="cliente">Cliente</option>
</select>

<!-- FORMULARIO USUARIO (movil, admin, supervisor) -->
<form id="form_usuario" action="crear_usuario.php" method="POST" style="display:none">
    <input type="hidden" name="tipo_usuario" value="otro">
    <label>Nombre:</label><br>
    <input type="text" name="nombre" required><br>

    <label>Apellidos:</label><br>
    <input type="text" name="apellidos" required><br>

    <label>Cédula:</label><br>
    <input type="text" name="cedula" required><br>

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
    </select><br><br>

    <label>Tipo de Usuario:</label><br>
    <select name="rol" required>
        <option value="">Seleccionar tipo</option>
        <option value="administrador">Administrador</option>
        <option value="movil">Móvil</option>
        <option value="supervisor">Supervisor</option>
    </select><br><br>

    <button type="submit">💾 Guardar Usuario</button>
</form>

<!-- FORMULARIO CLIENTE -->
<form id="form_cliente" action="crear_cliente.php" method="POST" style="display:none">
    <label>Razón social (Nombre del cliente):</label>
    <input type="text" name="razon_social" required><br>

    <label>NIT:</label>
    <input type="text" name="nit" required><br>

    <label>Categoría del cliente:</label>
    <input type="text" name="categoria_cliente" required><br>

    <label>Ciudad base:</label>
    <input type="text" name="ciudad_base" required><br>

    <label>Mide participación por:</label>
    <select name="marca_participacion" required>
        <option value="">Seleccione una opción</option>
        <option value="Marca A">Marcas</option>
        <option value="Marca B">Empresa</option>
    </select><br>

    <label>Unidades de participación:</label>
    <select name="caras_unidades" required>
        <option value="">Seleccione una opción</option>
        <option value="Cara A">Caras</option>
    </select><br><br>

    <button type="submit">💾 Guardar Cliente</button>
</form>

</body>
</html>
