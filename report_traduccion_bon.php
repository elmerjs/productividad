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
    t.id AS id,
    t.identificador AS identificador,
    t.numero_oficio AS numero_oficio,
    t.fecha_solicitud AS fecha_solicitud,
    t.producto AS producto,
    t.numero_profesores AS numero_profesores,
    t.puntaje AS puntaje,
    t.estado AS estado,
    t.tipo_productividad AS tipo_productividad,

    -- Facultad y Departamento
    f.nombre_fac_min AS facultad,
    d.depto_nom_propio AS departamento,

    -- Concatenar los detalles de los profesores
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
        ORDER BY ter.documento_tercero
        SEPARATOR '\\n'
    ) AS detalles_profesores

FROM 
    traduccion_bon t
JOIN 
    traduccion_bon_profesor tp ON tp.id_traduccion = t.id
JOIN 
    tercero ter ON tp.id_profesor = ter.documento_tercero
JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC

WHERE 1 = 1
";

// Añadir condiciones según los filtros

if (!empty($identificador)) {
    $sql .= " AND dt.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND dt.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

$sql .= " 
GROUP BY 
    t.id, t.identificador, t.numero_oficio, t.fecha_solicitud, t.producto, 
    t.numero_profesores, t.puntaje, t.estado, t.tipo_productividad, f.nombre_fac_min, d.depto_nom_propio

ORDER BY 
    t.fecha_solicitud;
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'IDENTIFICADOR', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD', 
    'PRODUCTO', 'NÚMERO DE PROFESORES', 'PUNTAJE', 'ESTADO', 
    'TIPO DE PRODUCTIVIDAD', 'FACULTAD', 'DEPARTAMENTO', 
    'DETALLES DE PROFESORES'
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
$fileName = "Reporte_traducc_bon_" . date('Ymd') . ".xlsx";

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
