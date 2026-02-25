<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL con la información de facultades y departamentos
$sql = "
SELECT 
    tc.numero_oficio AS `NUMERO DE OFICIO`,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    tc.producto AS `PRODUCTO`,
    tc.difusion AS `DIFUSION`,
    tc.finalidad AS `FINALIDAD`,
    tc.area AS `AREA`,
CONCAT('(', tc.evaluador1, ')', '+', '(', tc.evaluador2, ')/2', '=', FORMAT((tc.evaluador1 + tc.evaluador2)/2, 2), '%') AS `EVALUACIONES`,
    tc.puntaje AS `PUNTAJE FINAL`,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`
FROM 
    trabajos_cientificos tc
JOIN 
    trabajo_profesor tp ON tc.id = tp.id_trabajo_cientifico
JOIN 
    tercero t ON tp.profesor_id = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 
    (tc.estado_cient is null or tc.estado_cient <> 'an')
";



// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND tc.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(tc.fecha_solicitud_tr) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY 
    tc.id
ORDER BY 
    f.nombre_fac_min, d.depto_nom_propio";


// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Word
$phpWord = new PhpWord();
$section = $phpWord->addSection([
      'marginLeft'   => 1701,  // 3 cm margen izquierdo
    'marginRight'  => 1701,  // 3 cm margen derecho
    'marginTop' => 1000,   // 2 cm margen superior
    'marginBottom' => 1000 // 2 cm margen inferior
]);

// Añadir el título solo una vez
$section->addText("TRABAJOS CIENTÍFICOS POR FACULTAD Y DEPARTAMENTO", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);

// Definir estilos de texto y de tabla
$paragraphStyle = ['spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0];
$cellTextStyle = ['size' => 9, 'name' => 'Arial'];
$cellTextStyleHeader = ['bold' => true, 'size' => 10, 'name' => 'Arial'];
$headerCellStyle = ['bgColor' => '#f2f2f2', 'valign' => 'center'];
$styleTable = [
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 60,
];

$phpWord->addTableStyle('CustomTableStyle', $styleTable);

$dataGrouped = [];

// Agrupar datos por facultad y departamento
if ($result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        $dataGrouped[$data['FACULTAD']][$data['DEPARTAMENTO']][] = $data;
    }
}

// Imprimir datos por facultad y departamento
foreach ($dataGrouped as $facultad => $departamentos) {
    // Agregar el nombre de la facultad
    $section->addText(mb_strtoupper($facultad, 'UTF-8'), ['bold' => true, 'size' => 12]);


    foreach ($departamentos as $departamento => $trabajos) {
        // Agregar el nombre del departamento
       $section->addText("DEPARTAMENTO: " . mb_strtoupper($departamento, 'UTF-8'), ['bold' => true, 'size' => 11]);


        // Imprimir los detalles de los profesores fuera de la tabla
$detallesProfesores = explode("\n", $trabajos[0]['DETALLES PROFESORES']);

// Agregar los detalles de los profesores, línea por línea, debajo del nombre del departamento
foreach ($detallesProfesores as $detalle) {
    $section->addText($detalle, $cellTextStyle, $paragraphStyle);
}        $section->addTextBreak(0);

        // Imprimir la tabla para cada trabajo científico
        foreach ($trabajos as $data) {
            // Crear la tabla
            $table = $section->addTable('CustomTableStyle');
            $table->setWidth('50%');

            // Encabezados de la tabla
            $row = $table->addRow();
            $row->addCell(1500, $headerCellStyle)->addText("Campo", $cellTextStyleHeader, $paragraphStyle);
            $row->addCell(6000, $headerCellStyle)->addText("Detalle", $cellTextStyleHeader, $paragraphStyle);

            // Agregar datos a la tabla
            $table->addRow();
            $table->addCell(1500)->addText("NUMERO DE OFICIO", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['NUMERO DE OFICIO'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("PRODUCTO", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['PRODUCTO'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("DIFUSION", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['DIFUSION'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("FINALIDAD", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['FINALIDAD'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("AREA", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['AREA'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("EVALUACIONES", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['EVALUACIONES'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("PUNTAJE FINAL", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['PUNTAJE FINAL'], $cellTextStyle, $paragraphStyle);

            // Agregar una separación entre registros
            $section->addTextBreak(1);
        }
    }
}

// Nombre del archivo Word
$fileName = "Cuadros_Trabajos_Cientificos_{$identificador_solicitud}.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
