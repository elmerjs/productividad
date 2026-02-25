<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
// Obtener los filtros desde el modal (si existen)
$identificador_libro = isset($_GET['cuadro_identificador_libro']) ? $_GET['cuadro_identificador_libro'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    l.tipo_libro as TIPO_LIBRO,
    l.numero_oficio AS `NUMERO DE OFICIO`,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    l.producto AS `LIBRO`,
    l.isbn AS `ISBN`,
    l.nombre_editorial AS `EDITORIAL`,
    l.mes_ano_edicion AS `AÑO`,
    l.tiraje AS `EJEMPLARES`,
    l.autores AS `AUTORES`,
    CONCAT(l.evaluacion_1, ' + ', l.evaluacion_2, ' = ', (l.evaluacion_1 + l.evaluacion_2), ' / 2 = ', ROUND((l.evaluacion_1 + l.evaluacion_2) / 2, 2)) AS `EVALUACIONES`,
    l.puntaje_final AS `PUNTAJE FINAL`,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`
FROM 
    libros l
JOIN 
    libro_profesor lp ON l.id_libro = lp.id_libro
JOIN 
    tercero t ON lp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE (l.estado is null or l.estado <> 'an')";

// Añadir condiciones según los filtros
if (!empty($identificador_libro)) {
    $sql .= " AND l.identificador = '" . $conn->real_escape_string($identificador_libro) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(l.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY l.id_libro ORDER BY f.nombre_fac_min, d.depto_nom_propio";

// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Word
$phpWord = new PhpWord();
$section = $phpWord->addSection(array(
   'marginLeft'   => 1701,  // 3 cm margen izquierdo
    'marginRight'  => 1701,  // 3 cm margen derecho
    'marginTop' => 1000,   // 2 cm margen superior
    'marginBottom' => 1000 // 2 cm margen inferior
));

// Añadir el título solo una vez
$section->addText("LIBROS", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
$section->addTextBreak(0);

// Definir estilos de texto y de tabla
$paragraphStyle = array('spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0);
$cellTextStyle = array('size' => 9, 'name' => 'Arial');
$cellTextStyleHeader = array('bold' => true, 'size' => 10, 'name' => 'Arial');
$headerCellStyle = array('bgColor' => '#f2f2f2', 'valign' => 'center');
$styleTable = array(
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 60,
);

$phpWord->addTableStyle('CustomTableStyle', $styleTable);

$dataGrouped = [];

// Agrupar datos por facultades y departamentos
if ($result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        $dataGrouped[$data['FACULTAD']][$data['DEPARTAMENTO']][] = $data;
    }
}

// Imprimir datos por facultad y departamento
foreach ($dataGrouped as $facultad => $departamentos) {
    // Agregar la facultad
    $section->addText(mb_strtoupper($facultad, 'UTF-8'), ['bold' => true, 'size' => 12]);

    foreach ($departamentos as $departamento => $libros) {
        // Agregar el departamento
        $section->addText("DEPARTAMENTO: " . mb_strtoupper($departamento, 'UTF-8'), ['bold' => true, 'size' => 11]);

        // Imprimir la tabla para cada departamento
        foreach ($libros as $data) {
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
$table->addCell(1500)->addText("No. OFICIO", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['NUMERO DE OFICIO'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("LIBRO DE ".$data['TIPO_LIBRO'], $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['LIBRO'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("ISBN", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['ISBN'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("EDITORIAL", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['EDITORIAL'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("AÑO", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['AÑO'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("EJEMPLARES", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['EJEMPLARES'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("AUTORES", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['AUTORES'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("EVALUACIONES", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText($data['EVALUACIONES'], $cellTextStyle, $paragraphStyle);

$table->addRow();
$table->addCell(1500)->addText("ASIGNAR", $cellTextStyle, $paragraphStyle);
$table->addCell(6000)->addText("{$data['PUNTAJE FINAL']} /puntos", $cellTextStyle, $paragraphStyle);    
            // Agregar una separación entre registros
            $section->addTextBreak(1);
        }
    }
}

// Nombre del archivo Word
$fileName = "Reporte_Libros_{$identificador_libro}.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
