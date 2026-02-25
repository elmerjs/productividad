<?php
// Requerir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los filtros desde el modal (si existen)
$identificador_solicitud = isset($_GET['identificador']) ? $_GET['identificador'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    p.nombre_evento AS `EVENTO_PREMIO`,
    p.ambito AS `AMBITO`,
    p.categoria_premio AS `CATEGORIA_PREMIO`,
    p.nivel_ganado AS `NIVEL_GANADO`,
    p.lugar_fecha AS `LUGAR_Y_FECHA`,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`,
    p.numero_oficio AS `OFICIO`, 
    p.puntos AS `PUNTOS`
FROM 
    premios p 
JOIN 
    premios_profesor pp ON pp.id_premio = p.id
JOIN 
    tercero t ON pp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 
    1 = 1
";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND p.identificador_solicitud = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(p.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados
$sql .= " GROUP BY f.nombre_fac_min, d.depto_nom_propio, p.nombre_evento, p.ambito, p.categoria_premio, p.nivel_ganado, p.lugar_fecha, p.numero_oficio";
$sql .= " ORDER BY p.id DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ITEM',
    'FACULTAD',
    'DEPARTAMENTO',
    'CEDULA',
    'NOMBRES',
    'EVENTO PREMIO',
    'AMBITO',
    'CATEGORIA PREMIO',
    'NIVEL GANADO',
    'LUGAR Y FECHA',
    'DETALLES PROFESORES',
    'OFICIO', 
    'PUNTOS'
];
$sheet->fromArray($headers, NULL, 'A1');

// Rellenar los datos en la hoja de Excel
if ($result->num_rows > 0) {
    $row = 2; // Iniciar en la segunda fila después de los encabezados
    $contador = 1; // Inicializar el contador
    while ($data = $result->fetch_assoc()) {
        array_unshift($data, $contador); // Agregar el contador al inicio del array
        $sheet->fromArray(array_values($data), NULL, 'A' . $row);
        $row++;
        $contador++;
    }
}

// Nombre del archivo Excel
$fileName = "Reporte_premios_" . date('Ymd') . ".xlsx";

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
