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

   // Consulta SQL
 $sql = "
    SELECT 
        pb.id AS id,
        f.nombre_fac_min AS `FACULTAD`,
        d.depto_nom_propio AS `DEPARTAMENTO`,
        pb.identificador_completo,
        pb.numeroOficio,
        pb.fecha_solicitud,
        pb.difusion,
        pb.producto,
        pb.nombre_evento,
        pb.fecha_evento,
        pb.lugar_evento,
        pb.autores,
        pb.evaluacion1,
        pb.evaluacion2,
        pb.puntaje,
        pb.puntaje_final,
        pb.tipo_productividad,
        
        -- Concatenar los detalles de los profesores para cada ponencia
        GROUP_CONCAT(
            DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
            ORDER BY ter.documento_tercero
            SEPARATOR '\n'
        ) AS DETALLES_PROFESORES
    FROM 
        ponencias_bon pb
    JOIN 
        ponencias_bon_profesor pbp ON pbp.id_ponencias_bon = pb.id
    JOIN 
        tercero ter ON pbp.documento_profesor = ter.documento_tercero
    JOIN 
        deparmanentos d ON ter.fk_depto = d.PK_DEPTO
    JOIN 
        facultad f ON d.FK_FAC = f.PK_FAC
    WHERE 
        1 = 1
   
";
// Añadir condiciones según los filtros
if (!empty($identificador_completo)) {
    $sql .= " AND cb.identificador_completo = '" . $conn->real_escape_string($identificador_completo) . "'";
}
if (!empty($numeroOficio)) {
    $sql .= " AND cb.numeroOficio = '" . $conn->real_escape_string($numeroOficio) . "'";
}
$sql .= " 
   GROUP BY 
        pb.id, f.nombre_fac_min, d.depto_nom_propio, pb.identificador_completo, 
        pb.numeroOficio, pb.fecha_solicitud, pb.difusion, pb.producto, 
        pb.nombre_evento, pb.fecha_evento, pb.lugar_evento, pb.autores, 
        pb.evaluacion1, pb.evaluacion2, pb.puntaje, pb.puntaje_final, 
        pb.tipo_productividad
    ORDER BY 
        pb.fecha_solicitud;
";

    // Ejecutar la consulta
// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'FACULTAD', 'DEPARTAMENTO', 'IDENTIFICADOR COMPLETO', 'NÚMERO DE OFICIO', 
    'FECHA DE SOLICITUD', 'DIFUSIÓN', 'PRODUCTO', 'NOMBRE DEL EVENTO', 'FECHA DEL EVENTO', 
    'LUGAR DEL EVENTO', 'AUTORES', 'EVALUACIÓN 1', 'EVALUACIÓN 2', 'PUNTAJE', 
    'PUNTAJE FINAL', 'TIPO DE PRODUCTIVIDAD', 'DETALLES DE PROFESORES'
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
$fileName = "Reporte_ponencias_" . date('Ymd') . ".xlsx";

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
