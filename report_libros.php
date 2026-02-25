<?php
// Requerir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los filtros desde la solicitud (si existen)
$identificador = isset($_GET['identificador']) ? $_GET['identificador'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    s.numero_oficio,  
    GROUP_CONCAT(CONCAT(t.documento_tercero, ' - ', t.nombre_completo) ORDER BY t.documento_tercero SEPARATOR '\n') AS AUTORES,
    f.nombre_fac_min AS FACULTAD,
    d.depto_nom_propio AS DEPARTAMENTO,
    s.producto AS `NOMBRE DEL PRODUCTO`,
    s.tipo_libro AS `TIPO DE LIBRO`,
    s.nombre_editorial AS EDITORIAL,
    s.isbn AS ISBN,
    s.identificador AS IDENTIFICADOR, s.evaluacion_1 as EVALUACION_1, s.evaluacion_2 AS EVALUACION_2, s.puntaje_final AS PUNTAJE_FINAL
FROM 
    libros s
JOIN 
    libro_profesor sp ON s.id_libro = sp.id_libro
JOIN 
    tercero t ON sp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND s.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(s.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por id_libro
$sql .= " GROUP BY s.id_libro";

// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja con ITEM al inicio
$headers = [
    'ITEM',  // Nueva columna numerada
    'OFICIO',
    'FACULTAD',
    'DEPARTAMENTO',
        'AUTORES',

    'NOMBRE DEL PRODUCTO',
    'TIPO DE LIBRO',
    'EDITORIAL',
    'ISBN',
    'IDENTIFICADOR',
    'EVALUACION_1',
    'EVALUACION_2',
    'PUNTAJE_FINAL'
];
$sheet->fromArray($headers, NULL, 'A1');

// Aplicar negrita a los encabezados
$sheet->getStyle('A1:J1')->getFont()->setBold(true);

// Ajustar ancho de columnas automáticamente
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Rellenar los datos en la hoja de Excel
if ($result->num_rows > 0) {
    $row = 2; // Iniciar en la segunda fila después de los encabezados
    $item = 1; // Contador para la columna ITEM
    
    while ($data = $result->fetch_assoc()) {
        // Reemplazar '\n' por saltos de línea reales en Excel
        $data['AUTORES'] = str_replace('\n', "\n", $data['AUTORES']);
        
        $sheet->fromArray([
            $item, // Número de fila en la columna ITEM
            $data['numero_oficio'],
            $data['FACULTAD'],
            $data['DEPARTAMENTO'],
                        $data['AUTORES'],

            $data['NOMBRE DEL PRODUCTO'],
            $data['TIPO DE LIBRO'],
            $data['EDITORIAL'],
            $data['ISBN'],
            $data['IDENTIFICADOR'],
            $data['EVALUACION_1'],
            $data['EVALUACION_2'],
            $data['PUNTAJE_FINAL']
        ], NULL, 'A' . $row);

        // Habilitar ajuste de texto en la columna AUTORES
        $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
$sheet->getColumnDimension('F')->setWidth(20); // Ajusta el ancho de la columna F

        $row++;
        $item++; // Incrementar el número de ITEM
    }
}

// Nombre del archivo Excel
$fileName = "Reporte_libros_" . date('Ymd') . ".xlsx";

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
