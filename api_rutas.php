<?php
header("Content-Type: application/json");
include("conexion.php"); // tu archivo de conexión MySQL

// --- LÍNEA DE DEPURACIÓN INICIO ---
error_log("Recibida solicitud para api_rutas.php. GET params: " . print_r($_GET, true));
// --- LÍNEA DE DEPURACIÓN FIN ---

$response = ["success" => false, "rutas" => []];

if (isset($_GET['id_promotor'])) {
    $id = intval($_GET['id_promotor']);
    // Obtener la fecha actual en formato 'YYYY-MM-DD'
    $fecha_actual = date('Y-m-d'); // Esto obtiene la fecha de HOY en el servidor

    // Esta es la clave: el filtro por fecha_inicio igual a la fecha actual
    $query = "SELECT * FROM rutas WHERE id_promotor = $id AND DATE(fecha_inicio) = '$fecha_actual'";
    $result = mysqli_query($conn, $query);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response['rutas'][] = [
                    "id" => $row["id"],
                    "id_promotor" => $row["id_promotor"],
                    "id_pv" => $row["id_pv"],
                    "id_empresa" => $row["id_empresa"],
                    "ndia" => $row["ndia"],
                    "fecha_inicio" => $row["fecha_inicio"],
                    "horas" => $row["horas"],
                    "bolsa" => $row["bolsa"],
                    "nombre_promotor" => $row["nombre_promotor"],
                    "nombre_punto_venta" => $row["nombre_punto_venta"],
                    "nombre_empresa" => $row["nombre_empresa"],
                    "ciudad_pv" => $row["ciudad_pv"],
                    "departamento_pv" => $row["departamento_pv"],
                    "estado" => $row["estado"],
                    "codigo_carga" => $row["codigo_carga"],
                    "foto" => $row["foto"]
                ];
            }
            $response['success'] = true;
        } else {
            $response['mensaje'] = "No se encontraron rutas para hoy para este promotor.";
        }
    } else {
        $response['mensaje'] = "Error en la consulta: " . mysqli_error($conn);
    }
} else {
    $response['mensaje'] = "ID de promotor no proporcionado. Parámetros recibidos: " . json_encode($_GET);
}

echo json_encode($response);
?>





