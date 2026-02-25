<?php
// Requerir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los filtros desde el modal (si existen)
//$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
//$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

$identificador_solicitud = isset($_GET['identificador']) ? $_GET['identificador'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;


// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    tc.identificador AS `IDENTIFICADOR`,
    tc.numero_oficio AS `NUMERO OFICIO`,
    tc.producto AS `PRODUCTO`,
    tc.difusion AS `DIFUSION`,
    tc.finalidad AS `FINALIDAD`,
    tc.area AS `AREA`,
    GROUP_CONCAT(p.nombre_completo ORDER BY p.nombre_completo SEPARATOR '; ') AS `PROFESORES`,
    tc.evaluador1 AS `EVALUADOR 1`,
    tc.evaluador2 AS `EVALUADOR 2`,
    tc.puntaje AS `PUNTAJE`,
    DATE_FORMAT(tc.fecha_solicitud_tr, '%Y-%m-%d %H:%i:%s') AS `FECHA SOLICITUD`,
    tc.tipo_productividad AS `TIPO DE PRODUCTIVIDAD`
FROM 
    trabajos_cientificos tc
JOIN 
    trabajo_profesor tp ON tc.id = tp.id_trabajo_cientifico
LEFT JOIN 
    tercero p ON tp.profesor_id = p.documento_tercero
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND tc.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND tc.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID del trabajo científico
$sql .= " GROUP BY tc.id";
// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'IDENTIFICADOR',
    'NUMERO OFICIO',
    'PRODUCTO',
    'DIFUSION',
    'FINALIDAD',
    'AREA',
    'PROFESORES',
    'EVALUADOR 1',
    'EVALUADOR 2',
    'PUNTAJE',
    'FECHA SOLICITUD',
    'TIPO DE PRODUCTIVIDAD'
];
$sheet->fromArray($headers, NULL, 'A1');

// Rellenar los datos en la hoja de Excel
if ($result->num_rows > 0) {
    $row = 2; // Iniciar en la segunda fila después de los encabezados
    while ($data = $result->fetch_assoc()) {
        $sheet->fromArray(array_values($data), NULL, 'A' . $row);
        $row++;
    }
}

// Nombre del archivo Excel
$fileName = "Reporte_trabajo_cient_" . date('Ymd') . ".xlsx";

// Enviar el archivo como descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Crear el archivo Excel y enviarlo al navegador
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Cerrar la conexión a la base de datos
$conn->close();
exit;
?>
