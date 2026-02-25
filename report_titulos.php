<?php
// Requerir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los filtros desde el modal (si existen)
//$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
//$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

$identificador_solicitud = isset($_GET['identificador_solicitud']) ? $_GET['identificador_solicitud'] : null;
$ano = isset($_GET['ano']) ? $_GET['ano'] : null;
$sql = "
SELECT 
 
 f.nombre_fac_min AS FACULTAD,
         d.depto_nom_propio AS DEPARTAMENTO,
   
    t.numero_oficio,
    t.titulo_obtenido,
    t.tipo,
    t.tipo_estudio,
    t.institucion,
    t.fecha_terminacion,
    t.resolucion_convalidacion,
    t.puntaje,
    t.tipo_productividad,
    
    -- Obtener los nombres de los profesores relacionados con el título
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' - ', ter.documento_tercero)
        ORDER BY ter.nombre_completo
        SEPARATOR ', '
    ) AS profesores
FROM 
    titulos t
JOIN 
    titulo_profesor tp ON tp.id_titulo = t.id_titulo
JOIN 
    tercero ter ON tp.fk_tercero = ter.documento_tercero
join 
    deparmanentos d  on (d.PK_DEPTO = ter.fk_depto) 
join 
    facultad f on (f.PK_FAC = d.FK_FAC)
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}



$sql .= " GROUP BY 
    t.id_titulo, t.identificador, t.numero_oficio, t.titulo_obtenido, t.tipo, 
    t.tipo_estudio, t.institucion, t.fecha_terminacion, t.resolucion_convalidacion, 
    t.puntaje, t.tipo_productividad
ORDER BY 
    t.fecha_terminacion DESC
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'FACULTAD','DEPARTAMENTO','NÚMERO DE OFICIO', 'TÍTULO OBTENIDO', 'TIPO', 
    'TIPO DE ESTUDIO', 'INSTITUCIÓN', 'FECHA DE TERMINACIÓN', 'RESOLUCIÓN DE CONVALIDACIÓN', 
    'PUNTAJE', 'TIPO DE PRODUCTIVIDAD', 'PROFESORES'
];
$sheet->fromArray($headers, NULL, 'A1');

// Rellenar los datos en la hoja de Excel
if ($result->num_rows > 0) {
    $row = 2; // Iniciar en la segunda fila después de los encabezados
     $contador = 1; // Iniciar contador desde 1
    while ($data = $result->fetch_assoc()) {
       $fila = array_values($data);
        array_shift($fila); // Remover la primera columna original (IDENTIFICADOR)
        array_unshift($fila, $contador); // Agregar el número consecutivo al inicio
        
        $sheet->fromArray($fila, NULL, 'A' . $row);
        $contador++; // Incrementar contador
        $row++;
    }
}

// Nombre del archivo Excel
$fileName = "Reporte_titulos_" . date('Ymd') . ".xlsx";

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
