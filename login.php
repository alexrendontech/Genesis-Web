<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso al sistema</title>
</head>
<body>
    <div style="text-align: center;">
        <h2>🔐 Iniciar sesión</h2>

        <?php if (isset($_GET['error'])): ?>
            <p style="color:red;">⚠️ <?= htmlspecialchars($_GET['error']) ?></p>
        <?php endif; ?>

        <form action="procesar_login.php" method="POST" style="display: inline-block;">
            <label for="username">Usuario:</label><br>
            <input type="text" id="username" name="username" required><br><br>

            <label for="password">Contraseña:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
