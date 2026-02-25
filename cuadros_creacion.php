<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener las creaciones artísticas
$sql = "
SELECT 
    c.id AS id_creacion,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    c.numeroOficio,
    c.fecha_solicitud,
    c.tipo_producto,
    c.impacto,
    c.producto, 
    c.nombre_evento,
    c.evento,
    c.fecha_evento,
    c.lugar_evento,
    c.autores,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero) ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`,
    c.puntaje_final
FROM 
    creacion c
JOIN 
    creacion_profesor cp ON cp.id_creacion = c.id
JOIN 
    tercero ter ON cp.documento_profesor = ter.documento_tercero
JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
     WHERE 1 = 1 and (c.estado_creacion is null or c.estado_creacion <> 'an')
";


// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND c.identificador_completo = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(c.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY 
    d.depto_nom_propio,c.id, c.numeroOficio, c.fecha_solicitud, c.tipo_producto, c.impacto, c.producto, c.nombre_evento, c.evento, c.fecha_evento, c.lugar_evento, c.autores, c.puntaje_final
ORDER BY 
    f.nombre_fac_min, d.depto_nom_propio, c.fecha_solicitud;";

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
$section->addText("CUADROS DE CREACIÓN ARTÍSTICA POR FACULTAD Y DEPARTAMENTO", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

// Imprimir los datos de cada creación artística
if ($result->num_rows > 0) {
    $currentFacultad = '';
    $currentDepartamento = '';
    
    while ($data = $result->fetch_assoc()) {
        // Mostrar la facultad solo si cambia
if ($data['FACULTAD'] != $currentFacultad) {
    $section->addText(mb_strtoupper($data['FACULTAD'], 'UTF-8'), ['bold' => true, 'size' => 12]);
    $currentFacultad = $data['FACULTAD'];
}

// Mostrar el departamento solo si cambia
if ($data['DEPARTAMENTO'] != $currentDepartamento) {
    $section->addText("DEPARTAMENTO: " . mb_strtoupper($data['DEPARTAMENTO'], 'UTF-8'), ['bold' => true, 'size' => 12]);

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
        $table->setWidth('50%');

        // Encabezados de la tabla
        $row = $table->addRow();
        $row->addCell(1500, $headerCellStyle)->addText("Campo", $cellTextStyleHeader, $paragraphStyle);
        $row->addCell(6000, $headerCellStyle)->addText("Detalle", $cellTextStyleHeader, $paragraphStyle);

        // Agregar datos a la tabla
        $table->addRow();
        $table->addCell(1500)->addText("Número de Oficio", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['numeroOficio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Fecha de Solicitud", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['fecha_solicitud'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Tipo de Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['tipo_producto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Impacto", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['impacto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['producto'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Nombre del Evento", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['nombre_evento'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Evento", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['evento'], $cellTextStyle, $paragraphStyle);

   
        $table->addRow();
        $table->addCell(1500)->addText("Lugar y fecha", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText("{$data['fecha_evento']}, {$data['lugar_evento']}", $cellTextStyle, $paragraphStyle);
        

        $table->addRow();
        $table->addCell(1500)->addText("Número de Autores", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['autores'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Puntaje Final", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['puntaje_final'], $cellTextStyle, $paragraphStyle);

        // Agregar una separación entre registros
        $section->addTextBreak(1);
    }
}

// Nombre del archivo Word
$fileName = "Cuadros_Creacion_Artistica_{$identificador_solicitud}.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
