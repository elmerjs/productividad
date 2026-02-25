<?php
// Requerir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los filtros desde el modal (si existen)
//$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
//$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

$identificador_solicitud = isset($_GET['identificador']) ? $_GET['identificador'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
    SELECT 
        rb.id AS id,
        rb.identificador_completo AS identificador_completo,
        rb.numeroOficio AS numero_oficio,
        rb.fecha_solicitud AS fecha_solicitud,
        rb.categoria_colciencias AS categoria_colciencias,
        rb.nombre_revista AS nombre_revista,
        rb.producto AS producto,
        rb.issn AS issn,
        rb.autores AS autores,
        rb.evaluacion1 AS evaluacion1,
        rb.evaluacion2 AS evaluacion2,
        rb.puntaje AS puntaje,
        rb.puntaje_final AS puntaje_final,
        rb.tipo_productividad AS tipo_productividad,
        
        -- Facultad y Departamento
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento,
        
        -- Concatenar los detalles de los profesores
        GROUP_CONCAT(
            DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
            ORDER BY ter.documento_tercero
            SEPARATOR '\n'
        ) AS detalles_profesores

    FROM 
        resena_bon rb
    JOIN 
        resena_bon_profesor rpp ON rpp.id_publicacion_bon = rb.id
    JOIN 
        tercero ter ON rpp.documento_profesor = ter.documento_tercero
    JOIN 
        deparmanentos d ON ter.fk_depto = d.PK_DEPTO
    JOIN 
        facultad f ON d.FK_FAC = f.PK_FAC

    WHERE 1 = 1
";

// Añadir condiciones según los filtros
if (!empty($identificador_completo)) {
    $sql .= " AND rb.identificador_completo = '" . $conn->real_escape_string($identificador_completo) . "'";
}
if (!empty($numeroOficio)) {
    $sql .= " AND rb.numeroOficio = '" . $conn->real_escape_string($numeroOficio) . "'";
}

$sql .= " 
    GROUP BY 
        rb.id, rb.identificador_completo, rb.numeroOficio, rb.fecha_solicitud, 
        rb.categoria_colciencias, rb.nombre_revista, rb.producto, rb.issn, 
        rb.autores, rb.evaluacion1, rb.evaluacion2, rb.puntaje, 
        rb.puntaje_final, rb.tipo_productividad, f.nombre_fac_min, d.depto_nom_propio

    ORDER BY 
        rb.fecha_solicitud;
";

    // Ejecutar la consulta
// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'IDENTIFICADOR COMPLETO', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD', 
    'CATEGORÍA COLCIENCIAS', 'NOMBRE DE REVISTA', 'PRODUCTO', 'ISSN', 
    'AUTORES', 'EVALUACIÓN 1', 'EVALUACIÓN 2', 'PUNTAJE', 'PUNTAJE FINAL', 
    'TIPO DE PRODUCTIVIDAD', 'FACULTAD', 'DEPARTAMENTO', 'DETALLES DE PROFESORES'
];
$sheet->fromArray($headers, NULL, 'A1');

// Rellenar los datos en la hoja de Excel
if ($result->num_rows > 0) {
    $row = 2; // Iniciar en la segunda fila después de los encabezados
    while ($data = $result->fetch_assoc()) {
        $sheet->fromArray(array_values($data), NULL, 'A' . $row);
        $row++;
    }
}

// Nombre del archivo Excel
$fileName = "Reporte_resena_" . date('Ymd') . ".xlsx";

// Enviar el archivo como descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Crear el archivo Excel y enviarlo al navegador
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Cerrar la conexión a la base de datos
$conn->close();
exit;
?>
