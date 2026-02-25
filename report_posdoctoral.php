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
    p.id AS id,
    p.identificador AS identificador,
    p.numero_oficio AS numero_oficio,
    p.documento_profesor AS documento_profesor,
    p.titulo_obtenido AS titulo_obtenido,
    p.institucion AS institucion,
    p.fecha_terminacion AS fecha_terminacion,
    p.puntaje AS puntaje,
    p.tipo_productividad AS tipo_productividad,
    
    -- Facultad y Departamento
    f.nombre_fac_min AS facultad,
    d.depto_nom_propio AS departamento,
    
    -- Concatenar los detalles de los profesores
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
        ORDER BY ter.documento_tercero
        SEPARATOR '\n'
    ) AS detalles_profesores

FROM 
    posdoctoral p
JOIN 
    posdoctoral_profesor pp ON pp.id_titulo = p.id
JOIN 
    tercero ter ON pp.fk_tercero = ter.documento_tercero
JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC

WHERE 1 = 1

";
// Añadir condiciones según los filtros
if (!empty($identificador_completo)) {
    $sql .= " AND cb.identificador = '" . $conn->real_escape_string($identificador_completo) . "'";
}
if (!empty($numeroOficio)) {
    $sql .= " AND cb.numero_oficio = '" . $conn->real_escape_string($numeroOficio) . "'";
}
$sql .= " 
 
GROUP BY 
    p.id, p.identificador, p.numero_oficio, p.documento_profesor, 
    p.titulo_obtenido, p.institucion, p.fecha_terminacion, 
    p.puntaje, p.tipo_productividad, f.nombre_fac_min, d.depto_nom_propio

ORDER BY 
    p.fecha_terminacion;
   
";

    // Ejecutar la consulta
// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'IDENTIFICADOR', 'NÚMERO DE OFICIO', 'CÉDULA DE IDENTIDAD', 
    'TÍTULO OBTENIDO', 'INSTITUCIÓN', 'FECHA DE TERMINACIÓN', 
    'PUNTAJE', 'TIPO DE PRODUCTIVIDAD', 'FACULTAD', 'DEPARTAMENTO', 'DETALLES DE PROFESORES'
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
$fileName = "Reporte_posdoctoral_" . date('Ymd') . ".xlsx";

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
