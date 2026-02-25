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

$sql = "
SELECT 
    t.id_innovacion AS id,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numero_oficio,
    t.fecha_solicitud,
    t.producto,
    t.impacto,
    t.puntaje AS puntaje_final,
    
    -- Concatenar detalles de profesores solo para el mismo id_innovacion
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
        ORDER BY ter.documento_tercero
        SEPARATOR '\n'
    ) AS `DETALLES_PROFESORES`
FROM 
    innovacion t
JOIN 
    innovacion_profesor tp ON tp.id_innovacion = t.id_innovacion
JOIN 
    tercero ter ON tp.id_profesor = ter.documento_tercero
JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID de la innovación
$sql .= " GROUP BY 
    t.id_innovacion, f.nombre_fac_min, d.depto_nom_propio, t.identificador, 
    t.numero_oficio, t.fecha_solicitud, t.producto, t.impacto, t.puntaje
ORDER BY 
    f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud
";
// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD',
    'PRODUCTO', 'IMPACTO', 'PUNTAJE FINAL', 'DETALLES DE PROFESORES'
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
$fileName = "Reporte_innovacion_" . date('Ymd') . ".xlsx";

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
