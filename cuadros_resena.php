<?php
// Requerir la conexión a la base de datos y la librería PHPWord
include_once 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener los datos de reseñas
$sql = "
SELECT 
    r.id AS id_resena,
    r.identificador_completo,
    r.numeroOficio,
    r.fecha_solicitud,
    r.categoria_colciencias,
    r.nombre_revista,
    r.producto,
    r.issn,
    r.autores,
    r.evaluacion1,
    r.evaluacion2,
    r.puntaje,
    r.puntaje_final,
    r.tipo_productividad,
    f.nombre_fac_min AS NOMBREF_FAC,
    d.depto_nom_propio AS NOMBRE_DEPTO,
    GROUP_CONCAT(DISTINCT CONCAT(prof.nombre_completo, ' - ', prof.documento_tercero) ORDER BY prof.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    resena_bon r
JOIN 
    resena_bon_profesor rbp ON rbp.id_publicacion_bon = r.id
JOIN 
    tercero prof ON rbp.documento_profesor = prof.documento_tercero
JOIN 
    deparmanentos d ON prof.fk_depto = d.pk_depto
JOIN 
    facultad f ON f.PK_FAC = d.FK_FAC
    WHERE 1 = 1

";


// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND r.identificador_completo = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(r.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, r.id, r.identificador_completo, r.numeroOficio, r.fecha_solicitud, r.categoria_colciencias, r.nombre_revista, r.producto, r.issn, r.autores, r.evaluacion1, r.evaluacion2, r.puntaje, r.puntaje_final, r.tipo_productividad
ORDER BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, r.fecha_solicitud";


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
$section->addText("INFORME DE RESEÑAS", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

// Imprimir los datos de reseñas
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
        $table->addCell(2000)->addText("Categoría COLCIENCIAS", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['categoria_colciencias'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Nombre de la Revista", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['nombre_revista'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['producto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("ISSN", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['issn'], $cellTextStyle, $paragraphStyle);

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
        $table->addCell(2000)->addText("Puntaje Final", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['puntaje_final'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Tipo de Productividad", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['tipo_productividad'], $cellTextStyle, $paragraphStyle);

        // Añadir un salto de línea después de cada reseña
        $section->addTextBreak(1);
    }
} else {
    $section->addText("No hay registros disponibles.", ['size' => 12], ['alignment' => Jc::CENTER]);
}

// Guardar el documento
$filename = 'resenas_bon_' . date('Y_m_d_H_i_s') . '.docx';
$phpWord->save($filename, 'Word2007');

// Cerrar la conexión a la base de datos
$conn->close();

// Enviar el documento generado
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
readfile($filename);
exit;
?>
