<?php
// Requerir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los filtros desde el modal (si existen)
$identificador_solicitud = isset($_GET['identificador_solicitud']) ? $_GET['identificador_solicitud'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    s.numero_oficio AS `NUMERO DE OFICIO`,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR ' \n') AS `DETALLES PROFESORES`,
    s.titulo_articulo AS `NOMBRE DEL PRODUCTO`,
    s.tipo_articulo AS `TIPO DE ARTICULO`,
    s.tipo_revista AS `TIPO REVISTA`,
    s.nombre_revista AS `NOMBRE REVISTA`,
    s.issn AS `ISSN`,
    s.eissn AS `eISSN`,
    s.ano_publicacion AS `AÑO`,
    s.tipo_publindex AS `TIPO publindex`,
    s.volumen AS `VOL`,
    s.numero_r AS `Nº ARTICULO`,
    s.numero_autores AS `Nª AUTORES`,
    s.puntaje AS `PUNTAJE`,
    s.identificador_solicitud AS `IDENTIFICADOR`,
    s.fecha_solicitud AS `FECHA DE INGRESO`,
    s.doi,
    CASE WHEN s.est_scimago = 1 THEN 'OK' ELSE '' END AS `SCIMAGO`,
    CASE WHEN s.est_doaj = 1 THEN 'OK' ELSE '' END AS `DOAJ`,
    CASE WHEN s.est_scopus = 1 THEN 'OK' ELSE '' END AS `SCOPUS`,
    CASE WHEN s.est_miar = 1 THEN 'OK' ELSE '' END AS `MIAR`
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
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND s.identificador_solicitud = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(s.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por id de solicitud
$sql .= " GROUP BY s.id_solicitud_articulo, d.depto_nom_propio";

// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'NUMERO DE OFICIO', 'FACULTAD', 'DEPARTAMENTO', 'CEDULA', 'DETALLES PROFESORES', 
    'NOMBRE DEL PRODUCTO', 'TIPO DE ARTICULO', 'TIPO REVISTA', 
    'NOMBRE REVISTA', 'ISSN', 'eISSN', 'AÑO', 'TIPO publindex', 
    'VOL', 'Nº ARTICULO', 'Nª AUTORES', 'PUNTAJE', 
    'IDENTIFICADOR', 'FECHA DE INGRESO', 'DOI','SCIMAGO', 'DOAJ', 'SCOPUS','MIAR' 
];
$sheet->fromArray($headers, NULL, 'A1');

// Ajustar anchos de columna
$sheet->getColumnDimension('B')->setWidth(20); // Facultad
$sheet->getColumnDimension('C')->setWidth(35); // Departamento
$sheet->getColumnDimension('E')->setWidth(20); // Detalles Profesores
$sheet->getColumnDimension('F')->setWidth(55); // Nombre del Producto
$sheet->getColumnDimension('G')->setWidth(55); // Nombre del Producto

$sheet->getStyle('E')->getAlignment()->setWrapText(true); // Ajustar texto en Detalles Profesores
$sheet->getStyle('F')->getAlignment()->setWrapText(true); // Ajustar texto en Detalles Profesores

// Rellenar los datos en la hoja de Excel
if ($result->num_rows > 0) {
    $row = 2; // Iniciar en la segunda fila después de los encabezados
     $id = 1; // Contador para ID consecutivo
    while ($data = $result->fetch_assoc()) {
        $sheet->fromArray(array_merge([$id], array_values($data)), NULL, 'A' . $row);
        $row++; $id++;
    }
}

// Nombre del archivo Excel
$fileName = "Reporte_Solicitudes_" . date('Ymd') . ".xlsx";

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
