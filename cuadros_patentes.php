<?php
// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;

// Crear la consulta SQL para obtener las patentes
$sql = "
SELECT 
   p.id_patente,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    p.numero_oficio,
    p.fecha_solicitud,
    p.producto,
    p.numero_profesores,
    p.puntaje,
    p.estado,
    p.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    patentes p
JOIN 
    patente_profesor pp ON pp.id_patente = p.id_patente
JOIN 
    tercero t ON pp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC   WHERE  (p.estado is null or p.estado <> 'an')
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
    p.id_patente,  p.numero_oficio, p.fecha_solicitud, p.producto, p.numero_profesores, p.puntaje, p.estado, p.tipo_productividad
ORDER BY 
    f.nombre_fac_min, d.depto_nom_propio, p.fecha_solicitud";

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
$section->addText("CUADROS DE PATENTES POR FACULTAD Y DEPARTAMENTO", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
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

// Variables para almacenar las facultades y departamentos procesados
$facultadAnterior = '';
$departamentoAnterior = '';

// Imprimir los datos de cada patente
if ($result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        // Verificar si la facultad ya ha sido agregada
        if ($data['FACULTAD'] !== $facultadAnterior) {
            // Añadir la facultad solo si es nueva
            $section->addText( mb_strtoupper($data['FACULTAD'], 'UTF-8'), ['bold' => true, 'size' => 12]);

            $facultadAnterior = $data['FACULTAD']; // Actualizar facultad procesada
        }

        // Verificar si el departamento ya ha sido agregado
        if ($data['DEPARTAMENTO'] !== $departamentoAnterior) {
            // Añadir el departamento solo si es nuevo
            $section->addText("DEPARTAMENTO: " . mb_strtoupper($data['DEPARTAMENTO'], 'UTF-8'), ['bold' => true, 'size' => 12]);

            $departamentoAnterior = $data['DEPARTAMENTO']; // Actualizar departamento procesado
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
        $table->addCell(6000)->addText($data['numero_oficio'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Fecha de Solicitud", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['fecha_solicitud'], $cellTextStyle, $paragraphStyle);

        $table->addRow();
        $table->addCell(1500)->addText("Producto", $cellTextStyle, $paragraphStyle);
        $table->addCell(6000)->addText($data['producto'], $cellTextStyle, $paragraphStyle);

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
$fileName = "Cuadros_Patentes_{$identificador_solicitud}.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
