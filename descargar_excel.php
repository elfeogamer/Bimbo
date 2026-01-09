<?php
session_start();
require "conexion.php";

// Cargar PhpSpreadsheet
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear documento
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ENCABEZADOS
$sheet->setCellValue('A1', 'Item');
$sheet->setCellValue('B1', 'Producto');
$sheet->setCellValue('C1', 'Cantidad');
$sheet->setCellValue('D1', 'Clave');
$sheet->setCellValue('E1', 'Fecha subida');

// Obtener datos
$sql = "SELECT item, producto, cantidad, clave, fecha_subida FROM existencias";
$res = $conexion->query($sql);

$fila = 2;

while ($row = $res->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['item']);
    $sheet->setCellValue('B' . $fila, $row['producto']);
    $sheet->setCellValue('C' . $fila, $row['cantidad']);
    $sheet->setCellValue('D' . $fila, $row['clave']);
    $sheet->setCellValue('E' . $fila, $row['fecha_subida']);
    $fila++;
}

// Nombre del archivo
$nombreArchivo = "inventario_" . date("Y-m-d_His") . ".xlsx";

// Forzar descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
