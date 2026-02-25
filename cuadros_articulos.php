<?php

// Requerir la conexión a la base de datos y la librería PHPWord
require 'conn.php';
require 'vendor/autoload.php'; // Asegúrate de que la librería PHPWord esté instalada y configurada

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
// Obtener los filtros desde el modal (si existen)
//$identificador_solicitud = isset($_GET['identificador_solicitud']) ? $_GET['identificador_solicitud'] : null;
//$ano = isset($_GET['ano']) ? $_GET['ano'] : null;

//$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? $_GET['cuadro_identificador_solicitud'] : null;
$identificador_solicitud = isset($_GET['cuadro_identificador_solicitud']) ? trim($_GET['cuadro_identificador_solicitud']) : '';

//$ano = isset($_GET['cuadro_ano']) ? $_GET['cuadro_ano'] : null;
$ano = isset($_GET['cuadro_ano']) ? trim($_GET['cuadro_ano']) : '';

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    s.numero_oficio AS `NUMERO DE OFICIO`,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    s.titulo_articulo AS `NOMBRE DEL PRODUCTO`,
    s.nombre_revista AS `REVISTA`,
    s.issn AS `ISSN`,
    s.tipo_publindex AS `TIPO`,
        s.tipo_articulo AS `TIPO_ARTICULO`,

    s.ano_publicacion AS `AÑO`,
    s.volumen AS `VOL`,
    s.numero_r AS `Nº ARTICULO`,
    s.numero_autores AS `Nª AUTORES`,
    s.puntaje AS `PUNTAJE`,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`
FROM 
    solicitud s
JOIN 
    solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
JOIN 
    tercero t ON sp.fk_id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
LEFT JOIN 
    articulo a ON s.fk_id_articulo = a.id_articulo
WHERE (s.estado_solicitud is null or s.estado_solicitud <> 'an') ";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    // Filtrar por identificador si se proporciona
    $sql .= " AND s.identificador_solicitud = '" . $conn->real_escape_string($identificador_solicitud) . "'";
} elseif (!empty($ano)) {
    // Si el identificador está vacío pero el año tiene valor, filtrar por año
//$sql .= " AND s.vigencia_sol ='" . $conn->real_escape_string($ano) . "'";
$sql .= " AND s.vigencia_sol = " . (int) $ano;

}
// Agrupar resultados por facultad y departamento
$sql .= " GROUP BY s.id_solicitud_articulo, d.depto_nom_propio ORDER BY f.nombre_fac_min, d.depto_nom_propio";

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
// Mostrar valores de las variables antes de ejecutar la consulta
/*echo "<pre>";
echo "Identificador de solicitud: ";
var_dump($identificador_solicitud);
echo "Año: ";
var_dump($ano);
echo "SQL generada: ";
echo $sql;
echo "</pre>";
exit; // Detener la ejecución para analizar los valores antes de generar el Word
*/
// Añadir el título solo una vez
$section->addText("REVISTAS INDEXADAS", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);

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


    foreach ($departamentos as $departamento => $solicitudes) {
        // Agregar el departamento
         $section->addText("DEPARTAMENTO: " . mb_strtoupper($departamento, 'UTF-8'), ['bold' => true, 'size' => 11]);

        // Imprimir la tabla para cada departamento
        foreach ($solicitudes as $data) {
            // Añadir nombres de los profesores
           // Añadir nombres de los profesores en líneas separadas
    $detallesProfesores = explode("\n", $data['DETALLES PROFESORES']); // Divide el texto por las líneas
    foreach ($detallesProfesores as $detalle) {
        $section->addText($detalle, ['size' => 10]); // Agrega cada profesor en una nueva línea
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
                $table->addCell(1500)->addText($data['TIPO_ARTICULO'], $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText(mb_strtoupper($data['NOMBRE DEL PRODUCTO'], 'UTF-8'), $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("REVISTA", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText($data['REVISTA'], $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("ISSN", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText($data['ISSN'], $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("TIPO", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText($data['TIPO'], $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("AÑO", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText($data['AÑO'], $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("VOL.", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText($data['VOL'], $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("N°", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText($data['Nº ARTICULO'], $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("AUTORES", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText($data['Nª AUTORES'], $cellTextStyle, $paragraphStyle);

                $table->addRow();
                $table->addCell(1500)->addText("ASIGNAR", $cellTextStyle, $paragraphStyle);
                $table->addCell(6000)->addText("{$data['PUNTAJE']} /puntos", $cellTextStyle, $paragraphStyle);

            // Agregar una separación entre registros
            $section->addTextBreak(1);
        }
    }
}

// Nombre del archivo Word
$fileName = "Cuadros_articulos_{$identificador_solicitud}.docx";

// Configurar la cabecera para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
