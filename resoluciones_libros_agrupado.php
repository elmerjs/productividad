<?php
/**
 * Generador de Resoluciones CIARP - Libros (CONSOLIDADO MASIVO)
 * - 1 Sola Resolución para TODOS los docentes del identificador.
 * - Tabla Resumen Inicial.
 * - Agrupación por Facultad -> Departamento -> Libro.
 * - Resolutiva estructurada en TABLA.
 * - Gramática dinámica global para literales (C y/o D).
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

$identificador = isset($_GET['cuadro_identificador_libro']) ? trim($_GET['cuadro_identificador_libro']) : '';
if (empty($identificador)) die("Identificador requerido.");

// --- 2. CONSULTA PLANA ---
$sql = "
    SELECT 
        l.id_libro,
        l.numero_oficio,
        l.tipo_libro,
        l.producto AS titulo_libro,
        l.nombre_editorial,
        l.isbn,
        l.mes_ano_edicion AS ano_publicacion,
        l.tiraje AS ejemplares, 
        l.autores AS numero_autores,
        l.evaluacion_1,
        l.evaluacion_2,
        l.puntaje_final AS puntaje,
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo,
        t.email,
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento
    FROM libros l
    JOIN libro_profesor lp ON l.id_libro = lp.id_libro
    JOIN tercero t ON lp.id_profesor = t.documento_tercero
    JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE l.identificador = '" . $conn->real_escape_string($identificador) . "'
    AND (l.estado IS NULL OR l.estado <> 'an')
    ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.nombre_completo
";

$res = $conn->query($sql);
if ($res->num_rows === 0) die("No se encontraron registros activos para el identificador: " . htmlspecialchars($identificador));

// --- 3. ESTRUCTURACIÓN DE DATOS MASIVOS ---
$resumen_docentes = [];
$books_by_dept = [];
$teachers_by_dept = [];

$all_emails = [];
$all_female = true;

// Variables para control de Literales C y D globales
$globalConceptos = [];
$globalLiterales = [];

// Función para capitalizar correctamente Departamentos y Facultades
function formatearNombrePropio($texto) {
    $texto = mb_convert_case(mb_strtolower(trim($texto), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    return str_replace(
        [' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], 
        [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], 
        $texto
    );
}

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    $id_l = $row['id_libro'];
    $fac = formatearNombrePropio($row['facultad']);
    $dept = formatearNombrePropio($row['departamento']);
    
    // Género global
    if (strtoupper(trim($row['sexo'] ?? '')) !== 'F') {
        $all_female = false;
    }

    // Correos globales
    if (!empty($row['email'])) {
        $all_emails[] = strtolower(trim($row['email']));
    }

    // Detección de literales (Texto vs Investigación)
    $tipoLibro = mb_strtolower(trim($row['tipo_libro']), 'UTF-8');
    if (strpos($tipoLibro, 'texto') !== false) {
        $globalConceptos[] = "libros de texto";
        $globalLiterales[] = "d";
    } else {
        $globalConceptos[] = "libros que resultan de una labor de investigación";
        $globalLiterales[] = "c";
    }

    // 1. Datos para la Tabla Resumen
    if (!isset($resumen_docentes[$cc])) {
        $resumen_docentes[$cc] = [
            'nombre' => mb_convert_case(mb_strtolower($row['profe_nombre'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8'),
            'cc' => $cc,
            'depto_fac' => $dept . " / " . $fac,
            'oficio' => $row['numero_oficio']
        ];
    }

    // 2. Agrupación por Libro (Para los Considerandos)
    if (!isset($books_by_dept[$fac][$dept][$id_l])) {
        $books_by_dept[$fac][$dept][$id_l] = [
            'libro' => $row,
            'profesores' => []
        ];
    }
    $books_by_dept[$fac][$dept][$id_l]['profesores'][$cc] = $row;

    // 3. Agrupación por Profesor (Para construir los "Packs" Resolutivos)
    if (!isset($teachers_by_dept[$fac][$dept][$cc])) {
        $teachers_by_dept[$fac][$dept][$cc] = [
            'info' => $row,
            'libros' => []
        ];
    }
    
    // Evitar duplicar el mismo libro en el array del profe
    $exists = false;
    foreach ($teachers_by_dept[$fac][$dept][$cc]['libros'] as $b) {
        if ($b['id_libro'] == $id_l) { $exists = true; break; }
    }
    if (!$exists) {
        $teachers_by_dept[$fac][$dept][$cc]['libros'][] = $row;
    }
}

// 4. Construir los "Packs" Resolutivos
$resuelve_packs = [];
foreach ($teachers_by_dept as $fac => $depts) {
    foreach ($depts as $dept => $profesores) {
        foreach ($profesores as $cc => $data) {
            $book_ids = array_map(function($b) { return $b['id_libro']; }, $data['libros']);
            sort($book_ids);
            $key = implode('-', $book_ids);
            
            if (!isset($resuelve_packs[$fac][$dept][$key])) {
                $resuelve_packs[$fac][$dept][$key] = [
                    'profesores' => [],
                    'libros' => $data['libros']
                ];
            }
            $resuelve_packs[$fac][$dept][$key]['profesores'][] = $data['info'];
        }
    }
}

// 5. Procesar variables globales de gramática (Literales)
$globalConceptos = array_values(array_unique($globalConceptos));
$globalLiterales = array_unique($globalLiterales); 
sort($globalLiterales);

$strGlobalConceptos = unirLista($globalConceptos);
$strGlobalLiterales = unirLista($globalLiterales);
$palabraLiteralG = count($globalLiterales) > 1 ? "sus literales" : "su literal";
$palabraLiteralGP = count($globalLiterales) > 1 ? "literales" : "literal";

// --- 4. CONFIGURACIÓN DE WORD ---
$phpWord = new PhpWord();
$phpWord->addFontStyle('StyleBold', ['bold' => true, 'name' => 'Arial', 'size' => 11]);
$phpWord->addFontStyle('StyleNormal', ['name' => 'Arial', 'size' => 11]);
$styleTable = ['borderSize' => 6, 'borderColor' => '000000', 'cellMarginTop' => 40, 'cellMarginBottom' => 40, 'cellMarginLeft' => 80, 'cellMarginRight' => 80]; 
$phpWord->addFontStyle('FontTableBold', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
$phpWord->addFontStyle('FontTableNormal', ['name' => 'Arial', 'size' => 9]);
$phpWord->addParagraphStyle('ParaTable', ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0]);

$textoFecha = "____"; 
$textoMes = "________"; 
$textoAno = "____";

$txtProfesoresGlobal = $all_female ? "profesoras" : "profesores";
$txtLosDocentes = $all_female ? "Las docentes relacionadas" : "Los docentes relacionados";

// --- 5. RENDERIZADO DEL DOCUMENTO ---
$section = $phpWord->addSection([
    'paperSize' => 'Folio', 'marginTop' => 3000, 'marginLeft' => 1701, 'marginRight' => 1701, 'marginBottom' => 1417, 'footerHeight' => 500
]);

$header = $section->addHeader();
$tableHeader = $header->addTable(); $tableHeader->addRow();
$tableHeader->addCell(8000)->addImage('img/encabezadob.png', ['width' => 170, 'alignment' => Jc::LEFT]);

$footer = $section->addFooter();
$tableFooter = $footer->addTable(); $tableFooter->addRow();
$tableFooter->addCell(10000)->addImage('img/PIEb.png', ['width' => 430, 'alignment' => Jc::LEFT]);

// Encabezado
$section->addText("4-4.5", 'StyleNormal');
$section->addText("RESOLUCIÓN CIARP Nº ____ de {$textoAno}", 'StyleBold', ['alignment' => Jc::CENTER]);
$section->addText("({$textoFecha} de {$textoMes})", 'StyleNormal', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);

// TÍTULO DINÁMICO
$section->addText("Por la cual se reconocen puntos a la Base –Salarial a {$txtProfesoresGlobal} de la Universidad del Cauca, por concepto de {$strGlobalConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);
$section->addTextBreak(1);

$section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
$section->addTextBreak(1);
$section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);

$section->addText("El Estatuto del profesor Universitario – Acuerdo 024 de 1993, reglamenta las funciones del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);

// CONSIDERANDO DECRETO (Dinámico C/D)
$section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en {$palabraLiteralG} {$strGlobalLiterales}, topes por producción en {$strGlobalConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

$section->addText("Que {$txtLosDocentes} a continuación, debidamente vinculados a la planta de personal de la Universidad, elevaron ante los respectivos Comités de Personal de Facultad la solicitud de reconocimiento y asignación de puntos por productividad académica, de conformidad con lo establecido en el Decreto 1279 de 2002. Que, tras verificar el cumplimiento de los requisitos formales, las unidades académicas remitieron los expedientes a este Comité (CIARP) para su evaluación técnica y decisión final, mediante los oficios que se detallan en el siguiente cuadro:", 'StyleNormal', ['alignment' => Jc::BOTH]);
$section->addTextBreak(1);

// --- TABLA RESUMEN INICIAL ---
$tableResumen = $section->addTable($styleTable);
$tableResumen->addRow();
$tableResumen->addCell(3000)->addText("Docente", 'FontTableBold', 'ParaTable');
$tableResumen->addCell(2000)->addText("Identificación (C.C.)", 'FontTableBold', 'ParaTable');
$tableResumen->addCell(3500)->addText("Departamento / Facultad", 'FontTableBold', 'ParaTable');
$tableResumen->addCell(2000)->addText("Oficio de Remisión", 'FontTableBold', 'ParaTable');

foreach ($resumen_docentes as $doc) {
    $tableResumen->addRow();
    $tableResumen->addCell(3000)->addText($doc['nombre'], 'FontTableNormal', 'ParaTable');
    $tableResumen->addCell(2000)->addText($doc['cc'], 'FontTableNormal', 'ParaTable');
    $tableResumen->addCell(3500)->addText($doc['depto_fac'], 'FontTableNormal', 'ParaTable');
    $tableResumen->addCell(2000)->addText($doc['oficio'], 'FontTableNormal', 'ParaTable');
}
$section->addTextBreak(1);

// CONSIDERANDO DE ANÁLISIS (Dinámico C/D)
$section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$textoFecha} de {$textoMes} de {$textoAno}, previo a la asignación de los puntajes correspondientes y con fundamento en el concepto del Comité de Personal Docente de las facultades antes mencionadas, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, {$palabraLiteralGP} {$strGlobalLiterales}, que establece el reconocimiento de {$strGlobalConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

$section->addText("Decidiéndose por el citado Comité otorgar los puntajes que a continuación se enuncian:", 'StyleNormal');
$section->addText("Puntaje por base salarial:", 'StyleBold');
$section->addTextBreak(1);

// --- IMPRESIÓN DE TABLAS DETALLADAS POR FACULTAD -> DEPTO -> LIBRO ---
foreach ($books_by_dept as $fac => $depts) {
    $section->addText("FACULTAD DE " . mb_strtoupper($fac, 'UTF-8'), 'StyleBold');
    
    foreach ($depts as $dept => $libros) {
        $section->addText("DEPARTAMENTO DE " . mb_strtoupper($dept, 'UTF-8'), 'StyleBold');
        
        foreach ($libros as $data_libro) {
            $nombresArr = [];
            foreach ($data_libro['profesores'] as $p) {
                $nombresArr[] = mb_strtoupper($p['profe_nombre'], 'UTF-8') . " C.C " . $p['documento_tercero'];
            }
            $section->addText(implode(" // ", $nombresArr), 'StyleBold');
            
            $lib = $data_libro['libro'];
            $table = $section->addTable($styleTable);
            
            $table->addRow();
            $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("NO. OFICIO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($lib['numero_oficio'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $tipoStr = !empty($lib['tipo_libro']) ? mb_strtoupper($lib['tipo_libro'], 'UTF-8') : "INVESTIGACIÓN";
            $table->addCell(3000)->addText("LIBRO DE " . $tipoStr, 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText(mb_strtoupper($lib['titulo_libro'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("ISBN", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($lib['isbn'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("EDITORIAL", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText(mb_strtoupper($lib['nombre_editorial'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("AÑO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($lib['ano_publicacion'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("EJEMPLARES", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($lib['ejemplares'] ?? '0', 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("AUTORES", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($lib['numero_autores'], 'FontTableNormal', 'ParaTable');

            $ev1 = floatval($lib['evaluacion_1']);
            $ev2 = floatval($lib['evaluacion_2']);
            $suma = $ev1 + $ev2;
            $promedio = $suma / 2;
            $textoEvaluacion = "{$ev1} + {$ev2} = {$suma} / 2 = {$promedio}%";

            $table->addRow();
            $table->addCell(3000)->addText("EVALUACIONES", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($textoEvaluacion, 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("ASIGNAR", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($lib['puntaje'] . " PUNTOS", 'FontTableBold', 'ParaTable');

            $section->addTextBreak(1);
        }
    }
}

// --- RESOLUTIVA (CONVERTIDA EN TABLA Y CON LITERALES DINÁMICOS) ---
$section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);

$runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
$runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
$runR1->addText("Reconocer puntos a la base salarial de los {$txtProfesoresGlobal} relacionados a continuación, conforme al producto mencionado en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, {$palabraLiteralGP} {$strGlobalLiterales}, que establece el reconocimiento de {$strGlobalConceptos}; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');

$section->addText("Puntaje por base salarial:", 'StyleBold');
$section->addTextBreak(1);

// Creación de la Tabla de Resoluciones
$tableResolutiva = $section->addTable($styleTable);
$tableResolutiva->addRow();
$tableResolutiva->addCell(2500)->addText("FACULTAD", 'FontTableBold', 'ParaTable');
$tableResolutiva->addCell(2500)->addText("DEPARTAMENTO", 'FontTableBold', 'ParaTable');
$tableResolutiva->addCell(3500)->addText("DOCENTE(S)", 'FontTableBold', 'ParaTable');
$tableResolutiva->addCell(1500)->addText("CÉDULA(S)", 'FontTableBold', 'ParaTable');
$tableResolutiva->addCell(2000)->addText("PUNTOS A RECONOCER", 'FontTableBold', 'ParaTable');

foreach ($resuelve_packs as $fac => $depts) {
    foreach ($depts as $dept => $packs) {
        foreach ($packs as $pack) {
            $isMultipleProf = count($pack['profesores']) > 1;
            
            // Calcular puntos para la columna Puntos
            $ptsArray = [];
            $totalPts = 0;
            foreach ($pack['libros'] as $b) {
                $ptsArray[] = $b['puntaje'];
                $totalPts += floatval($b['puntaje']);
            }
            
            $strCadaUno = $isMultipleProf ? " A CADA UNO." : ".";

            $tableResolutiva->addRow();
            
            // Columnas Facultad y Departamento
            $tableResolutiva->addCell(2500)->addText(mb_strtoupper($fac, 'UTF-8'), 'FontTableNormal', 'ParaTable');
            $tableResolutiva->addCell(2500)->addText(mb_strtoupper($dept, 'UTF-8'), 'FontTableNormal', 'ParaTable');
            
            // Columnas Docente(s) y Cédula(s)
            $cellNombres = $tableResolutiva->addCell(3500);
            $cellCedulas = $tableResolutiva->addCell(1500);
            
            foreach ($pack['profesores'] as $p) {
                $cellNombres->addText(mb_strtoupper($p['profe_nombre'], 'UTF-8'), 'FontTableNormal', 'ParaTable');
                $cellCedulas->addText($p['documento_tercero'], 'FontTableNormal', 'ParaTable');
            }
            
            // Columna Puntos a Reconocer (Con saltos de línea automáticos)
            $cellPuntos = $tableResolutiva->addCell(2000);
            if (count($ptsArray) > 1) {
                $cellPuntos->addText(implode(", ", $ptsArray) . " PUNTOS.", 'FontTableBold', 'ParaTable');
                $cellPuntos->addText("TOTAL: " . $totalPts . " PUNTOS" . $strCadaUno, 'FontTableBold', 'ParaTable');
            } else {
                $cellPuntos->addText($totalPts . " PUNTOS" . $strCadaUno, 'FontTableBold', 'ParaTable');
            }
        }
    }
}

$section->addTextBreak(1);
$runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
$runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
$runR2->addText("Los puntajes asignados a los {$txtProfesoresGlobal}, tendrán efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, {$palabraLiteralGP} {$strGlobalLiterales}, del Decreto 1279 de 2002.", 'StyleNormal');

$strGlobalEmails = unirLista(array_values(array_unique($all_emails)));
if (empty($strGlobalEmails)) $strGlobalEmails = "los correos institucionales correspondientes";

$runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
$runR3->addText("ARTÍCULO TERCERO. ", 'StyleBold');
$runR3->addText("Notificar el presente acto administrativo a los {$txtProfesoresGlobal}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a su autorización expresa en el formato PM-FO-4-FOR-4, al correo {$strGlobalEmails}; advirtiéndoles que contra éstas procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días siguientes a la fecha de la notificación.", 'StyleNormal');

$runR4 = $section->addTextRun(['alignment' => Jc::BOTH]);
$runR4->addText("ARTÍCULO CUARTO. ", 'StyleBold');
$runR4->addText("Comunicar el presente acto administrativo a la División de Gestión del Talento Humano, para efectos del reconocimiento y efecto en la liquidación de la nómina.", 'StyleNormal');
$section->addTextBreak(1);

$section->addText("Se expide en Popayán, el {$textoFecha} de {$textoMes} de {$textoAno}.", 'StyleNormal');
$section->addTextBreak(2);

$section->addText("COMUNÍQUESE, NOTIFÍQUESE Y CÚMPLASE", 'StyleBold', ['alignment' => Jc::CENTER]);
$section->addTextBreak(3);

$section->addText("AIDA PATRICIA GONZÁLEZ NIEVA", 'StyleBold', ['alignment' => Jc::CENTER]);
$section->addText("Vicerrectora Académica", 'StyleNormal', ['alignment' => Jc::CENTER]);
$section->addText("Presidenta CIARP", 'StyleNormal', ['alignment' => Jc::CENTER]);

$section->addTextBreak(1);
$section->addText("Revisó: Víctor D. Ruiz P.", 'StyleNormal');
$section->addText("Elaboró: ElizeteR", 'StyleNormal');

// --- 6. DESCARGA SEGURA ---
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Libros_Masiva_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>