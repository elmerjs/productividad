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

// Crear la consulta SQL con los filtros opcionales
$sql = "
    SELECT 
        dt.id AS id,
        dt.identificador AS identificador,
        dt.numero_oficio AS numero_oficio,
        dt.documento_profesor AS documento_profesor,
        dt.titulo_obtenido AS titulo_obtenido,
        dt.tipo AS tipo,
        dt.nombre_estudiante AS nombre_estudiante,
        dt.fecha_sustentacion AS fecha_sustentacion,
        dt.fecha_terminacion AS fecha_terminacion,
        dt.resolucion AS resolucion,
        dt.puntaje AS puntaje,
        dt.tipo_productividad AS tipo_productividad,
        dt.created_at AS created_at,
        
        -- Facultad y Departamento
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento,
        
        -- Detalles de los profesores que dirigen tesis
        GROUP_CONCAT(
            DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
            ORDER BY ter.documento_tercero
            SEPARATOR '\n'
        ) AS detalles_profesores

    FROM 
        direccion_tesis dt
    JOIN 
        direccion_t_profesor dtp ON dtp.id_titulo = dt.id
    JOIN 
        tercero ter ON dtp.fk_tercero = ter.documento_tercero
    JOIN 
        deparmanentos d ON ter.fk_depto = d.PK_DEPTO
    JOIN 
        facultad f ON d.FK_FAC = f.PK_FAC

    WHERE 1 = 1
";
// Añadir condiciones según los filtros

if (!empty($identificador)) {
    $sql .= " AND dt.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND dt.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}
$sql .= " 
    GROUP BY 
        dt.id, dt.identificador, dt.numero_oficio, dt.documento_profesor, 
        dt.titulo_obtenido, dt.tipo, dt.nombre_estudiante, dt.fecha_sustentacion, 
        dt.fecha_terminacion, dt.resolucion, dt.puntaje, dt.tipo_productividad, dt.created_at,
        f.nombre_fac_min, d.depto_nom_propio

    ORDER BY 
        dt.fecha_terminacion;
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Crear un nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar encabezados de la hoja
$headers = [
    'ID', 'IDENTIFICADOR', 'NÚMERO DE OFICIO', 'DOCUMENTO PROFESOR', 'TÍTULO OBTENIDO', 
    'TIPO', 'NOMBRE ESTUDIANTE', 'FECHA DE SUSTENTACIÓN', 'FECHA DE TERMINACIÓN', 
    'RESOLUCIÓN', 'PUNTAJE', 'TIPO DE PRODUCTIVIDAD', 'FECHA DE CREACIÓN', 
    'FACULTAD', 'DEPARTAMENTO', 'DETALLES DE PROFESORES'
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
$fileName = "Reporte_dir_tesis_" . date('Ymd') . ".xlsx";

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
