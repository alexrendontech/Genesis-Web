<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

include 'conexion.php';

// Query SOLO para usuarios que NO sean clientes
$sql = "SELECT * FROM usuarios 
        WHERE tipo_usuario != 'cliente' 
        ORDER BY tipo_usuario ASC, nombre ASC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Usuarios</title>
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
        .bug {
            color: red;
            font-weight: bold;
        }
        .acciones button {
            padding: 4px 8px;
            margin: 2px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h2>👥 Usuarios del sistema (Admin/Supervisor/Móvil)</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Cédula</th>
                <th>Zona</th>
                <th>Rol</th>
                <th>Usuario</th>
                <th>Contraseña</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>

                        <td>
                            <?php
                            $nombre = trim($row['nombre']);
                            $apellidos = trim($row['apellidos']);
                            echo (!empty($nombre) || !empty($apellidos))
                                ? htmlspecialchars($nombre . ' ' . $apellidos)
                                : "<span class='bug'>⚠️ Sin nombre/apellidos</span>";
                            ?>
                        </td>

                        <td><?= !empty($row['cedula']) ? htmlspecialchars($row['cedula']) : "<span class='bug'>⚠️ Sin cédula</span>" ?></td>

                        <td><?= !empty($row['zona']) ? htmlspecialchars($row['zona']) : "<span class='bug'>⚠️ Sin zona</span>" ?></td>

                        <td><strong><?= ucfirst($row['tipo_usuario']) ?></strong></td>

                        <td><?= !empty($row['username']) ? htmlspecialchars($row['username']) : "<span class='bug'>⚠️ Sin usuario</span>" ?></td>
                        <td><?= !empty($row['password']) ? htmlspecialchars($row['password']) : "<span class='bug'>⚠️ Sin contraseña</span>" ?></td>

                        <td class="acciones">
                            <form action="editar_usuario.php" method="GET" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit">📝 Editar</button>
                            </form>

                            <form action="eliminar_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit">🗑️ Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">
                        <strong>No hay usuarios registrados (solo administradores, supervisores o móviles)</strong>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>
    <a href="listar_clientes.php">🏢 Ver clientes</a> | 
    <a href="dashboard.php">🔙 Volver al inicio</a>
</body>
</html>

