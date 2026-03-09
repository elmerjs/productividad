<?php
/**
 * Generador de Resoluciones CIARP - Productividad por PATENTES
 * Versión Inteligente (Plan A): 
 * - Consolida múltiples patentes por docente.
 * - Agrupa a varios profesores si comparten la misma patente (coautores).
 * - Gramática de Género (F/M) y Nombres Propios para Departamentos/Facultades.
 * - Basado en Literal g (Patentes de invención).
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

$identificador = isset($_GET['cuadro_identificador_patente']) ? trim($_GET['cuadro_identificador_patente']) : '';
if (empty($identificador)) die("Identificador requerido.");

// --- 2. CONSULTA PLANA ---
$sql = "
    SELECT 
        p.id_patente,
        p.numero_oficio,
        p.producto AS nombre_patente,
        p.numero_profesores AS numero_autores,
        p.puntaje AS puntos,
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo,
        t.email,
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento
    FROM patentes p
    JOIN patente_profesor pp ON p.id_patente = pp.id_patente
    JOIN tercero t ON pp.id_profesor = t.documento_tercero
    JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE p.identificador = '" . $conn->real_escape_string($identificador) . "'
    AND (p.estado IS NULL OR p.estado <> 'an')
    ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.nombre_completo
";

$res = $conn->query($sql);
if ($res->num_rows === 0) die("No se encontraron registros activos para el identificador: " . htmlspecialchars($identificador));

// --- 3. MOTOR DE CLASIFICACIÓN (PLAN A) ---
$prof_records = [];
$seen_patentes = [];

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    $id_patente = $row['id_patente'];
    
    if (!isset($prof_records[$cc])) {
        $prof_records[$cc] = [];
        $seen_patentes[$cc] = [];
    }
    if (!in_array($id_patente, $seen_patentes[$cc])) {
        $prof_records[$cc][] = $row;
        $seen_patentes[$cc][] = $id_patente;
    }
}

$prof_multiples = [];
$patentes_grupales = [];

foreach ($prof_records as $cc => $patentes) {
    if (count($patentes) > 1) {
        $prof_multiples[$cc] = $patentes;
    } else {
        $row = $patentes[0];
        $id_patente = $row['id_patente'];
        $fac = $row['facultad'];
        
        if (!isset($patentes_grupales[$id_patente])) $patentes_grupales[$id_patente] = [];
        if (!isset($patentes_grupales[$id_patente][$fac])) $patentes_grupales[$id_patente][$fac] = [];
        
        $patentes_grupales[$id_patente][$fac][] = $row;
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
foreach ($prof_multiples as $cc => $patentes) {
    $docentes_list = [[
        'documento_tercero' => $cc,
        'profe_nombre' => $patentes[0]['profe_nombre'],
        'sexo' => $patentes[0]['sexo'],
        'email' => $patentes[0]['email'],
        'departamento' => $patentes[0]['departamento']
    ]];
    generarResolucionPatente($phpWord, $docentes_list, $patentes, $patentes[0]['facultad'], $styleTable);
}

foreach ($patentes_grupales as $id_patente => $facultades) {
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
        $patentes_list = [$profesores[0]]; 
        generarResolucionPatente($phpWord, $docentes_list, $patentes_list, $fac, $styleTable);
    }
}

// --- 6. FUNCIÓN DE RENDERIZADO ---
function generarResolucionPatente($phpWord, $docentes_list, $patentes_list, $facultad, $styleTable) {
    
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

    // PREPARACIÓN DATOS PATENTES
    $oficios = []; 
    $puntajeTotal = 0;
    foreach ($patentes_list as $pat) {
        $puntajeTotal += floatval($pat['puntos']);
        $oficios[] = $pat['numero_oficio'];
    }
    $oficios = array_unique($oficios);
    $textoOficios = unirLista($oficios);

    // PREPARACIÓN DATOS PROFESORES (GÉNERO Y NOMBRE PROPIO)
    $nombresTextArray = []; $deptos = []; $emails = [];
    $allFemale = true;

    foreach ($docentes_list as $d) {
        $sexo = strtoupper(trim($d['sexo'] ?? ''));
        if ($sexo !== 'F') $allFemale = false;
        
        $txtIdentificado = ($sexo === 'F') ? "identificada" : "identificado";
        $nombresTextArray[] = mb_strtoupper($d['profe_nombre'], 'UTF-8') . " {$txtIdentificado} con C.C N° " . $d['documento_tercero'];
        
        $nomDepto = mb_convert_case(mb_strtolower(trim($d['departamento']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $nomDepto = str_replace([' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], $nomDepto);
        $deptos[] = $nomDepto;

        if (!empty($d['email'])) $emails[] = $d['email'];
    }
    
    $isGroup = count($docentes_list) > 1;

    // Gramática Dinámica
    if ($isGroup) {
        if ($allFemale) {
            $textoUnProfesor = "unas profesoras";
            $textoProfesores = "Las profesoras ";
            $textoDelProfesor = "de las profesoras relacionadas";
            $textoAdscrito = "adscritas";
            $txtAlProfesor = "a las profesoras";
        } else {
            $textoUnProfesor = "unos profesores";
            $textoProfesores = "Los profesores ";
            $textoDelProfesor = "de los profesores relacionados";
            $textoAdscrito = "adscritos";
            $txtAlProfesor = "a los profesores";
        }
        $palabraSolicito = "solicitaron al Comité de Personal Docente de su facultad, el reconocimiento por productividad académica por patentes, y a su vez el CPD remitió";
        $adv = "advirtiéndoles";
        $auth = "sus autorizaciones expresas";
    } else {
        if ($allFemale) {
            $textoUnProfesor = "una profesora";
            $textoProfesores = "La profesora ";
            $textoDelProfesor = "de la profesora relacionada";
            $textoAdscrito = "adscrita";
            $txtAlProfesor = "a la profesora";
        } else {
            $textoUnProfesor = "un profesor";
            $textoProfesores = "El profesor ";
            $textoDelProfesor = "del profesor relacionado";
            $textoAdscrito = "adscrito";
            $txtAlProfesor = "al profesor";
        }
        $palabraSolicito = "solicitó al Comité de Personal Docente de su facultad, el reconocimiento por productividad académica por patentes, y a su vez el CPD remitió";
        $adv = "advirtiéndole";
        $auth = "su autorización expresa";
    }
    
    $nomFacultad = mb_convert_case(mb_strtolower(trim($facultad), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nomFacultad = str_replace([' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], $nomFacultad);

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

    // Título corregido: Patentes de invención
    $section->addText("Por la cual se reconocen puntos a la Base –Salarial a {$textoUnProfesor} de la Universidad del Cauca, por concepto de Patentes de invención.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("El Estatuto del Profesor Universitario – Acuerdo 024 de 1993, reglamenta los integrantes, funciones y criterios de asignación y reconocimiento de puntos del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    // Párrafo específico literal g
    $section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en su literal g, los topes por producción de Patentes.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$textoProfesores}{$textoNombresList}, {$textoAdscrito} {$palabraDepto} {$textoDeptos} de la {$nomFacultad}, {$palabraSolicito} al CIARP mediante oficio N° {$textoOficios} de {$textoAno}.", 'StyleNormal');

    $section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$textoFecha} de {$textoMes} de {$textoAno}, previo a la asignación de los puntajes correspondientes y con fundamento en el concepto del Comité de Personal Docente de la facultad antes mencionada y la clasificación realizada por MINCIENCIAS, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, literal g, que establece el reconocimiento de Patentes de invención.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("Decidiéndose por el citado Comité otorgar los puntajes que a continuación se enuncian:", 'StyleNormal');

    $section->addText("Puntaje por base salarial:", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'], 'StyleBold');
    }
    $section->addTextBreak(1);

    // TABLAS PARA PATENTES
    foreach ($patentes_list as $pat) {
        $table = $section->addTable($styleTable);
        
        $table->addRow();
        $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("AUTORES", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pat['numero_autores'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NÚMERO DE OFICIO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pat['numero_oficio'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("PRODUCTO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($pat['nombre_patente'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("ASIGNAR", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pat['puntos'] . " PUNTOS", 'FontTableBold', 'ParaTable');

        $section->addTextBreak(1);
    }

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
    $runR1->addText("Reconocer puntos a la base salarial {$textoDelProfesor} a continuación, conforme a los productos mencionados en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, literal g, que establece el reconocimiento de Patentes de invención; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . ". RECONOCER  " . $puntajeTotal . "  PUNTOS", 'StyleNormal');
    }
    $section->addTextBreak(1);

    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
    $runR2->addText("El puntaje asignado tendrá efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, literal g, del Decreto 1279 de 2002.", 'StyleNormal');

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
    $section->addText("Revisó: ", 'StyleNormal');
    $section->addText("Elaboró: ElizeteR", 'StyleNormal');

    $section->addPageBreak();
}

// --- 7. DESCARGA SEGURA ---
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Patentes_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>