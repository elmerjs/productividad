<?php
/**
 * Generador de Resoluciones CIARP - Libros de Investigación y de Texto
 * Versión Inteligente (Plan A): 
 * - Consolida múltiples libros por docente.
 * - Agrupa coautores del mismo libro (por Facultad).
 * - Gramática dinámica para literales C y D.
 * - Nombres de Departamentos y Facultades en "Nombre Propio".
 * - Detección automática de GÉNERO (F/M) para redacción inclusiva y gramaticalmente correcta.
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
        l.autores AS numero_autores,
        l.evaluacion_1,
        l.evaluacion_2,
        l.puntaje_final AS puntaje,
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo, /* <-- CAMPO AÑADIDO PARA DETECCIÓN DE GÉNERO */
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

// --- 3. MOTOR DE CLASIFICACIÓN (PLAN A) ---
$prof_records = [];
$seen_libros = [];

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    $id_l = $row['id_libro'];
    
    if (!isset($prof_records[$cc])) {
        $prof_records[$cc] = [];
        $seen_libros[$cc] = [];
    }
    if (!in_array($id_l, $seen_libros[$cc])) {
        $prof_records[$cc][] = $row;
        $seen_libros[$cc][] = $id_l;
    }
}

$prof_multiples = [];
$libros_grupales = [];

foreach ($prof_records as $cc => $libros) {
    if (count($libros) > 1) {
        $prof_multiples[$cc] = $libros;
    } else {
        $row = $libros[0];
        $id_l = $row['id_libro'];
        $fac = $row['facultad'];
        
        if (!isset($libros_grupales[$id_l])) $libros_grupales[$id_l] = [];
        if (!isset($libros_grupales[$id_l][$fac])) $libros_grupales[$id_l][$fac] = [];
        
        $libros_grupales[$id_l][$fac][] = $row;
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

// --- 5. BUCLE DE GENERACIÓN ---

foreach ($prof_multiples as $cc => $libros) {
    $docentes_list = [[
        'documento_tercero' => $cc,
        'profe_nombre' => $libros[0]['profe_nombre'],
        'sexo' => $libros[0]['sexo'],
        'email' => $libros[0]['email'],
        'departamento' => $libros[0]['departamento']
    ]];
    generarResolucionLibro($phpWord, $docentes_list, $libros, $libros[0]['facultad'], $styleTable);
}

foreach ($libros_grupales as $id_l => $facultades) {
    foreach ($facultades as $fac => $profesores) {
        $docentes_list = [];
        foreach ($profesores as $p) {
            $docentes_list[] = [
                'documento_tercero' => $p['documento_tercero'],
                'profe_nombre' => $p['profe_nombre'],
                'sexo' => $p['sexo'],
                'email' => $p['email'],
                'departamento' => $p['departamento']
            ];
        }
        $libros_list = [$profesores[0]]; 
        generarResolucionLibro($phpWord, $docentes_list, $libros_list, $fac, $styleTable);
    }
}

// --- 6. FUNCIÓN DE RENDERIZADO ---
function generarResolucionLibro($phpWord, $docentes_list, $libros_list, $facultad, $styleTable) {
    
    $section = $phpWord->addSection([
        'paperSize' => 'Folio', 'marginTop' => 3000, 'marginLeft' => 1701, 'marginRight' => 1701, 'marginBottom' => 1417, 'footerHeight' => 500
    ]);

    // ENCABEZADO Y PIE
    $header = $section->addHeader();
    $tableHeader = $header->addTable(); $tableHeader->addRow();
    $tableHeader->addCell(8000)->addImage('img/encabezadob.png', ['width' => 170, 'alignment' => Jc::LEFT]);

    $footer = $section->addFooter();
    $tableFooter = $footer->addTable(); $tableFooter->addRow();
    $tableFooter->addCell(10000)->addImage('img/PIEb.png', ['width' => 430, 'alignment' => Jc::LEFT]);

    // --- LÓGICA DE DETECCIÓN: LITERAL C vs LITERAL D ---
    $conceptos = [];
    $literales = [];
    $oficios = []; 
    $puntajeTotal = 0;

    foreach ($libros_list as $lib) {
        $puntajeTotal += floatval($lib['puntaje']);
        $oficios[] = $lib['numero_oficio'];
        
        $tipo = mb_strtolower(trim($lib['tipo_libro']), 'UTF-8');
        
        if (strpos($tipo, 'texto') !== false) {
            $conceptos[] = "libros de texto";
            $literales[] = "d";
        } else {
            $conceptos[] = "libros que resultan de una labor de investigación";
            $literales[] = "c";
        }
    }
    
    $conceptos = array_values(array_unique($conceptos));
    $literales = array_unique($literales); 
    sort($literales);
    $oficios = array_values(array_unique($oficios));
    
    $textoConcepto = unirLista($conceptos);
    $textoLiteral = unirLista($literales);
    $textoOficios = unirLista($oficios);

    $palabraLiteral = count($literales) > 1 ? "sus literales" : "su literal";
    $palabraLiteralP = count($literales) > 1 ? "literales" : "literal";

    // --- PREPARACIÓN DE DATOS CON NOMBRE PROPIO Y GRAMÁTICA DE GÉNERO ---
    $nombresTextArray = []; 
    $deptos = []; 
    $emails = [];
    $isGroup = count($docentes_list) > 1;
    $allFemale = true; // Asumimos que todas son mujeres hasta demostrar lo contrario
    
    foreach ($docentes_list as $d) {
        // Lógica de género individual (identificado vs identificada)
        $sexo = strtoupper(trim($d['sexo']));
        if ($sexo !== 'F') {
            $allFemale = false; // Si hay al menos un hombre o dato vacío, el plural será masculino
        }
        
        $txtIdentificado = ($sexo === 'F') ? "identificada" : "identificado";
        $nombresTextArray[] = mb_strtoupper($d['profe_nombre'], 'UTF-8') . " {$txtIdentificado} con cédula de ciudadanía N°" . $d['documento_tercero'];
        
        // Conversión a Nombre Propio para el Departamento
        $nomDepto = mb_convert_case(mb_strtolower(trim($d['departamento']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $nomDepto = str_replace(
            [' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], 
            [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], 
            $nomDepto
        );
        $deptos[] = $nomDepto;

        if (!empty($d['email'])) $emails[] = $d['email'];
    }
    
    // --- LÓGICA DE GRAMÁTICA DE GRUPOS (Masculino, Femenino, Plural, Singular) ---
    if ($isGroup) {
        if ($allFemale) {
            $textoProfesores = "Las profesoras ";
            $textoUnProfesor = "unas profesoras";
            $textoDelProfesor = "de las profesoras relacionadas";
            $textoAdscrito = "adscritas";
            $txtAlProfesor = "a las profesoras";
        } else {
            $textoProfesores = "Los profesores ";
            $textoUnProfesor = "unos profesores";
            $textoDelProfesor = "de los profesores relacionados";
            $textoAdscrito = "adscritos";
            $txtAlProfesor = "a los profesores";
        }
        $palabraSolicito = "solicitaron";
        $adv = "advirtiéndoles";
        $auth = "sus autorizaciones expresas";
    } else {
        if ($allFemale) {
            $textoProfesores = "La profesora ";
            $textoUnProfesor = "una profesora";
            $textoDelProfesor = "de la profesora relacionada";
            $textoAdscrito = "adscrita";
            $txtAlProfesor = "a la profesora";
        } else {
            $textoProfesores = "El profesor ";
            $textoUnProfesor = "un profesor";
            $textoDelProfesor = "del profesor relacionado";
            $textoAdscrito = "adscrito";
            $txtAlProfesor = "al profesor";
        }
        $palabraSolicito = "solicitó";
        $adv = "advirtiéndole";
        $auth = "su autorización expresa";
    }

    // Conversión a Nombre Propio para la Facultad
    $nomFacultad = mb_convert_case(mb_strtolower(trim($facultad), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nomFacultad = str_replace(
        [' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], 
        [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], 
        $nomFacultad
    );
    
    $textoNombresList = unirLista($nombresTextArray);
    $deptosUnique = array_values(array_unique($deptos));
    $textoDeptos = unirLista($deptosUnique);
    
    $palabraDepto = count($deptosUnique) > 1 ? "a los Departamentos de" : "al Departamento de";
    $lblDepto = count($deptosUnique) > 1 ? "DEPARTAMENTOS DE " : "DEPARTAMENTO DE ";
    
    $correosTexto = empty($emails) ? "No registrado" : implode("; ", array_unique($emails));

    $textoFecha = "____"; $textoMes = "________"; $textoAno = "____";

    // --- IMPRESIÓN DEL DOCUMENTO ---
    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº ____ DE {$textoAno}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$textoFecha} de {$textoMes})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    // Título ajustado dinámicamente
    $section->addText("Por la cual se reconocen puntos a la Base – Salarial a {$textoUnProfesor} de la Universidad del Cauca, por concepto de {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("El Estatuto del profesor Universitario – Acuerdo 024 de 1993, reglamenta las funciones del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en {$palabraLiteral} {$textoLiteral}, topes por producción en {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    // Párrafo con Departamento, Facultad en Nombre Propio y Gramática Exacta
    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$textoProfesores}{$textoNombresList}, {$textoAdscrito} {$palabraDepto} {$textoDeptos} de la {$nomFacultad}, {$palabraSolicito} al Comité de Personal Docente de la facultad el reconocimiento por productividad académica, y a su vez, el CPD remitió al CIARP mediante oficio N° {$textoOficios} de {$textoAno}.", 'StyleNormal');

    $section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$textoFecha} de {$textoMes}, previo a la asignación del puntaje correspondiente y con fundamento en el concepto del Comité de Personal Docente de la facultad antes mencionada, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, {$palabraLiteralP} {$textoLiteral}, que establece el reconocimiento de {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("Decidiéndose por el citado Comité otorgar los puntajes que a continuación se enuncian:", 'StyleNormal');

    $section->addText("Puntaje por base salarial:", 'StyleBold');
    
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'], 'StyleBold');
    }
    $section->addTextBreak(1);

    // TABLAS
    foreach ($libros_list as $lib) {
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
        $table->addCell(3000)->addText("ISBN DIGITAL", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($lib['isbn'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("EDITORIAL", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($lib['nombre_editorial'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("AÑO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($lib['ano_publicacion'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("AUTORES", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($lib['numero_autores'], 'FontTableNormal', 'ParaTable');

        // Cálculo del promedio para las Evaluaciones
        $ev1 = floatval($lib['evaluacion_1']);
        $ev2 = floatval($lib['evaluacion_2']);
        $suma = $ev1 + $ev2;
        $promedio = $suma / 2;
        $textoEvaluacion = "{$ev1} + {$ev2} = {$suma} / 2 = {$promedio}%";

        $table->addRow();
        $table->addCell(3000)->addText("EVALUACIONES", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($textoEvaluacion, 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("RECONOCER", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($lib['puntaje'] . " PUNTOS", 'FontTableBold', 'ParaTable');

        $section->addTextBreak(1);
    }

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
    $runR1->addText("Reconocer puntos a la base salarial {$textoDelProfesor} a continuación, conforme al producto mencionado en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, {$palabraLiteralP} {$textoLiteral}, que establece el reconocimiento de {$textoConcepto}; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');

    $section->addText("Puntaje por base salarial:", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . " RECONOCER " . $puntajeTotal . " PUNTOS", 'StyleBold');
    }
    $section->addTextBreak(1);

    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
    $runR2->addText("El puntaje asignado {$txtAlProfesor} tendrá efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, {$palabraLiteralP} {$textoLiteral}, del Decreto 1279 de 2002.", 'StyleNormal');

    $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR3->addText("ARTÍCULO TERCERO. ", 'StyleBold');
    $runR3->addText("Notificar el presente acto administrativo {$txtAlProfesor}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a {$auth} en el formato PM-FO-4-FOR-4, al correo {$correosTexto}; {$adv} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días siguientes a la fecha de la notificación.", 'StyleNormal');

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

    $section->addPageBreak();
}

// --- 7. DESCARGA SEGURA ---
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Libros_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>