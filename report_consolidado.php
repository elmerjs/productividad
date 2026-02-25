<?php
// Requerir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los filtros desde la solicitud (si existen)
$identificador = $_GET['identificador_solicitud'] ?? null;
$ano = $_GET['ano'] ?? null;

// Crear un nuevo documento de Excel con una hoja predeterminada.
// Esta hoja se usará para la primera tabla con datos, si los hay.
$spreadsheet = new Spreadsheet();
$hoja_existente = false;

// *** HOJA 1: PREMIOS ***
// Consulta SQL para Premios con filtros
$sql1 = "
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
    1 = 1 AND p.estado <> 'an'  -- MODIFICADO: Excluir anulados
";

if (!empty($identificador)) {
    $sql1 .= " AND p.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql1 .= " AND YEAR(p.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

$sql1 .= " GROUP BY f.nombre_fac_min, d.depto_nom_propio, p.nombre_evento, p.ambito, p.categoria_premio, p.nivel_ganado, p.lugar_fecha, p.numero_oficio";
$sql1 .= " ORDER BY p.id DESC";

$result1 = $conn->query($sql1);

// Lógica para crear la hoja solo si hay resultados
if ($result1->num_rows > 0) {
    $sheet1 = $spreadsheet->getActiveSheet();
    $sheet1->setTitle('Premios');
    $hoja_existente = true;

    // Encabezados
    $headers1 = [
        'ITEM', 'FACULTAD', 'DEPARTAMENTO', 'CEDULA', 'NOMBRES', 'EVENTO PREMIO',
        'AMBITO', 'CATEGORIA PREMIO', 'NIVEL GANADO', 'LUGAR Y FECHA',
        'DETALLES PROFESORES', 'OFICIO', 'PUNTOS'
    ];
    $sheet1->fromArray($headers1, NULL, 'A1');
    $sheet1->getStyle('A1:M1')->getFont()->setBold(true);

    // Llenar datos
    $row1 = 2;
    $contador1 = 1;
    while ($data1 = $result1->fetch_assoc()) {
        array_unshift($data1, $contador1);
        $data1['DETALLES PROFESORES'] = str_replace('\n', "\n", $data1['DETALLES PROFESORES']);
        $sheet1->fromArray(array_values($data1), NULL, 'A' . $row1);
        $row1++;
        $contador1++;
    }
}

// *** HOJA 2: LIBROS ***
$sql2 = "SELECT s.numero_oficio, GROUP_CONCAT(CONCAT(t.documento_tercero, ' - ', t.nombre_completo) ORDER BY t.documento_tercero SEPARATOR '\n') AS AUTORES, f.nombre_fac_min AS FACULTAD, d.depto_nom_propio AS DEPARTAMENTO, s.producto AS `NOMBRE DEL PRODUCTO`, s.tipo_libro AS `TIPO DE LIBRO`, s.nombre_editorial AS EDITORIAL, s.isbn AS ISBN, s.identificador AS IDENTIFICADOR, s.evaluacion_1 AS EVALUACION_1, s.evaluacion_2 AS EVALUACION_2, s.puntaje_final AS PUNTAJE_FINAL FROM libros s JOIN libro_profesor sp ON s.id_libro = sp.id_libro JOIN tercero t ON sp.id_profesor = t.documento_tercero JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO JOIN facultad f ON d.FK_FAC = f.PK_FAC 
WHERE 1 = 1 AND s.estado <> 'an' -- MODIFICADO: Excluir anulados
";

if (!empty($identificador)) {
    $sql2 .= " AND s.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql2 .= " AND YEAR(s.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}
$sql2 .= " GROUP BY s.id_libro";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    $sheet2 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet2->setTitle('Libros');
    $hoja_existente = true;

    $headers2 = ['ITEM', 'OFICIO', 'FACULTAD', 'DEPARTAMENTO', 'AUTORES', 'NOMBRE DEL PRODUCTO', 'TIPO DE LIBRO', 'EDITORIAL', 'ISBN', 'IDENTIFICADOR', 'EVALUACION_1', 'EVALUACION_2', 'PUNTAJE_FINAL'];
    $sheet2->fromArray($headers2, NULL, 'A1');
    $sheet2->getStyle('A1:M1')->getFont()->setBold(true);

    $row = 2;
    $item = 1;
    while ($data = $result2->fetch_assoc()) {
        $data['AUTORES'] = str_replace('\n', "\n", $data['AUTORES']);
        $sheet2->fromArray([
            $item, $data['numero_oficio'], $data['FACULTAD'], $data['DEPARTAMENTO'], $data['AUTORES'], $data['NOMBRE DEL PRODUCTO'],
            $data['TIPO DE LIBRO'], $data['EDITORIAL'], $data['ISBN'], $data['IDENTIFICADOR'], $data['EVALUACION_1'],
            $data['EVALUACION_2'], $data['PUNTAJE_FINAL']
        ], NULL, 'A' . $row);
        $row++;
        $item++;
    }
}

// *** HOJA 3: ARTICULOS ***
// *** HOJA 3: ARTICULOS ***
$sql3 = "SELECT
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
    s.doi AS DOI,
    CASE WHEN s.est_scimago = 1 THEN 'OK' ELSE '' END AS `SCIMAGO`,
    CASE WHEN s.est_doaj = 1 THEN 'OK' ELSE '' END AS `DOAJ`,
    CASE WHEN s.est_scopus = 1 THEN 'OK' ELSE '' END AS `SCOPUS`,
    CASE WHEN s.est_miar = 1 THEN 'OK' ELSE '' END AS `MIAR`
FROM
    solicitud s
INNER JOIN
    solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
INNER JOIN
    tercero t ON sp.fk_id_profesor = t.documento_tercero
INNER JOIN
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
INNER JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1 AND (s.estado_solicitud <> 'an' OR s.estado_solicitud IS NULL)
";

// CORRECCIÓN: Asegurar que el identificador se limpia correctamente
if (!empty($identificador)) {
    $sql3 .= " AND s.identificador_solicitud = '" . $conn->real_escape_string(trim($identificador)) . "'";
}

if (!empty($ano)) {
    $sql3 .= " AND YEAR(s.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Simplificamos el GROUP BY al ID principal para evitar exclusiones por inconsistencias de texto
$sql3 .= " GROUP BY s.id_solicitud_articulo";
$result3 = $conn->query($sql3);

if ($result3->num_rows > 0) {
    $sheet3 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet3->setTitle('Articulos');
    $hoja_existente = true;

    $headers3 = ['ID', 'NUMERO DE OFICIO', 'FACULTAD', 'DEPARTAMENTO', 'CEDULA', 'DETALLES PROFESORES',
        'NOMBRE DEL PRODUCTO', 'TIPO DE ARTICULO', 'TIPO REVISTA',
        'NOMBRE REVISTA', 'ISSN', 'eISSN', 'AÑO', 'TIPO publindex',
        'VOL', 'Nº ARTICULO', 'Nª AUTORES', 'PUNTAJE',
        'IDENTIFICADOR', 'FECHA DE INGRESO', 'DOI','SCIMAGO', 'DOAJ', 'SCOPUS','MIAR' ];
    $sheet3->fromArray($headers3, NULL, 'A1');
    $sheet3->getStyle('A1:Y1')->getFont()->setBold(true);

    $row = 2;
    $item = 1;
    while ($data = $result3->fetch_assoc()) {
        $data['DETALLES PROFESORES'] = str_replace('\n', "\n", $data['DETALLES PROFESORES']);
        $sheet3->fromArray([
            $item, $data['NUMERO DE OFICIO'], $data['FACULTAD'], $data['DEPARTAMENTO'], $data['CEDULA'], $data['DETALLES PROFESORES'],
            $data['NOMBRE DEL PRODUCTO'], $data['TIPO DE ARTICULO'], $data['TIPO REVISTA'],
            $data['NOMBRE REVISTA'], $data['ISSN'], $data['eISSN'], $data['AÑO'], $data['TIPO publindex'],
            $data['VOL'], $data['Nº ARTICULO'], $data['Nª AUTORES'], $data['PUNTAJE'],
            $data['IDENTIFICADOR'], $data['FECHA DE INGRESO'], $data['DOI'], $data['SCIMAGO'],
            $data['DOAJ'], $data['SCOPUS'], $data['MIAR']
        ], NULL, 'A' . $row);
        $row++;
        $item++;
    }
}

// *** HOJA 4: PATENTES ***
$sql4 = "
SELECT
    f.nombre_fac_min AS FACULTAD,
    d.depto_nom_propio AS DEPARTAMENTO,
    p.numero_oficio,
    p.fecha_solicitud,
    p.producto,
    p.numero_profesores,
    p.puntaje,
    p.estado,
    p.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) 
                 ORDER BY t.documento_tercero SEPARATOR '\n') AS DETALLES_PROFESORES
FROM
    patentes p
JOIN
    patente_profesor pp ON pp.id_patente = p.id_patente
JOIN
    tercero t ON pp.id_profesor = t.documento_tercero
JOIN
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1 AND p.estado <> 'an' -- MODIFICADO: Excluir anulados
";

if (!empty($identificador)) {
    $sql4 .= " AND p.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql4 .= " AND YEAR(p.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

$sql4 .= " GROUP BY
    p.id_patente, p.numero_oficio, p.fecha_solicitud, p.producto, 
    p.numero_profesores, p.puntaje, p.estado, p.tipo_productividad";

$result4 = $conn->query($sql4);

if ($result4->num_rows > 0) {
    $sheet4 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet4->setTitle('Patentes');
    $hoja_existente = true;

    // Encabezados (ID PATENTE -> N°)
    $headers4 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD',
        'PRODUCTO', 'NÚMERO DE PROFESORES', 'PUNTAJE', 'ESTADO',
        'TIPO DE PRODUCTIVIDAD', 'DETALLES DE PROFESORES'
    ];
    $sheet4->fromArray($headers4, NULL, 'A1');
    $sheet4->getStyle('A1:K1')->getFont()->setBold(true);

    $row4 = 2;
    $contador = 1;
    while ($data4 = $result4->fetch_assoc()) {
        $data4['DETALLES_PROFESORES'] = str_replace('\n', "\n", $data4['DETALLES_PROFESORES']);

        // Insertar número consecutivo
        $sheet4->setCellValue('A' . $row4, $contador);

        // Resto de datos a partir de la columna B
        $sheet4->fromArray(array_values($data4), NULL, 'B' . $row4);

        $row4++;
        $contador++;
    }
}

// *** HOJA 5: CREACIONES ***
$sql5 = "
SELECT
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numeroOficio,
    t.fecha_solicitud,
    t.tipo_producto,
    t.impacto,
    t.producto,
    t.nombre_evento,
    t.evento,
    t.fecha_evento,
    t.lugar_evento,
    t.autores,
    t.evaluacion1,
    t.evaluacion2,
    t.puntaje_final,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero) 
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM
    creacion t
JOIN
    creacion_profesor tp ON tp.id_creacion = t.id
JOIN
    tercero ter ON tp.documento_profesor = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1 AND (t.estado_creacion IS NULL OR t.estado_creacion <> 'an') -- SIN MODIFICAR (ya estaba correcta)
";

if (!empty($identificador)) {
    $sql5 .= " AND t.identificador_completo = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql5 .= " AND YEAR(t.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

$sql5 .= " GROUP BY
    t.id, t.numeroOficio, t.fecha_solicitud, t.tipo_producto, t.impacto, t.producto,
    t.nombre_evento, t.evento, t.fecha_evento, t.lugar_evento, t.autores,
    t.evaluacion1, t.evaluacion2, t.puntaje_final
ORDER BY
    f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud";

$result5 = $conn->query($sql5);

if ($result5->num_rows > 0) {
    $sheet5 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet5->setTitle('Creaciones');
    $hoja_existente = true;

    // Encabezados (ID -> N°)
    $headers5 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD',
        'TIPO DE PRODUCTO', 'IMPACTO', 'PRODUCTO', 'NOMBRE DEL EVENTO', 'EVENTO',
        'FECHA DEL EVENTO', 'LUGAR DEL EVENTO', 'AUTORES', 'EVALUACIÓN 1', 'EVALUACIÓN 2',
        'PUNTAJE FINAL', 'DETALLES DE PROFESORES'
    ];
    $sheet5->fromArray($headers5, NULL, 'A1');
    $sheet5->getStyle('A1:Q1')->getFont()->setBold(true);

    $row5 = 2;
    $contador = 1;
    while ($data5 = $result5->fetch_assoc()) {
        $data5['DETALLES_PROFESORES'] = str_replace('\n', "\n", $data5['DETALLES_PROFESORES']);

        // Agregar número consecutivo
        $sheet5->setCellValue('A' . $row5, $contador);

        // Resto de valores desde la columna B
        $sheet5->fromArray(array_values($data5), NULL, 'B' . $row5);

        $row5++;
        $contador++;
    }
}



// *** HOJA 6: TÍTULOS ***
$sql6 = "
SELECT
    t.identificador,
    t.numero_oficio,
    t.titulo_obtenido,
    t.tipo,
    t.tipo_estudio,
    t.institucion,
    t.fecha_terminacion,
    t.resolucion_convalidacion,
    t.puntaje,
    t.tipo_productividad,
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
WHERE 1 = 1 AND (t.estado_titulo IS NULL OR t.estado_titulo <> 'an') -- MODIFICADO: Excluir anulados (y permitir nulos)
";

if (!empty($identificador)) {
    $sql6 .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}

$sql6 .= " GROUP BY
    t.id_titulo, t.identificador, t.numero_oficio, t.titulo_obtenido, t.tipo,
    t.tipo_estudio, t.institucion, t.fecha_terminacion, t.resolucion_convalidacion,
    t.puntaje, t.tipo_productividad
ORDER BY
    t.fecha_terminacion DESC";

$result6 = $conn->query($sql6);

if ($result6->num_rows > 0) {
    $sheet6 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet6->setTitle('Títulos');
    $hoja_existente = true;

    // Encabezados (ID -> N°)
    $headers6 = [
        'N°', 'IDENTIFICADOR', 'NÚMERO DE OFICIO', 'TÍTULO OBTENIDO', 'TIPO',
        'TIPO DE ESTUDIO', 'INSTITUCIÓN', 'FECHA DE TERMINACIÓN', 'RESOLUCIÓN DE CONVALIDACIÓN',
        'PUNTAJE', 'TIPO DE PRODUCTIVIDAD', 'PROFESORES'
    ];
    $sheet6->fromArray($headers6, NULL, 'A1');
    $sheet6->getStyle('A1:L1')->getFont()->setBold(true);

    $row6 = 2;
    $contador = 1;
    while ($data6 = $result6->fetch_assoc()) {
        // Colocar número consecutivo en la primera columna
        $sheet6->setCellValue('A' . $row6, $contador);

        // El resto de datos desde la columna B
        $sheet6->fromArray(array_values($data6), NULL, 'B' . $row6);

        $row6++;
        $contador++;
    }
}

// *** HOJA 7: TRADUCCIÓN DE LIBROS ***
$sql7 = "
SELECT
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numero_oficio,
    t.fecha_solicitud,
    t.tipo_traduccion,
    t.producto,
    t.numero_profesores,
    t.puntaje,
    t.obs_traduccion,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM
    traduccion_libros t
JOIN
    traduccion_profesor tp ON tp.id_traduccion = t.id_traduccion
JOIN
    tercero ter ON tp.id_profesor = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE t.estado = 'ac' -- SIN MODIFICAR (ya filtra por 'ac', lo que excluye 'an')
";

if (!empty($identificador)) {
    $sql7 .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql7 .= " AND YEAR(t.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

$sql7 .= "
GROUP BY t.id_traduccion, t.numero_oficio, t.fecha_solicitud, t.tipo_traduccion,
         t.producto, t.numero_profesores, t.puntaje, t.obs_traduccion
ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud";

$result7 = $conn->query($sql7);

if ($result7->num_rows > 0) {
    $sheet7 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet7->setTitle('Traducción Libros');
    $hoja_existente = true;

    // Encabezados (ID -> N°)
    $headers7 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD',
        'TIPO DE TRADUCCIÓN', 'PRODUCTO', 'NÚMERO DE PROFESORES',
        'PUNTAJE', 'OBSERVACIONES', 'DETALLES DE PROFESORES'
    ];
    $sheet7->fromArray($headers7, NULL, 'A1');
    $sheet7->getStyle('A1:K1')->getFont()->setBold(true);

    $row7 = 2;
    $contador = 1;
    while ($data7 = $result7->fetch_assoc()) {
        $data7['DETALLES_PROFESORES'] = str_replace('\n', "\n", $data7['DETALLES_PROFESORES']);

        // Contador en la primera columna
        $sheet7->setCellValue('A' . $row7, $contador);

        // Resto de datos desde la columna B
        $sheet7->fromArray(array_values($data7), NULL, 'B' . $row7);

        $row7++;
        $contador++;
    }
}

// *** HOJA 8: INNOVACIÓN ***
$sql8 = "
SELECT
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numero_oficio,
    t.fecha_solicitud,
    t.producto,
    t.impacto,
    t.numero_profesores,
    t.puntaje,
    t.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM
    innovacion t
JOIN
    innovacion_profesor tp ON tp.id_innovacion = t.id_innovacion
JOIN
    tercero ter ON tp.id_profesor = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE t.estado = 'ac' -- SIN MODIFICAR (ya filtra por 'ac', lo que excluye 'an')
";

if (!empty($identificador)) {
    $sql8 .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql8 .= " AND YEAR(t.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

$sql8 .= "
GROUP BY t.id_innovacion, t.numero_oficio, t.fecha_solicitud, t.producto,
         t.impacto, t.numero_profesores, t.puntaje, t.tipo_productividad
ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud";

$result8 = $conn->query($sql8);

if ($result8->num_rows > 0) {
    $sheet8 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet8->setTitle('Innovación');
    $hoja_existente = true;

    // Encabezados (ID -> N°)
    $headers8 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD',
        'PRODUCTO', 'IMPACTO', 'NÚMERO DE PROFESORES', 'PUNTAJE',
        'TIPO DE PRODUCTIVIDAD', 'DETALLES DE PROFESORES'
    ];
    $sheet8->fromArray($headers8, NULL, 'A1');
    $sheet8->getStyle('A1:K1')->getFont()->setBold(true);

    $row8 = 2;
    $contador = 1;
    while ($data8 = $result8->fetch_assoc()) {
        $data8['DETALLES_PROFESORES'] = str_replace('\n', "\n", $data8['DETALLES_PROFESORES']);

        // Contador en la primera columna
        $sheet8->setCellValue('A' . $row8, $contador);

        // Resto de datos desde la columna B
        $sheet8->fromArray(array_values($data8), NULL, 'B' . $row8);

        $row8++;
        $contador++;
    }
}


// *** HOJA 9: PRODUCCIÓN T/S ***
$sql9 = "
SELECT
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numero_oficio,
    t.fecha_solicitud,
    t.productop AS PRODUCTO,
    t.numero_profesores,
    t.puntaje,
    t.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM
    produccion_t_s t
JOIN
    produccionp_profesor tp ON tp.id_produccion = t.id_produccion
JOIN
    tercero ter ON tp.id_profesor = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE t.estado = 'ac' -- SIN MODIFICAR (ya filtra por 'ac', lo que excluye 'an')
";

if (!empty($identificador)) {
    $sql9 .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql9 .= " AND YEAR(t.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

$sql9 .= "
GROUP BY t.id_produccion, t.numero_oficio, t.fecha_solicitud, t.productop,
         t.numero_profesores, t.puntaje, t.tipo_productividad
ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud";

$result9 = $conn->query($sql9);

if ($result9->num_rows > 0) {
    $sheet9 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet9->setTitle('Produccion TS');
    $hoja_existente = true;

    // Encabezados (ID -> N°)
    $headers9 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD',
        'PRODUCTO', 'NÚMERO DE PROFESORES', 'PUNTAJE', 'TIPO DE PRODUCTIVIDAD', 'DETALLES DE PROFESORES'
    ];
    $sheet9->fromArray($headers9, NULL, 'A1');
    $sheet9->getStyle('A1:J1')->getFont()->setBold(true);

    $row9 = 2;
    $contador = 1;
    while ($data9 = $result9->fetch_assoc()) {
        $data9['DETALLES_PROFESORES'] = str_replace('\n', "\n", $data9['DETALLES_PROFESORES']);

        // Insertamos el contador en la primera columna
        $sheet9->setCellValue('A' . $row9, $contador);

        // Insertamos el resto de los datos desde la columna B
        $sheet9->fromArray(array_values($data9), NULL, 'B' . $row9);

        $row9++;
        $contador++;
    }
}


// *** HOJA 10: PUBLICACIONES (BONIFICACIÓN) ***
// SIN MODIFICAR (Tabla publicacion_bon no tiene columna 'estado' en el esquema)
$sql10 = "
SELECT
    t.id AS ID,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.identificador_completo,
    t.numeroOficio,
    t.fecha_solicitud,
    t.tipo_producto,
    t.nombre_revista,
    t.producto,
    t.isbn,
    t.fecha_publicacion,
    t.lugar_publicacion,
    t.autores,
    t.evaluacion1,
    t.evaluacion2,
    t.puntaje,
    t.puntaje_final,
    t.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM
    publicacion_bon t
JOIN
    publicacion_bon_profesor tp ON tp.id_publicacion_bon = t.id
JOIN
    tercero ter ON tp.documento_profesor = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

if (!empty($identificador)) {
    $sql10 .= " AND t.identificador_completo = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql10 .= " AND YEAR(t.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

$sql10 .= "
GROUP BY t.id, t.identificador_completo, t.numeroOficio, t.fecha_solicitud,
         t.tipo_producto, t.nombre_revista, t.producto, t.isbn,
         t.fecha_publicacion, t.lugar_publicacion, t.autores,
         t.evaluacion1, t.evaluacion2, t.puntaje, t.puntaje_final,
         t.tipo_productividad
ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud";

$result10 = $conn->query($sql10);

if ($result10->num_rows > 0) {
    $sheet10 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet10->setTitle('Publicaciones Bonif.');
    $sheet10->getTabColor()->setRGB('A3C1DA');
    $hoja_existente = true;

    // Encabezados (ID -> N°)
    $headers10 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'IDENTIFICADOR COMPLETO',
        'NÚMERO DE OFICIO', 'FECHA DE SOLICITUD', 'TIPO DE PRODUCTO',
        'NOMBRE REVISTA', 'PRODUCTO', 'ISBN', 'FECHA PUBLICACIÓN',
        'LUGAR PUBLICACIÓN', 'AUTORES', 'EVALUACIÓN 1', 'EVALUACIÓN 2',
        'PUNTAJE', 'PUNTAJE FINAL', 'TIPO DE PRODUCTIVIDAD', 'DETALLES DE PROFESORES'
    ];
    $sheet10->fromArray($headers10, NULL, 'A1');
    $sheet10->getStyle('A1:S1')->getFont()->setBold(true);

    $row10 = 2;
    $contador = 1;
    while ($data10 = $result10->fetch_assoc()) {
        $data10['DETALLES_PROFESORES'] = str_replace('\n', "\n", $data10['DETALLES_PROFESORES']);

        // Insertamos contador en la columna A
        $sheet10->setCellValue('A' . $row10, $contador);

        // Insertamos los demás datos desde la columna B
        $sheet10->fromArray(array_values($data10), NULL, 'B' . $row10);

        $row10++;
        $contador++;
    }
}

// *** HOJA 11: DIRECCIÓN DE TESIS ***
// SIN MODIFICAR (Tabla direccion_tesis no tiene columna 'estado' en el esquema)
$sql11 = "
SELECT
    f.nombre_fac_min AS FACULTAD,
    d.depto_nom_propio AS DEPARTAMENTO,
    t.numero_oficio,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS PROFESORES,
    t.titulo_obtenido,
    t.tipo,
    t.nombre_estudiante,
    t.fecha_sustentacion,
    t.fecha_terminacion,
    t.resolucion,
    t.puntaje,
    t.tipo_productividad
FROM
    direccion_tesis t
JOIN
    direccion_t_profesor tp ON tp.id_titulo = t.id
JOIN
    tercero ter ON tp.fk_tercero = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1=1
";

if (!empty($identificador)) {
    $sql11 .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql11 .= " AND YEAR(t.fecha_sustentacion) = '" . $conn->real_escape_string($ano) . "'";
}

$sql11 .= "
GROUP BY t.id, t.numero_oficio, t.titulo_obtenido, t.tipo,
         t.nombre_estudiante, t.fecha_sustentacion,
         t.fecha_terminacion, t.resolucion, t.puntaje, t.tipo_productividad
ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.fecha_sustentacion
";

$result11 = $conn->query($sql11);

if ($result11->num_rows > 0) {
    $sheet11 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet11->setTitle('Dirección de Tesis');
    $sheet11->getTabColor()->setRGB('A3C1DA');
    $hoja_existente = true;

    // Encabezados (ID -> CONTADOR)
    $headers11 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO',
        'PROFESOR(ES)', 'TÍTULO OBTENIDO', 'TIPO', 'NOMBRE ESTUDIANTE',
        'FECHA SUSTENTACIÓN', 'FECHA TERMINACIÓN', 'RESOLUCIÓN',
        'PUNTAJE', 'TIPO DE PRODUCTIVIDAD'
    ];
    $sheet11->fromArray($headers11, NULL, 'A1');
    $sheet11->getStyle('A1:M1')->getFont()->setBold(true);

    // Insertar datos
    $row11 = 2;
    $contador = 1;
    while ($data11 = $result11->fetch_assoc()) {
        $data11['PROFESORES'] = str_replace('\n', "\n", $data11['PROFESORES']);

        // Insertamos contador manual en la primera celda
        $sheet11->setCellValue('A' . $row11, $contador);

        // Insertamos el resto de datos desde la columna B
        $sheet11->fromArray(array_values($data11), NULL, 'B' . $row11);

        $row11++;
        $contador++;
    }
}

// *** HOJA 13: posdoctorales(BONIFICACIÓN) ***
// SIN MODIFICAR (Tabla posdoctoral no tiene columna 'estado' en el esquema)
$sql13 = "
SELECT
    p.id AS ID,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    p.identificador,
    p.numero_oficio,
    p.titulo_obtenido,
    p.institucion,
    p.fecha_terminacion,
    p.puntaje,
    p.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM
    posdoctoral p
JOIN
    posdoctoral_profesor pp ON pp.id_titulo = p.id
JOIN
    tercero ter ON pp.fk_tercero = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

if (!empty($identificador)) {
    $sql13 .= " AND p.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($ano)) {
    $sql13 .= " AND YEAR(p.fecha_terminacion) = '" . $conn->real_escape_string($ano) . "'";
}

$sql13 .= "
GROUP BY p.id, p.identificador, p.numero_oficio, p.titulo_obtenido,
         p.institucion, p.fecha_terminacion, p.puntaje, p.tipo_productividad
ORDER BY f.nombre_fac_min, d.depto_nom_propio, p.fecha_terminacion";

$result13 = $conn->query($sql13);

if ($result13->num_rows > 0) {
    $sheet13 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet13->setTitle('Posdoctorales');
    $sheet13->getTabColor()->setRGB('A3C1DA');
    $hoja_existente = true;

    // Encabezados (ID -> N°)
    $headers13 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'IDENTIFICADOR',
        'NÚMERO DE OFICIO', 'TÍTULO OBTENIDO', 'INSTITUCIÓN',
        'FECHA TERMINACIÓN', 'PUNTAJE', 'TIPO DE PRODUCTIVIDAD', 
        'DETALLES DE PROFESORES'
    ];
    $sheet13->fromArray($headers13, NULL, 'A1');
    $sheet13->getStyle('A1:K1')->getFont()->setBold(true);

    $row13 = 2;
    $contador = 1;
    while ($data13 = $result13->fetch_assoc()) {
        $data13['DETALLES_PROFESORES'] = str_replace('\n', "\n", $data13['DETALLES_PROFESORES']);

        // Insertamos contador en la columna A
        $sheet13->setCellValue('A' . $row13, $contador);

        // Eliminamos el ID del array de datos para que no aparezca duplicado
        unset($data13['ID']);
        
        // Insertamos los demás datos desde la columna B (sin el ID)
        $sheet13->fromArray(array_values($data13), NULL, 'B' . $row13);

        $row13++;
        $contador++;
    }
}

// Lógica final: Si no se creó ninguna hoja, se crea una hoja vacía para evitar errores.
if (!$hoja_existente) {
    $sheet_final = $spreadsheet->getActiveSheet();
    $sheet_final->setTitle('Sin resultados');
    $sheet_final->setCellValue('A1', 'No se encontraron resultados para los filtros aplicados.');
} else {
    // Si se crearon hojas, la primera hoja creada es la activa por defecto.
    // Aquí puedes limpiar la hoja inicial si quedó vacía y se crearon otras después.
    $hoja_inicial = $spreadsheet->getSheet(0);
    if ($hoja_inicial->getTitle() === 'Worksheet' || ($hoja_inicial->getHighestRow() == 1 && empty($hoja_inicial->getCell('A1')->getValue()))) {
        $spreadsheet->removeSheetByIndex(0);
    }
}


// *** HOJA 14: PONENCIAS ***
// SIN MODIFICAR (Tabla ponencias_bon no tiene columna 'estado' en el esquema)
$sql14 = "
SELECT
    f.nombre_fac_min AS FACULTAD,
    d.depto_nom_propio AS DEPARTAMENTO,
    p.numeroOficio AS 'NUMERO_OFICIO',
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
                 ORDER BY ter.documento_tercero SEPARATOR '\n') AS PROFESORES,
    p.producto AS 'TITULO_PONENCIA',
    p.difusion AS 'DIFUSION',
    p.nombre_evento AS 'NOMBRE_EVENTO',
    p.fecha_evento AS 'FECHA_EVENTO',
    p.lugar_evento AS 'LUGAR_EVENTO',
    p.evaluacion1 AS 'EVALUACION_1',
    p.evaluacion2 AS 'EVALUACION_2',
    p.puntaje AS 'DETALLE_CALCULO',
    p.puntaje_final AS 'PUNTAJE_FINAL',
    p.tipo_productividad AS 'TIPO_PRODUCTIVIDAD',
    p.fecha_solicitud AS 'FECHA_SOLICITUD'
FROM
    ponencias_bon p
JOIN
    ponencias_bon_profesor pp ON pp.id_ponencias_bon = p.id
JOIN
    tercero ter ON pp.documento_profesor = ter.documento_tercero
JOIN
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1=1
";

// Aplicar filtros (Asumiendo que $identificador es la base 'AÑO_MES')
if (!empty($identificador)) {
    // Usamos LIKE para que '2024_10' coincida con '2024_10_1', '2024_10_2', etc.
    $sql14 .= " AND p.identificador_completo LIKE '" . $conn->real_escape_string($identificador) . "%'";
}
if (!empty($ano)) {
    // Filtramos por el año del evento
    $sql14 .= " AND YEAR(p.fecha_evento) = '" . $conn->real_escape_string($ano) . "'";
}

$sql14 .= "
GROUP BY p.id
ORDER BY f.nombre_fac_min, d.depto_nom_propio, p.fecha_evento
";

$result14 = $conn->query($sql14);

if ($result14->num_rows > 0) {
    // Crear la hoja solo si no existe o si ya existe otra (para $hoja_existente)
    $sheet14 = $hoja_existente ? $spreadsheet->createSheet() : $spreadsheet->getActiveSheet();
    $sheet14->setTitle('Ponencias');
    $sheet14->getTabColor()->setRGB('DAA3A3'); // Un color diferente (Rojo pálido)
    $hoja_existente = true;

    // Encabezados
    $headers14 = [
        'N°', 'FACULTAD', 'DEPARTAMENTO', 'NÚMERO DE OFICIO',
        'PROFESOR(ES)', 'TÍTULO PONENCIA', 'DIFUSIÓN', 'NOMBRE EVENTO',
        'FECHA EVENTO', 'LUGAR EVENTO', 'EVALUACIÓN 1', 'EVALUACIÓN 2',
        'DETALLE CÁLCULO', 'PUNTAJE FINAL', 'TIPO DE PRODUCTIVIDAD',
        'FECHA SOLICITUD'
    ];
    $sheet14->fromArray($headers14, NULL, 'A1');
    $sheet14->getStyle('A1:P1')->getFont()->setBold(true); // Ajustado hasta la P

    // Insertar datos
    $row14 = 2;
    $contador14 = 1;
    while ($data14 = $result14->fetch_assoc()) {
        // Asegurarse de que los saltos de línea en PROFESORES se interpreten
        $data14['PROFESORES'] = str_replace('\n', "\n", $data14['PROFESORES']);

        // Insertamos contador manual en la primera celda
        $sheet14->setCellValue('A' . $row14, $contador14);

        // Insertamos el resto de datos (de $data14) desde la columna B
        // Usamos array_values para asegurar el orden
        $sheet14->fromArray(array_values($data14), NULL, 'B' . $row14);

        // --- Ajuste importante para celdas con saltos de línea ---
        // Habilitar 'wrap text' para la columna de Profesores (Columna E)
        $sheet14->getStyle('E' . $row14)->getAlignment()->setWrapText(true);
        
        $row14++;
        $contador14++;
    }
    
    // Auto-ajustar columnas (opcional, pero recomendado)
    foreach (range('A', 'P') as $col) {
        if ($col != 'E') { // No auto-ajustar la de profesores, suele ser muy ancha
            $sheet14->getColumnDimension($col)->setAutoSize(true);
        }
    }
    $sheet14->getColumnDimension('E')->setWidth(40); // Ancho fijo para profesores
}

// Nombre del archivo Excel
$fileName = "Reporte_" . ($identificador ?? date('Y-m-d')) . ".xlsx";

// Enviar el archivo como descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheet.ml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el archivo Excel y enviarlo al navegador
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Cerrar la conexión a la base de datos
$conn->close();
exit;
?>