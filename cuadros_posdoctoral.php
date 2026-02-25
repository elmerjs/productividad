<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener los datos de los estudios posdoctorales
$sql = "
SELECT 
    pd.id AS id_posdoctoral,
    pd.identificador,
    pd.numero_oficio,
    pd.documento_profesor,
    pd.titulo_obtenido,
    pd.institucion,
    pd.fecha_terminacion,
    pd.puntaje,
    pd.tipo_productividad,
    f.nombre_fac_min AS NOMBREF_FAC,
    d.depto_nom_propio AS NOMBRE_DEPTO,
    GROUP_CONCAT(DISTINCT CONCAT(prof.nombre_completo, ' - ', prof.documento_tercero) ORDER BY prof.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    posdoctoral pd
JOIN 
    posdoctoral_profesor pdp ON pdp.id_titulo = pd.id
JOIN 
    tercero prof ON pdp.fk_tercero = prof.documento_tercero
JOIN 
    deparmanentos d ON prof.fk_depto = d.pk_depto
JOIN 
    facultad f ON f.PK_FAC = d.FK_FAC
    WHERE 1 = 1

";


// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND pd.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(pd.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, pd.id, pd.identificador, pd.numero_oficio, pd.documento_profesor, pd.titulo_obtenido, pd.institucion, pd.fecha_terminacion, pd.puntaje, pd.tipo_productividad
ORDER BY 
    f.NOMBREF_FAC, d.NOMBRE_DEPTO, pd.fecha_terminacion";

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
$section->addText("INFORME DE ESTUDIOS POSTDOCTORALES", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

// Imprimir los datos de los estudios posdoctorales
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
        $table->addCell(2000)->addText("Identificador", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['identificador'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Número de Oficio", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['numero_oficio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Documento del Profesor", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['documento_profesor'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Título Obtenido", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['titulo_obtenido'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Institución", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['institucion'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Fecha de Terminación", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['fecha_terminacion'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Puntaje", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['puntaje'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Tipo de Productividad", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['tipo_productividad'], $cellTextStyle, $paragraphStyle);

        // Salto de línea después de la tabla
        $section->addTextBreak(1);
    }
}

// Guardar el documento
$filename = 'Informe_Estudio_Posdoctoral_' . date('Y-m-d') . '.docx';
$phpWord->save($filename, 'Word2007');
echo "El informe se ha generado correctamente: <a href='$filename'>$filename</a>";
?>
