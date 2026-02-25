<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener los datos de innovación tecnológica con facultad y departamento
$sql = "
SELECT 
    i.id_innovacion,
    i.identificador,
    i.numero_oficio,
    i.fecha_solicitud,
    i.producto,
    i.impacto,
    i.numero_profesores,
    i.puntaje,
    i.estado,
    i.tipo_productividad,
    f.nombre_fac_min as NOMBREF_FAC,
    d.depto_nom_propio as NOMBRE_DEPTO,
    GROUP_CONCAT(DISTINCT CONCAT(p.nombre_completo, ' - ', p.documento_tercero) ORDER BY p.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    innovacion i
JOIN 
    innovacion_profesor ip ON ip.id_innovacion = i.id_innovacion
JOIN 
    tercero p ON ip.id_profesor = p.documento_tercero
JOIN 
    deparmanentos d ON p.fk_depto = d.pk_depto
JOIN 
    facultad f ON f.PK_FAC = d.FK_FAC
        WHERE (i.estado is null or i.estado <> 'an')

";


// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND i.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(i.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, i.id_innovacion, i.identificador, i.numero_oficio, i.fecha_solicitud, i.producto, i.impacto, i.numero_profesores, i.puntaje, i.estado, i.tipo_productividad
ORDER BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, i.fecha_solicitud";

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
$section->addText("INFORME DE INNOVACIÓN TECNOLÓGICA", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

// Imprimir los datos de innovación tecnológica
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

        // Añadir los detalles de los profesores
        $section->addText("Profesores:", ['bold' => true, 'size' => 10]);
        $profesores = explode("\n", $data['DETALLES_PROFESORES']);
        foreach ($profesores as $profesor) {
            $section->addText($profesor, ['size' => 10]);
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
        $table->addCell(1500)->addText("Identificador", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['identificador'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Número de Oficio", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['numero_oficio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Fecha de Solicitud", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['fecha_solicitud'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['producto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Impacto", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['impacto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Número de Profesores", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['numero_profesores'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Puntaje", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['puntaje'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Estado", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['estado'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Tipo de Productividad", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['tipo_productividad'], $cellTextStyle, $paragraphStyle);

        // Agregar una separación entre registros
        $section->addTextBreak(1);
    }
}

// Nombre del archivo Word
$fileName = "Cuadros_Innovacion_Tecnologica{$identificador_solicitud}.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
