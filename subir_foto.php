<?php
include 'conexion.php';

$id_ruta = $_POST['id_ruta'];
$archivo = $_FILES['foto'];

if ($archivo && $id_ruta) {
    $nombre = uniqid() . '_' . basename($archivo['name']);
    $ruta = "uploads/" . $nombre;
    move_uploaded_file($archivo['tmp_name'], $ruta);

    $stmt = $conn->prepare("UPDATE rutas SET foto = ? WHERE id = ?");
    $stmt->bind_param("si", $ruta, $id_ruta);
    $stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
}
