<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener las traducciones
$sql = "
SELECT 
    t.id,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numero_oficio,
    t.fecha_solicitud,
    t.producto,
    t.numero_profesores,
    t.puntaje,
    t.estado,
   GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero) ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
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
if (!empty($identificador_solicitud)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(t.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= "GROUP BY 
    t.id, t.numero_oficio, t.fecha_solicitud, t.producto, t.numero_profesores, t.puntaje, t.estado
ORDER BY 
    f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud;";

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
$section->addText("CUADROS DE TRADUCCIÓN  PUBLICACION - BONIFICACION", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

// Imprimir los datos de cada traducción
if ($result->num_rows > 0) {
    $currentFacultad = '';
    $currentDepartamento = '';
    
    while ($data = $result->fetch_assoc()) {
        // Mostrar la facultad solo si cambia
        if ($data['FACULTAD'] != $currentFacultad) {
            $section->addText("Facultad: {$data['FACULTAD']}", ['bold' => true, 'size' => 12]);
            $currentFacultad = $data['FACULTAD'];
        }

        // Mostrar el departamento solo si cambia
        if ($data['DEPARTAMENTO'] != $currentDepartamento) {
            $section->addText("Departamento: {$data['DEPARTAMENTO']}", ['bold' => true, 'size' => 12]);
            $currentDepartamento = $data['DEPARTAMENTO'];
        }

        // Añadir los detalles de los profesores
        $profesores = explode("\n", $data['DETALLES_PROFESORES']); // Separar los nombres de los profesores por salto de línea
        foreach ($profesores as $profesor) {
            $section->addText("{$profesor}", ['size' => 10]); // Agregar cada profesor en una línea
            $section->addTextBreak(0); // Salto de línea después de cada profesor
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
        $table->addCell(2000)->addText("Número de Oficio", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['numero_oficio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Fecha de Solicitud", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['fecha_solicitud'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['producto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Número de Profesores", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['numero_profesores'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Puntaje", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['puntaje'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(2000)->addText("Estado", $cellTextStyle, $paragraphStyle);
        $table->addCell(8000)->addText($data['estado'], $cellTextStyle, $paragraphStyle);

       

        // Agregar una separación entre registros
        $section->addTextBreak(1);
    }
}

// Nombre del archivo Word
$fileName = "Cuadros_Traduccion_bonificacion.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
