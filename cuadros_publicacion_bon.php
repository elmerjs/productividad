<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener los datos de publicaciones
$sql = "
SELECT 
    p.id AS id_publicacion,
    p.identificador_completo,
    p.numeroOficio,
    p.fecha_solicitud,
    p.tipo_producto,
    p.nombre_revista,
    p.producto,
    p.isbn,
    p.fecha_publicacion,
    p.lugar_publicacion,
    p.autores,
    p.evaluacion1,
    p.evaluacion2,
    p.puntaje,
    p.puntaje_final,
    p.tipo_productividad,
    f.nombre_fac_min AS NOMBREF_FAC,
    d.depto_nom_propio AS NOMBRE_DEPTO,
    GROUP_CONCAT(DISTINCT CONCAT(prof.nombre_completo, ' - ', prof.documento_tercero) ORDER BY prof.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    publicacion_bon p
JOIN 
    publicacion_bon_profesor pbp ON pbp.id_publicacion_bon = p.id
JOIN 
    tercero prof ON pbp.documento_profesor = prof.documento_tercero
JOIN 
    deparmanentos d ON prof.fk_depto = d.pk_depto
JOIN 
    facultad f ON f.PK_FAC = d.FK_FAC
        WHERE 1 = 1

";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND p.identificador_completo = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(p.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, p.id, p.identificador_completo, p.numeroOficio, p.fecha_solicitud, p.tipo_producto, p.nombre_revista, p.producto, p.isbn, p.fecha_publicacion, p.lugar_publicacion, p.autores, p.evaluacion1, p.evaluacion2, p.puntaje, p.puntaje_final, p.tipo_productividad
ORDER BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, p.fecha_solicitud";


// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Word
$phpWord = new PhpWord();
$section = $phpWord->addSection([
    'marginLeft' => 1000,  // 2 cm margen izquierdo
    'marginRight' => 1000, // 2 cm margen derecho
    'marginTop' => 1000,   // 2 cm margen superior
    'marginBottom' => 1000 // 2 cm margen inferior
]);

// Añadir el título solo una vez
$section->addText("INFORME DE PUBLICACIONES", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

// Imprimir los datos de publicaciones
if ($result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        // Si cambia la facultad, añadir un encabezado de facultad
        if ($currentFacultad !== $data['NOMBREF_FAC']) {
            $currentFacultad = $data['NOMBREF_FAC'];
            $section->addText("Facultad: " . $currentFacultad, ['bold' => true, 'size' => 12]);
        }

        // Si cambia el departamento, añadir un encabezado de departamento
        if ($currentDepartamento !== $data['NOMBRE_DEPTO']) {
            $currentDepartamento = $data['NOMBRE_DEPTO'];
            $section->addText("Departamento: " . $currentDepartamento, ['bold' => true, 'size' => 11]);
        }

        // Añadir los detalles de los profesores
        $section->addText("Profesores:", ['bold' => true, 'size' => 10]);
        $profesores = explode("\n", $data['DETALLES_PROFESORES']);
        foreach ($profesores as $profesor) {
            $section->addText($profesor, ['size' => 10]);
        }

        // Crear la tabla
        $table = $section->addTable('CustomTableStyle');
        $table->setWidth('100%');

        // Encabezados de la tabla
        $row = $table->addRow();
        $row->addCell(2000, $headerCellStyle)->addText("Campo", $cellTextStyleHeader, $paragraphStyle);
        $row->addCell(8000, $headerCellStyle)->addText("Detalle", $cellTextStyleHeader, $paragraphStyle);

        // Agregar datos a la tabla
        $table->addRow();
        $table->addCell(2000)->addText("Identificador Completo", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['identificador_completo'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Número de Oficio", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['numeroOficio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Fecha de Solicitud", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['fecha_solicitud'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Tipo de Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['tipo_producto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Nombre de la Revista", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['nombre_revista'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['producto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("ISBN", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['isbn'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Fecha de Publicación", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['fecha_publicacion'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Lugar de Publicación", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['lugar_publicacion'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Autores", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['autores'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Evaluación 1", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['evaluacion1'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Evaluación 2", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['evaluacion2'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Puntaje", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['puntaje'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Tipo de Productividad", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['tipo_productividad'], $cellTextStyle, $paragraphStyle);

        // Agregar una separación entre registros
        $section->addTextBreak(1);
    }
}

// Nombre del archivo Word
$fileName = "Informe_publicacion.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
