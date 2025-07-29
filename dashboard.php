<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard por Categorías</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f0f2f5; /* Un gris muy suave para el fondo */
            color: #333;
            line-height: 1.6;
        }
        .dashboard-container {
            max-width: 1000px; /* Un poco más ancho para las categorías */
            margin: 0 auto;
            padding: 25px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); /* Sombra un poco más pronunciada */
        }
        .welcome-section {
            background-color: #e9f0f7; /* Fondo suave para la sección de bienvenida */
            border-bottom: 1px solid #d0e0f0;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 6px;
            text-align: center;
        }
        .welcome-section h2 {
            margin-top: 0;
            color: #0056b3; /* Azul para el título */
            font-size: 1.8em;
        }
        .welcome-section p {
            color: #555;
            font-size: 1.1em;
        }

        .category-section {
            margin-bottom: 30px; /* Espacio entre categorías */
            border: 1px solid #e0e0e0; /* Borde ligero para la sección de categoría */
            border-radius: 6px;
            background-color: #ffffff; /* Fondo blanco para la caja de categoría */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); /* Sombra sutil para la categoría */
        }
        .category-section h3 {
            color: #0056b3; /* Título de categoría azul */
            border-bottom: 2px solid #007bff; /* Subrayado para el título */
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.4em;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Más flexible */
            gap: 20px; /* Espacio entre los cards */
        }
        .dashboard-card {
            background-color: #f8f9fa; /* Fondo muy claro para los cards */
            border: 1px solid #dee2e6; /* Borde ligero */
            padding: 18px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08); /* Sombra más sutil */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex; /* Para centrar contenido verticalmente si es necesario */
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 120px; /* Altura mínima para que los cards se vean uniformes */
        }
        .dashboard-card:hover {
            transform: translateY(-5px); /* Se levanta más al pasar el mouse */
            box-shadow: 0 6px 15px rgba(0,0,0,0.15); /* Sombra más fuerte al pasar el mouse */
        }
        .dashboard-card a {
            text-decoration: none;
            color: #333;
            display: flex; /* Para el contenido interno del link */
            flex-direction: column;
            width: 100%;
            height: 100%;
            justify-content: center;
            align-items: center;
        }
        .dashboard-card a:hover {
            color: #007bff;
        }
        .dashboard-card h4 { /* Cambiado a h4 para el título del card */
            margin-top: 10px;
            margin-bottom: 5px;
            font-size: 1.1em;
            color: #333;
        }
        .dashboard-card p {
            font-size: 0.85em;
            color: #777;
            margin-bottom: 0;
            line-height: 1.4;
        }
        .card-icon {
            font-size: 2.8em; /* Iconos un poco más grandes */
            margin-bottom: 8px;
            color: #6a1b9a; /* Un morado más profundo */
        }

        .logout-section {
            text-align: center;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }
        .logout-link {
            display: inline-block;
            padding: 12px 25px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .logout-link:hover {
            background-color: #c82333;
            transform: scale(1.02);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }
            .grid-container {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            .dashboard-card {
                min-height: 100px;
                padding: 15px;
            }
            .card-icon {
                font-size: 2.2em;
            }
            .dashboard-card h4 {
                font-size: 1em;
            }
            .dashboard-card p {
                font-size: 0.8em;
            }
            .category-section h3 {
                font-size: 1.2em;
            }
        }

        /* NUEVOS ESTILOS PARA EL SWITCH */
        .admin-settings-section {
            margin-top: 40px;
            margin-bottom: 30px;
            padding: 25px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .admin-settings-section h3 {
            color: #0056b3;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.4em;
        }
        .switch-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background-color: #e9f0f7; /* Un color suave para el fondo del switch */
            border-radius: 6px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        .switch {
          position: relative;
          display: inline-block;
          width: 60px;
          height: 34px;
        }
        .switch input {
          opacity: 0;
          width: 0;
          height: 0;
        }
        .slider {
          position: absolute;
          cursor: pointer;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: #ccc;
          -webkit-transition: .4s;
          transition: .4s;
          border-radius: 34px;
        }
        .slider:before {
          position: absolute;
          content: "";
          height: 26px;
          width: 26px;
          left: 4px;
          bottom: 4px;
          background-color: white;
          -webkit-transition: .4s;
          transition: .4s;
          border-radius: 50%;
        }
        input:checked + .slider {
          background-color: #28a745; /* Verde para activado */
        }
        input:focus + .slider {
          box-shadow: 0 0 1px #28a745;
        }
        input:checked + .slider:before {
          -webkit-transform: translateX(26px);
          -ms-transform: translateX(26px);
          transform: translateX(26px);
        }
        /* Rounded sliders */
        .slider.round {
          border-radius: 34px;
        }
        .slider.round:before {
          border-radius: 50%;
        }
        #restrictionStatusText {
            font-weight: bold;
            color: #555;
            min-width: 180px; /* Asegura espacio para el texto */
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="welcome-section">
            <h2>¡Bienvenido a tu panel, <?php echo htmlspecialchars($nombre); ?>!</h2>
            <p>Tu rol es: <strong><?php echo htmlspecialchars($rol); ?></strong></p>
        </div>

        <?php if ($rol === 'administrador'): ?>
            <div class="category-section">
                <h3>📂 Carga y Gestión de Archivos</h3>
                <div class="grid-container">
                    <a href='subir_rutas.php' class="dashboard-card">
                        <div class="card-icon">📥</div>
                        <h4>Subir Rutas</h4>
                        <p>Carga nuevos recorridos vía archivo Excel.</p>
                    </a>
                    <a href='subir_edicion_rutas.php' class="dashboard-card">
                        <div class="card-icon">✏️</div>
                        <h4>Editar Rutas</h4>
                        <p>Modifica rutas existentes por código de carga.</p>
                    </a>
                    <a href='subir_puntos_venta.php' class="dashboard-card">
                        <div class="card-icon">🏪</div>
                        <h4>Subir Puntos de Venta</h4>
                        <p>Importa nuevos puntos de interés masivamente.</p>
                    </a>
                </div>
            </div>

            <div class="category-section">
                <h3>👥 Gestión de Usuarios y Clientes</h3>
                <div class="grid-container">
                    <a href='crear_usuario.php' class="dashboard-card">
                        <div class="card-icon">➕</div>
                        <h4>Crear Usuario</h4>
                        <p>Añade nuevos usuarios al sistema.</p>
                    </a>
                    <a href='listar_usuarios.php' class="dashboard-card">
                        <div class="card-icon">👤</div>
                        <h4>Ver Usuarios</h4>
                        <p>Gestiona usuarios (admin, supervisor, móvil).</p>
                    </a>
                    <a href='listar_clientes.php' class="dashboard-card">
                        <div class="card-icon">🏢</div>
                        <h4>Ver Clientes</h4>
                        <p>Consulta la lista de clientes registrados.</p>
                    </a>
                </div>
            </div>

            <div class="category-section">
                <h3>📊 Visualización de Datos y Reportes</h3>
                <div class="grid-container">
                    <a href='ver_puntos_venta.php' class="dashboard-card">
                        <div class="card-icon">📍</div>
                        <h4>Ver Puntos de Venta</h4>
                        <p>Visualiza el mapa y detalles de los puntos.</p>
                    </a>
                    <a href='ver_rutas.php' class="dashboard-card">
                        <div class="card-icon">📦</div>
                        <h4>Ver Rutas</h4>
                        <p>Revisa las rutas cargadas y su estado.</p>
                    </a>
                    <a href='encuestas.php' class="dashboard-card">
                        <div class="card-icon">📋</div>
                        <h4>Encuestas</h4>
                        <p>Administra y revisa los resultados de encuestas.</p>
                    </a>
                    <a href='ver_reporte.php' class="dashboard-card">
                        <div class="card-icon">📈</div>
                        <h4>Ver Reportes</h4>
                        <p>Accede a los informes detallados y análisis.</p>
                    </a>
                </div>
            </div>

            <!-- NUEVA SECCIÓN: Configuración de la Aplicación Móvil (Solo para Administrador) -->
            <div class="admin-settings-section">
                <h3>⚙️ Configuración de la Aplicación Móvil</h3>
                <div class="switch-container">
                    <label style="margin-right: 10px;">Restricción de fecha para fotos de galería:</label>
                    <label class="switch">
                        <input type="checkbox" id="dateRestrictionToggle">
                        <span class="slider round"></span>
                    </label>
                    <span id="restrictionStatusText">Cargando...</span>
                </div>
                <p style="text-align: center; font-size: 0.9em; color: #777; margin-top: 15px;">
                    Esta opción controla si los usuarios de la aplicación móvil pueden subir fotos de la galería tomadas en días anteriores a la visita actual.
                </p>
            </div>
            <!-- FIN NUEVA SECCIÓN -->

        <?php endif; ?>

        <?php if ($rol === 'supervisor'): ?>
            <div class="category-section">
                <h3>📊 Visualización de Reportes</h3>
                <div class="grid-container">
                    <a href='ver_reporte.php' class="dashboard-card">
                        <div class="card-icon">📊</div>
                        <h4>Ver Reportes</h4>
                        <p>Consulta los informes de tu equipo.</p>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($rol === 'cliente'): ?>
            <div class="category-section">
                <h3>📈 Mis Reportes</h3>
                <div class="grid-container">
                    <a href='ver_reporte.php' class="dashboard-card">
                        <div class="card-icon">📈</div>
                        <h4>Ver Mis Reportes</h4>
                        <p>Accede a los reportes relacionados con tu actividad.</p>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="logout-section">
            <a href='logout.php' class="logout-link">🚪 Cerrar sesión</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Solo ejecutar la lógica del switch si el usuario es administrador
            <?php if ($rol === 'administrador'): ?>
                const toggle = document.getElementById('dateRestrictionToggle');
                const statusText = document.getElementById('restrictionStatusText');

                // Función para cargar el estado actual de la configuración
                function loadSetting() {
                    statusText.textContent = 'Cargando configuración...';
                    fetch('get_app_setting.php') // Asegúrate de que esta ruta sea correcta
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error de red: ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                toggle.checked = data.is_date_restriction_enabled;
                                statusText.textContent = data.is_date_restriction_enabled ? 'Restricción HABILITADA' : 'Restricción DESHABILITADA';
                            } else {
                                statusText.textContent = 'Error al cargar: ' + (data.message || 'Mensaje desconocido');
                                console.error('Error al cargar configuración:', data.message);
                            }
                        })
                        .catch(error => {
                            statusText.textContent = 'Error de red al cargar.';
                            console.error('Error de red:', error);
                        });
                }

                // Función para guardar el nuevo estado de la configuración
                toggle.addEventListener('change', function() {
                    const newValue = this.checked ? '1' : '0'; // '1' para habilitado, '0' para deshabilitado
                    statusText.textContent = 'Guardando configuración...';

                    fetch('set_app_setting.php', { // Asegúrate de que esta ruta sea correcta
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            setting_key: 'date_restriction_enabled',
                            setting_value: newValue
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error de red: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            statusText.textContent = this.checked ? 'Restricción HABILITADA' : 'Restricción DESHABILITADA';
                            alert('Configuración guardada: ' + (data.message || 'Éxito.'));
                        } else {
                            statusText.textContent = 'Error al guardar: ' + (data.message || 'Mensaje desconocido');
                            alert('Error al guardar configuración: ' + (data.message || 'Error desconocido.'));
                            // Revertir el estado del toggle si falla la operación en el servidor
                            this.checked = !this.checked;
                        }
                    })
                    .catch(error => {
                        statusText.textContent = 'Error de red al guardar.';
                        alert('Error de red al guardar configuración.');
                        this.checked = !this.checked; // Revertir el estado del toggle
                        console.error('Error de red:', error);
                    });
                });

                // Cargar la configuración al cargar la página
                loadSetting();
            <?php endif; ?>
        });
    </script>
</body>
</html>

