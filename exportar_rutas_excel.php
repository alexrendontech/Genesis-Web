<?php
require 'vendor/autoload.php';
include 'conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$headers = [
    'ID PROMOTOR', 'ID PV', 'ID EMPRESA', 'NDIA', 'FECHA INICIO DE CICLO', 'HORAS', 'BOLSA',
    'NOMBRE PROMOTOR', 'NOMBRE PUNTO DE VENTA', 'NOMBRE EMPRESA', 'CIUDAD PV',
    'DEPARTAMENTO PV', 'ESTADO', 'CODIGO DE CARGA'
];
$sheet->fromArray($headers, null, 'A1');

// Datos
$sql = "SELECT * FROM rutas ORDER BY codigo_carga DESC, id_promotor ASC, fecha_inicio ASC";
$result = mysqli_query($conn, $sql);
$rowNum = 2;

while ($row = mysqli_fetch_assoc($result)) {
    $sheet->fromArray([
        $row['id_promotor'],
        $row['id_pv'],
        $row['id_empresa'],
        $row['ndia'],
        $row['fecha_inicio'],
        $row['horas'],
        $row['bolsa'],
        $row['nombre_promotor'],
        $row['nombre_punto_venta'],
        $row['nombre_empresa'],
        $row['ciudad_pv'],
        $row['departamento_pv'],
        $row['estado'],
        $row['codigo_carga'],
    ], null, "A$rowNum");
    $rowNum++;
}

// Descargar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="rutas_exportadas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
