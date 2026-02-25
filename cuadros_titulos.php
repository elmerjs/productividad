<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener los datos de títulos con facultad y departamento
$sql = "
SELECT 
    t.id_titulo,
    t.identificador,
    t.numero_oficio,
    t.documento_profesor,
    t.titulo_obtenido,
    t.tipo,
    t.tipo_estudio,
    t.institucion,
    t.fecha_terminacion,
    t.resolucion_convalidacion,
    t.puntaje,
    t.tipo_productividad,
    f.nombre_fac_min as NOMBREF_FAC,
    d.depto_nom_propio as NOMBRE_DEPTO,
    p.nombre_completo
FROM 
    titulos t
JOIN 
    titulo_profesor tp ON tp.id_titulo = t.id_titulo
JOIN 
    tercero p ON tp.fk_tercero = p.documento_tercero
JOIN 
    deparmanentos d ON p.fk_depto = d.pk_depto
JOIN 
    facultad f ON f.PK_FAC = d.FK_FAC
    WHERE (t.estado_titulo is null or t.estado_titulo <> 'an')
";


// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
$sql .= " AND LEFT(t.IDENTIFICADOR, 4) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " ORDER BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, t.fecha_terminacion";

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
$section->addText("INFORME DE TÍTULOS OBTENIDOS", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);

// Variables para controlar agrupamiento
$currentFacultad = '';
$currentDepartamento = '';

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

// Imprimir los datos de títulos
if ($result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        // Si cambia la facultad, añadir un encabezado de facultad
        if ($currentFacultad !== $data['NOMBREF_FAC']) {
            $currentFacultad = $data['NOMBREF_FAC'];
            $section->addText(mb_strtoupper($currentFacultad, 'UTF-8'), ['bold' => true, 'size' => 12]);

        }

        // Si cambia el departamento, añadir un encabezado de departamento
        if ($currentDepartamento !== $data['NOMBRE_DEPTO']) {
            $currentDepartamento = $data['NOMBRE_DEPTO'];
            $section->addText("DEPARTAMENTO: " . mb_strtoupper($currentDepartamento, 'UTF-8'), ['bold' => true, 'size' => 11]);

        }

        // Añadir los detalles del profesor
        $section->addText("Profesor: " . $data['nombre_completo'], ['bold' => true, 'size' => 10]);

        // Crear la tabla
        $table = $section->addTable('CustomTableStyle');
        $table->setWidth('50%');

        // Encabezados de la tabla
        $row = $table->addRow();
        $row->addCell(1500, $headerCellStyle)->addText("Campo", $cellTextStyleHeader, $paragraphStyle);
        $row->addCell(6000, $headerCellStyle)->addText("Detalle", $cellTextStyleHeader, $paragraphStyle);

        // Agregar datos a la tabla
        $table->addRow();
        $table->addCell(1500)->addText("Identificación", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['documento_profesor'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Número de Oficio", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['numero_oficio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Título Obtenido", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['titulo_obtenido'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Tipo", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['tipo'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Tipo de Estudio", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['tipo_estudio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Institución", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['institucion'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Fecha de Graduación", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['fecha_terminacion'], $cellTextStyle, $paragraphStyle);

        if ($data['tipo']<> 'NACIONAL') {
            $table->addRow();
            $table->addCell(1500)->addText("Resolución de Convalidación", $cellTextStyle, $paragraphStyle);
            $table->addCell(6000)->addText($data['resolucion_convalidacion'] ?? 'N/A', $cellTextStyle, $paragraphStyle);
        }

        $table->addRow();
        $table->addCell(1500)->addText("Puntaje", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['puntaje'], $cellTextStyle, $paragraphStyle);

       
        

        // Agregar una separación entre registros
        $section->addTextBreak(1);
    }
}

// Nombre del archivo Word
$fileName = "Cuadros_Titulos_Obtenidos{$identificador_solicitud}.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
