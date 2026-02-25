<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador']) ? $_GET['cuadro_identificador'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener los premios
$sql = "
SELECT 
    p.id,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    p.nombre_evento AS `EVENTO_PREMIO`,
    p.ambito AS `AMBITO`,
    p.categoria_premio AS `CATEGORIA_PREMIO`,
    p.nivel_ganado AS `NIVEL_GANADO`,
    p.lugar_fecha AS `LUGAR_Y_FECHA`,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`,
    p.numero_oficio AS `OFICIO`
FROM 
    premios p 
JOIN 
    premios_profesor pp ON pp.id_premio = p.id
JOIN 
    tercero t ON pp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 
     (p.estado is null or p.estado <> 'an')
";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND p.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(p.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY 
    p.id,  p.nombre_evento, p.ambito, p.categoria_premio, p.nivel_ganado, p.lugar_fecha, p.numero_oficio
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
$section->addText("CUADROS DE PREMIOS POR FACULTAD Y DEPARTAMENTO", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

    foreach ($departamentos as $departamento => $premios) {
        // Agregar el nombre del departamento
          $section->addText("DEPARTAMENTO: " . mb_strtoupper($departamento, 'UTF-8'), ['bold' => true, 'size' => 11]);

        // Imprimir la tabla para cada premio
        foreach ($premios as $data) {
            // Añadir los detalles de los profesores
    $profesores = explode("\n", $data['DETALLES PROFESORES']); // Separar los nombres de los profesores por salto de línea

    foreach ($profesores as $profesor) {
        $section->addText("{$profesor}", ['size' => 10]); // Agregar cada profesor en una línea
        $section->addTextBreak(0); // Salto de línea después de cada profesor
    }
            
            
            
        // Crear la tabla
            $table = $section->addTable('CustomTableStyle');
            $table->setWidth('50%');

            // Encabezados de la tabla
            $row = $table->addRow();
         $row->addCell(1500, $headerCellStyle)->addText("Campo", $cellTextStyleHeader, $paragraphStyle);
            $row->addCell(6000, $headerCellStyle)->addText("Detalle", $cellTextStyleHeader, $paragraphStyle);

            // Agregar datos a la tabla
            $table->addRow();
            $table->addCell(1500)->addText("EVENTO_PREMIO", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['EVENTO_PREMIO'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("AMBITO", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['AMBITO'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("CATEGORIA_PREMIO", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['CATEGORIA_PREMIO'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("NIVEL_GANADO", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['NIVEL_GANADO'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("LUGAR_Y_FECHA", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['LUGAR_Y_FECHA'], $cellTextStyle, $paragraphStyle);

            $table->addRow();
            $table->addCell(1500)->addText("OFICIO", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['OFICIO'], $cellTextStyle, $paragraphStyle);
            // Agregar una separación entre registros
            $section->addTextBreak(1);
        }
    }
}

// Nombre del archivo Word
$fileName = "Cuadros_Premios_".$identificador_solicitud.".docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
