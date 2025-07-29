<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

include 'conexion.php';

// Query específica para clientes
$sql = "SELECT 
    c.id as cliente_id,
    c.usuario_id,
    c.razon_social,
    c.nit,
    c.categoria_cliente,
    c.ciudad_base,
    c.marca_participacion,
    c.caras_unidades,
    u.username,
    u.password
FROM clientes c
INNER JOIN usuarios u ON c.usuario_id = u.id
ORDER BY c.razon_social ASC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            padding: 8px;
            border: 1px solid #999;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
        .acciones button {
            padding: 4px 8px;
            margin: 2px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h2>🏢 Todos los clientes registrados</h2>

    <table>
        <thead>
            <tr>
                <th>ID Cliente</th>
                <th>Razón Social</th>
                <th>NIT</th>
                <th>Categoría</th>
                <th>Ciudad Base</th>
                <th>Participación</th>
                <th>Unidades</th>
                <th>Usuario</th>
                <th>Contraseña</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['cliente_id'] ?></td>
                    <td><?= htmlspecialchars($row['razon_social']) ?></td>
                    <td><?= htmlspecialchars($row['nit']) ?></td>
                    <td><?= htmlspecialchars($row['categoria_cliente']) ?></td>
                    <td><?= htmlspecialchars($row['ciudad_base']) ?></td>
                    <td><?= htmlspecialchars($row['marca_participacion']) ?></td>
                    <td><?= htmlspecialchars($row['caras_unidades']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['password']) ?></td>
                    <td class="acciones">
                        <form action="editar_cliente.php" method="GET" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['cliente_id'] ?>">
                            <button type="submit">📝 Editar</button>
                        </form>

                        <form action="eliminar_cliente.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
                            <input type="hidden" name="id" value="<?= $row['cliente_id'] ?>">
                            <button type="submit">🗑️ Eliminar</button>
                        </form>

                        <form action="subir_productos.php" method="GET" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['cliente_id'] ?>">
                            <button type="submit">📤 Subir productos</button>
                        </form>

                        <form action="ver_productos_cliente.php" method="GET" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['cliente_id'] ?>">
                            <button type="submit">📦 Ver productos</button>
                    </form>

                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <br>
    <a href="listar_usuarios.php">👥 Ver usuarios (admin/supervisor/móvil)</a> | 
    <a href="dashboard.php">🔙 Volver al inicio</a>
</body>
</html>
