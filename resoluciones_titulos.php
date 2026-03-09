<?php
/**
 * Generador de Resoluciones CIARP - Títulos de Posgrados (Nacional / Exterior)
 * Versión Inteligente (Plan A - Enterprise): 
 * - Estrictamente Individual (1 resolución por Docente).
 * - Tablas dinámicas (N° ACTA para nacional, CONVALIDACIÓN para exterior).
 * - Detección automática de literal (C = Doctorado, B = Maestría).
 * - VARIABLES DINÁMICAS (Firmas, Fechas, Resoluciones guardadas en BD).
 * - Puntos limpios (floatval) y sin Fecha de Terminación visible.
 */
require 'conn.php';
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

// --- 1. FUNCIÓN DE REDACCIÓN NATURAL ---
if (!function_exists('unirLista')) {
    function unirLista($items) {
        if (count($items) == 0) return "";
        if (count($items) == 1) return $items[0];
        if (count($items) == 2) return $items[0] . " y " . $items[1];
        $ultimo = array_pop($items);
        return implode(", ", $items) . " y " . $ultimo;
    }
}

$identificador = isset($_GET['cuadro_identificador_titulo']) ? trim($_GET['cuadro_identificador_titulo']) : '';if (empty($identificador)) die("Identificador requerido.");

// =========================================================================
// RECEPCIÓN Y GUARDADO DE VARIABLES DINÁMICAS (FORMULARIO)
// =========================================================================
$num_resolucion = isset($_GET['num_resolucion']) && trim($_GET['num_resolucion']) !== '' ? trim($_GET['num_resolucion']) : '____';

$fecha_input = isset($_GET['fecha_resolucion']) ? trim($_GET['fecha_resolucion']) : '';
$textoFecha = "____";
$textoMes = "________";
$textoAno = "____";

if (!empty($fecha_input)) {
    $timestamp = strtotime($fecha_input);
    $textoFecha = date('d', $timestamp);
    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $textoMes = $meses[date('n', $timestamp) - 1];
    $textoAno = date('Y', $timestamp);
}

$nombre_vicerrector = isset($_GET['nombre_vicerrector']) && trim($_GET['nombre_vicerrector']) !== '' ? trim($_GET['nombre_vicerrector']) : 'AIDA PATRICIA GONZÁLEZ NIEVA';
$genero_vicerrector = isset($_GET['genero_vicerrector']) ? trim($_GET['genero_vicerrector']) : 'F';

if ($genero_vicerrector === 'M') {
    $cargo_vicerrector = "Vicerrector Académico";
    $cargo_presidente = "Presidente CIARP";
} else {
    $cargo_vicerrector = "Vicerrectora Académica";
    $cargo_presidente = "Presidenta CIARP";
}

$nombre_reviso = isset($_GET['nombre_reviso']) && trim($_GET['nombre_reviso']) !== '' ? trim($_GET['nombre_reviso']) : 'Marjhory Castro';
$nombre_elaboro = isset($_GET['nombre_elaboro']) && trim($_GET['nombre_elaboro']) !== '' ? trim($_GET['nombre_elaboro']) : 'Elizete Rivera';

// GUARDADO SILENCIOSO EN LA BASE DE DATOS
if (isset($_GET['num_resolucion']) || isset($_GET['fecha_resolucion'])) {
    $db_num_res = (isset($_GET['num_resolucion']) && trim($_GET['num_resolucion']) !== '') ? trim($_GET['num_resolucion']) : null;
    $db_fecha_res = (isset($_GET['fecha_resolucion']) && trim($_GET['fecha_resolucion']) !== '') ? trim($_GET['fecha_resolucion']) : null;
    
    $sql_update = "UPDATE titulos SET 
                    num_resolucion = ?, 
                    fecha_resolucion = ?, 
                    nombre_vicerrector = ?, 
                    genero_vicerrector = ?, 
                    nombre_reviso = ?, 
                    nombre_elaboro = ? 
                   WHERE identificador = ?";
                   
    if ($stmt_upd = $conn->prepare($sql_update)) {
        $stmt_upd->bind_param("sssssss", $db_num_res, $db_fecha_res, $nombre_vicerrector, $genero_vicerrector, $nombre_reviso, $nombre_elaboro, $identificador);
        $stmt_upd->execute();
        $stmt_upd->close();
    }
}
// =========================================================================

// --- 2. CONSULTA PLANA ---
$sql = "
    SELECT 
        t.id_titulo,
        t.numero_oficio,
        t.titulo_obtenido,
        t.tipo,
        t.tipo_estudio,
        t.institucion,
        t.fecha_terminacion,
        t.resolucion_convalidacion,
        t.no_acta, 
        t.puntaje,
        ter.documento_tercero,
        ter.nombre_completo as profe_nombre,
        ter.sexo,
        ter.email,
        ter.vincul AS vinculacion, 
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento
    FROM titulos t
    JOIN titulo_profesor tp ON t.id_titulo = tp.id_titulo
    JOIN tercero ter ON tp.fk_tercero = ter.documento_tercero
    JOIN deparmanentos d ON ter.fk_depto = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE t.identificador = '" . $conn->real_escape_string($identificador) . "'
    AND (t.estado_titulo IS NULL OR t.estado_titulo <> 'an')
    ORDER BY ter.nombre_completo, t.id_titulo
";

$res = $conn->query($sql);
if ($res->num_rows === 0) die("No se encontraron registros activos para el identificador: " . htmlspecialchars($identificador));

// --- 3. MOTOR DE CLASIFICACIÓN INDIVIDUAL ---
$docentes_records = [];

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    if (!isset($docentes_records[$cc])) {
        $docentes_records[$cc] = [
            'info' => [
                'documento_tercero' => $cc,
                'profe_nombre' => $row['profe_nombre'],
                'sexo' => $row['sexo'],
                'email' => $row['email'],
                'vinculacion' => $row['vinculacion'],
                'departamento' => $row['departamento'],
                'facultad' => $row['facultad']
            ],
            'titulos' => []
        ];
    }
    $id_tit = $row['id_titulo'];
    $exists = false;
    foreach ($docentes_records[$cc]['titulos'] as $existing_tit) {
        if ($existing_tit['id_titulo'] == $id_tit) {
            $exists = true; break;
        }
    }
    if (!$exists) {
        $docentes_records[$cc]['titulos'][] = $row;
    }
}

// --- 4. CONFIGURACIÓN BASE DE WORD ---
$phpWord = new PhpWord();
$phpWord->addFontStyle('StyleBold', ['bold' => true, 'name' => 'Arial', 'size' => 11]);
$phpWord->addFontStyle('StyleNormal', ['name' => 'Arial', 'size' => 11]);
$styleTable = ['borderSize' => 6, 'borderColor' => '000000', 'cellMarginTop' => 20, 'cellMarginBottom' => 20, 'cellMarginLeft' => 80, 'cellMarginRight' => 80]; 
$phpWord->addFontStyle('FontTableBold', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
$phpWord->addFontStyle('FontTableNormal', ['name' => 'Arial', 'size' => 9]);
$phpWord->addParagraphStyle('ParaTable', ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0]);

// --- 5. BUCLE DE GENERACIÓN: UNA RESOLUCIÓN POR DOCENTE ---
foreach ($docentes_records as $cc => $data) {
    
    $docente = $data['info'];
    $titulos = $data['titulos'];

    // Lógica de Género
    $sexo = strtoupper(trim($docente['sexo'] ?? ''));
    if ($sexo === 'F') {
        $txtUnProf = "una profesora";
        $txtProfesor = "La profesora";
        $txtIdentificado = "identificada";
        $txtAdscrito = "adscrita";
        $txtDelProfesor = "de la profesora";
        $txtAlProfesor = "a la profesora";
    } else {
        $txtUnProf = "un profesor";
        $txtProfesor = "El profesor";
        $txtIdentificado = "identificado";
        $txtAdscrito = "adscrito";
        $txtDelProfesor = "del profesor";
        $txtAlProfesor = "al profesor";
    }

    // Nombres Propios para Departamento y Facultad
    $nomDepto = mb_convert_case(mb_strtolower(trim($docente['departamento']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nomDepto = str_replace([' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], $nomDepto);
    
    $nomFacultad = mb_convert_case(mb_strtolower(trim($docente['facultad']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nomFacultad = str_replace([' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], $nomFacultad);

    $txtVinculacion = mb_strtolower(trim($docente['vinculacion']), 'UTF-8');

    // Procesamiento de Oficios, Puntajes y Lógica de Literales (C o B)
    $oficios = [];
    $puntajeTotal = 0;
    $puntosArray = [];
    $literales = [];

    foreach ($titulos as $tit) {
        $oficios[] = trim($tit['numero_oficio']);
        
        $pt = floatval($tit['puntaje']); // floatval quita el .00 automáticamente
        $puntajeTotal += $pt;
        $puntosArray[] = $pt;

        $tipo_estudio = mb_strtolower(trim($tit['tipo_estudio']), 'UTF-8');
        if (strpos($tipo_estudio, 'doctorado') !== false) {
            $literales[] = "c";
        } elseif (strpos($tipo_estudio, 'maestria') !== false || strpos($tipo_estudio, 'maestría') !== false) {
            $literales[] = "b";
        } else {
            $literales[] = "a"; 
        }
    }

    $textoOficios = unirLista(array_values(array_unique(array_filter($oficios))));
    
    $literales = array_unique($literales); 
    sort($literales);
    $textoLiteral = unirLista($literales);
    $palabraLiteral = count($literales) > 1 ? "sus literales" : "su literal";
    $palabraLiteralP = count($literales) > 1 ? "literales" : "literal";

    $section = $phpWord->addSection([
        'paperSize' => 'Folio', 'marginTop' => 3000, 'marginLeft' => 1701, 'marginRight' => 1701, 'marginBottom' => 1417, 'footerHeight' => 500
    ]);

    $header = $section->addHeader();
    $tableHeader = $header->addTable(); $tableHeader->addRow();
    $tableHeader->addCell(8000)->addImage('img/encabezadob.png', ['width' => 170, 'alignment' => Jc::LEFT]);

    $footer = $section->addFooter();
    $tableFooter = $footer->addTable(); $tableFooter->addRow();
    $tableFooter->addCell(10000)->addImage('img/PIEb.png', ['width' => 430, 'alignment' => Jc::LEFT]);

    // --- IMPRESIÓN DEL DOCUMENTO ---
    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº {$num_resolucion} DE {$textoAno}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$textoFecha} de {$textoMes})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);

    $section->addText("Por la cual se reconocen puntos a la Base – Salarial a {$txtUnProf} de la Universidad del Cauca, por concepto de Título de Posgrado.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(0);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(0);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);

    $section->addText("El Estatuto del profesor Universitario – Acuerdo 024 de 1993, reglamenta las funciones del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, establece en el capítulo II, artículo 7 el reconocimiento y puntajes por concepto de títulos correspondientes a estudios universitarios, previendo en su numeral 2, {$palabraLiteral} {$textoLiteral}, títulos de posgrado.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$txtProfesor} de {$txtVinculacion} ", 'StyleNormal');
    $c1->addText(mb_strtoupper($docente['profe_nombre'], 'UTF-8'), 'StyleBold');
    $c1->addText(" {$txtIdentificado} con cédula de ciudadanía N° {$docente['documento_tercero']}, {$txtAdscrito} al Departamento de {$nomDepto} de la {$nomFacultad}, solicitó el reconocimiento de puntos que modifican su base salarial por el siguiente título de Posgrado:", 'StyleNormal');
    $section->addTextBreak(0);

    // --- TABLAS DE DETALLES ---
    foreach ($titulos as $ti) {
        $table = $section->addTable($styleTable);
        
        $table->addRow();
        $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("TÍTULO OBTENIDO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($ti['titulo_obtenido'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("TIPO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($ti['tipo'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("TIPO DE ESTUDIO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($ti['tipo_estudio'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("INSTITUCIÓN", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($ti['institucion'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        // FECHA DE TERMINACIÓN COMENTADA
        /*
        $table->addRow();
        $table->addCell(3000)->addText("FECHA DE TERMINACIÓN", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($ti['fecha_terminacion'], 'FontTableNormal', 'ParaTable');
        */

        if (strtoupper(trim($ti['tipo'])) === 'EXTERIOR') {
            $table->addRow();
            $table->addCell(3000)->addText("RESOLUCIÓN DE CONVALIDACIÓN", 'FontTableBold', 'ParaTable');
            $resolConvalidacion = !empty($ti['resolucion_convalidacion']) ? mb_strtoupper($ti['resolucion_convalidacion'], 'UTF-8') : "NO ESPECIFICADA";
            $table->addCell(6000)->addText($resolConvalidacion, 'FontTableNormal', 'ParaTable');
            
            $table->addRow();
            $table->addCell(3000)->addText("TÍTULO CONVALIDADO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText(mb_strtoupper($ti['titulo_obtenido'], 'UTF-8'), 'FontTableNormal', 'ParaTable');
        } else {
            $table->addRow();
            $table->addCell(3000)->addText("N° ACTA", 'FontTableBold', 'ParaTable');
            $noActa = !empty($ti['no_acta']) ? mb_strtoupper($ti['no_acta'], 'UTF-8') : "NO REGISTRA";
            $table->addCell(6000)->addText($noActa, 'FontTableNormal', 'ParaTable');
        }

        $table->addRow();
        $table->addCell(3000)->addText("RECONOCER", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(floatval($ti['puntaje']) . " PUNTOS", 'FontTableBold', 'ParaTable');
        
        $section->addTextBreak(0);
    }

    // ELIMINADO EL "de ____" AL FINAL DEL PÁRRAFO
    $section->addText("El Comité de Personal Docente de la {$nomFacultad}, remitió al CIARP mediante oficio N° {$textoOficios}, la solicitud y documentación para lo concerniente al otorgamiento de puntos, de conformidad con lo previsto en el Decreto 1279 de 2002, artículo 7, numeral 2, {$palabraLiteral} {$textoLiteral}.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("Los documentos, fueron analizados por el Comité Interno de Asignación y Reconocimiento de Puntaje en sesión del {$textoFecha} de {$textoMes} de {$textoAno}, con fundamento en el concepto del Comité de Personal Docente de su facultad, y decidió reconocer {$puntajeTotal} puntos como lo dispone la norma.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
    $runR1->addText("Reconocer {$puntajeTotal} puntos a la base salarial {$txtDelProfesor} de {$txtVinculacion} ", 'StyleNormal');
    $runR1->addText(mb_strtoupper($docente['profe_nombre'], 'UTF-8'), 'StyleBold');
    $runR1->addText(" {$txtIdentificado} con cédula de ciudadanía N° {$docente['documento_tercero']}, {$txtAdscrito} al Departamento de {$nomDepto} de la {$nomFacultad}, conforme a lo mencionado en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 7, numeral 2, {$palabraLiteralP} {$textoLiteral}, que establece el reconocimiento de Títulos de Posgrados; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');
    $section->addTextBreak(0);

    $correoStr = !empty($docente['email']) ? $docente['email'] : "No registrado";
    
    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
    // CORRECCIÓN "días hábiles"
    $runR2->addText("Notificar el presente acto administrativo {$txtAlProfesor}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a su autorización expresa en el formato PM-FO-4-FOR-4, al correo {$correoStr} advirtiéndole que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días hábiles siguientes a la fecha de la notificación.", 'StyleNormal');
    $section->addTextBreak(0);

    $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR3->addText("ARTÍCULO TERCERO. ", 'StyleBold');
    $runR3->addText("Comunicar el presente acto administrativo a la División de Gestión del Talento Humano, para efectos del reconocimiento y efecto en la liquidación de la nómina.", 'StyleNormal');
    $section->addTextBreak(0);

    // FIRMAS VARIABLES
  // FIRMAS VARIABLES
    $section->addText("Se expide en Popayán, el {$textoFecha} de {$textoMes} de {$textoAno}.", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("COMUNÍQUESE, NOTIFÍQUESE Y CÚMPLASE", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(2);
    
    // Aquí definimos los estilos sin espaciado
    $styleFirmaCenter = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
    $styleFirmaLeft = ['spaceAfter' => 0];
    $fontFirmaPequena = ['name' => 'Arial', 'size' => 8, 'italic' => true]; 
    
    // ¡AQUÍ ESTÁ EL CAMBIO! Aplicamos la variable $styleFirmaCenter a las 3 líneas
    $section->addText(mb_strtoupper($nombre_vicerrector, 'UTF-8'), 'StyleBold', $styleFirmaCenter);
    $section->addText($cargo_vicerrector, 'StyleNormal', $styleFirmaCenter);
    $section->addText($cargo_presidente, 'StyleNormal', $styleFirmaCenter);
    
    $section->addTextBreak(1); // Espacio entre Vice y Elaboró

    // Imprimir Revisó y Elaboró (alineadas a la izquierda, pegadas, tamaño 8 y cursiva)
    $section->addText("Revisó: " . $nombre_reviso, $fontFirmaPequena, $styleFirmaLeft);
    $section->addText("Elaboró: " . $nombre_elaboro, $fontFirmaPequena, $styleFirmaLeft);

    $section->addPageBreak();
}
// --- 6. DESCARGA SEGURA ---
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Titulos_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>