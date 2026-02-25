<?php
/**
 * Generador de Resoluciones CIARP - Artículos Especializados
 * Versión Agrupada por FACULTAD y DEPARTAMENTO (Plan B): 
 * - 1 Resolución por Facultad.
 * - Agrupación visual por Departamento.
 * - Consolida profesores con múltiples artículos y coautores.
 * - Detección automática de GÉNERO (F/M) para redacción dinámica y plurales.
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

$identificador = isset($_GET['cuadro_identificador_solicitud']) ? trim($_GET['cuadro_identificador_solicitud']) : '';
if (empty($identificador)) die("Identificador requerido.");

// --- 2. CONSULTA PLANA ---
$sql = "
    SELECT 
        s.id_solicitud_articulo,
        s.numero_oficio,
        s.tipo_articulo,
        s.titulo_articulo,
        s.nombre_revista,
        s.issn,
        s.tipo_publindex,
        s.ano_publicacion,
        s.volumen,
        s.numero_r,
        s.numero_autores,
        s.doi,
        s.puntaje,
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo, /* <-- CAMPO AÑADIDO PARA DETECCIÓN DE GÉNERO */
        t.email,
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento
    FROM solicitud s
    JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
    JOIN tercero t ON sp.fk_id_profesor = t.documento_tercero
    JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE s.identificador_solicitud = '" . $conn->real_escape_string($identificador) . "'
    AND (s.estado_solicitud IS NULL OR s.estado_solicitud <> 'an')
    ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.nombre_completo
";

$res = $conn->query($sql);
if ($res->num_rows === 0) die("No se encontraron registros activos para el identificador: " . htmlspecialchars($identificador));

// --- 3. MOTOR DE CLASIFICACIÓN POR FACULTADES ---
$facultades_data = [];

while ($row = $res->fetch_assoc()) {
    $fac = trim($row['facultad']);
    $facultades_data[$fac][] = $row;
}

// --- 4. CONFIGURACIÓN BASE DE WORD ---
$phpWord = new PhpWord();
$phpWord->addFontStyle('StyleBold', ['bold' => true, 'name' => 'Arial', 'size' => 11]);
$phpWord->addFontStyle('StyleNormal', ['name' => 'Arial', 'size' => 11]);
$styleTable = ['borderSize' => 6, 'borderColor' => '000000', 'cellMarginTop' => 20, 'cellMarginBottom' => 20, 'cellMarginLeft' => 80, 'cellMarginRight' => 80]; 
$phpWord->addFontStyle('FontTableBold', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
$phpWord->addFontStyle('FontTableNormal', ['name' => 'Arial', 'size' => 9]);
$phpWord->addParagraphStyle('ParaTable', ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0]);

$textoFecha = "____"; 
$textoMes = "________";
$textoAno = "____";

// --- 5. BUCLE PRINCIPAL: UNA RESOLUCIÓN POR FACULTAD ---
foreach ($facultades_data as $facultad_nombre => $rows) {
    
    // A. Sub-clasificar a los profesores dentro de esta Facultad
    $prof_records = [];
    $seen_articles = [];
    foreach ($rows as $r) {
        $cc = $r['documento_tercero'];
        $id_art = $r['id_solicitud_articulo'];
        if (!isset($seen_articles[$cc]) || !in_array($id_art, $seen_articles[$cc])) {
            $prof_records[$cc][] = $r;
            $seen_articles[$cc][] = $id_art;
        }
    }

    $subgrupos = [];
    $articulos_grupales = [];
    
    foreach ($prof_records as $cc => $arts) {
        if (count($arts) > 1) {
            $subgrupos[] = [
                'tipo' => 'individual',
                'profesores' => [$arts[0]], 
                'articulos' => $arts
            ];
        } else {
            $id_art = $arts[0]['id_solicitud_articulo'];
            $articulos_grupales[$id_art][] = $arts[0]; 
        }
    }
    
    foreach ($articulos_grupales as $id_art => $profesores) {
        $subgrupos[] = [
            'tipo' => 'grupo',
            'profesores' => $profesores,
            'articulos' => [$profesores[0]] 
        ];
    }

    // --- ORDENAR SUBGRUPOS POR DEPARTAMENTO PARA NO REPETIR TÍTULOS ---
    usort($subgrupos, function($a, $b) {
        $deptosA = []; foreach ($a['profesores'] as $p) $deptosA[] = mb_strtoupper(trim($p['departamento']), 'UTF-8');
        $deptosB = []; foreach ($b['profesores'] as $p) $deptosB[] = mb_strtoupper(trim($p['departamento']), 'UTF-8');
        $strA = implode(", ", array_values(array_unique($deptosA)));
        $strB = implode(", ", array_values(array_unique($deptosB)));
        return strcmp($strA, $strB);
    });

    // B. Extraer Datos Globales de la Facultad (Conceptos, Correos y Género General)
    $globalConceptos = []; $globalLiterales = []; $globalEmails = [];
    $allFacultadFemale = true;
    $totalProfesoresFacultad = 0;

    foreach ($subgrupos as $sg) {
        foreach ($sg['articulos'] as $art) {
            $tipo = mb_strtolower(trim($art['tipo_articulo']), 'UTF-8');
            if (strpos($tipo, 'full') !== false || strpos($tipo, 'especializada') !== false) {
                $globalConceptos[] = "en revistas especializadas"; $globalLiterales[] = "a";
            } elseif (strpos($tipo, 'corta') !== false || strpos($tipo, 'short') !== false || strpos($tipo, 'comunicacion') !== false || strpos($tipo, 'comunicación') !== false) {
                $globalConceptos[] = "por “Comunicación corta” (“short comunication”, “artículo corto”)"; $globalLiterales[] = "b";
            } elseif (strpos($tipo, 'revision') !== false || strpos($tipo, 'revisión') !== false) {
                $globalConceptos[] = "revisiones de tema"; $globalLiterales[] = "b";
            } elseif (strpos($tipo, 'editorial') !== false) {
                $globalConceptos[] = "editoriales"; $globalLiterales[] = "b";
            } else {
                $globalConceptos[] = $tipo; $globalLiterales[] = "b";
            }
        }
        foreach ($sg['profesores'] as $p) {
            if (!empty($p['email'])) $globalEmails[] = $p['email'];
            $sexo = strtoupper(trim($p['sexo'] ?? ''));
            if ($sexo !== 'F') $allFacultadFemale = false;
            $totalProfesoresFacultad++;
        }
    }
    
    $strGlobalConceptos = unirLista(array_values(array_unique($globalConceptos)));
    $arrLit = array_unique($globalLiterales); sort($arrLit);
    $strGlobalLiterales = unirLista($arrLit);
    $strGlobalEmails = empty($globalEmails) ? "No registrado" : implode("; ", array_unique($globalEmails));
    $palabraLiteralG = count($arrLit) > 1 ? "sus literales" : "su literal";
    $palabraLiteralGP = count($arrLit) > 1 ? "literales" : "literal";

    // Gramática Global para la Facultad (Título y Artículo 1, 4)
    $isGroupFacultad = $totalProfesoresFacultad > 1;
    if ($isGroupFacultad) {
        $txtTituloProf = $allFacultadFemale ? "a profesoras" : "a profesores";
        $txtResuelveProf = $allFacultadFemale ? "de las profesoras relacionadas" : "de los profesores relacionados";
        $txtNotificaProf = $allFacultadFemale ? "a las profesoras" : "a los profesores";
        $txtAdv = "advirtiéndoles";
        $txtAuth = "sus autorizaciones expresas";
    } else {
        $txtTituloProf = $allFacultadFemale ? "a una profesora" : "a un profesor";
        $txtResuelveProf = $allFacultadFemale ? "de la profesora relacionada" : "del profesor relacionado";
        $txtNotificaProf = $allFacultadFemale ? "a la profesora" : "al profesor";
        $txtAdv = "advirtiéndole";
        $txtAuth = "su autorización expresa";
    }

    // Conversión de Nombre de Facultad
    $nomFacultad = mb_convert_case(mb_strtolower(trim($facultad_nombre), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nomFacultad = str_replace(
        [' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], 
        [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], 
        $nomFacultad
    );

    // C. CREACIÓN DE LA PÁGINA DE LA FACULTAD
    $section = $phpWord->addSection([
        'paperSize' => 'Folio', 'marginTop' => 3000, 'marginLeft' => 1701, 'marginRight' => 1701, 'marginBottom' => 1417, 'footerHeight' => 500
    ]);

    $header = $section->addHeader();
    $tableHeader = $header->addTable(); $tableHeader->addRow();
    $tableHeader->addCell(8000)->addImage('img/encabezadob.png', ['width' => 170, 'alignment' => Jc::LEFT]);

    $footer = $section->addFooter();
    $tableFooter = $footer->addTable(); $tableFooter->addRow();
    $tableFooter->addCell(10000)->addImage('img/PIEb.png', ['width' => 430, 'alignment' => Jc::LEFT]);

    // --- ENCABEZADO LEGAL ---
    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº ____ DE {$textoAno}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$textoFecha} de {$textoMes})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    // Título Genérico con género y número
    $section->addText("Por la cual se reconocen puntos a la Base – Salarial {$txtTituloProf} de la {$nomFacultad} de la Universidad del Cauca, por concepto de productividad académica {$strGlobalConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("El Estatuto del Profesor Universitario – Acuerdo 024 de 1993, reglamenta los integrantes, funciones y criterios de asignación y reconocimiento de puntos del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en {$palabraLiteralG} “{$strGlobalLiterales}” los topes por producción {$strGlobalConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    // --- PÁRRAFOS DINÁMICOS POR CADA SUBGRUPO ---
    foreach ($subgrupos as $sg) {
        $nombresArray = []; $deptosArray = []; $oficiosArray = []; $conceptosSub = [];
        $isGroup = count($sg['profesores']) > 1;
        $allFemaleSub = true;

        foreach ($sg['profesores'] as $p) {
            $sexo = strtoupper(trim($p['sexo'] ?? ''));
            if ($sexo !== 'F') $allFemaleSub = false;
            $txtId = ($sexo === 'F') ? "identificada" : "identificado";

            $nombresArray[] = mb_strtoupper($p['profe_nombre'], 'UTF-8') . " {$txtId} con cédula de ciudadanía N° " . $p['documento_tercero'];
            
            $nomDepto = mb_convert_case(mb_strtolower(trim($p['departamento']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
            $nomDepto = str_replace(
                [' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], 
                [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], 
                $nomDepto
            );
            $deptosArray[] = $nomDepto;
        }

        foreach ($sg['articulos'] as $a) {
            $oficiosArray[] = $a['numero_oficio'];
            $tipo = mb_strtolower(trim($a['tipo_articulo']), 'UTF-8');
            if (strpos($tipo, 'full') !== false || strpos($tipo, 'especializada') !== false) {
                $conceptosSub[] = "en revistas especializadas";
            } elseif (strpos($tipo, 'corta') !== false || strpos($tipo, 'short') !== false || strpos($tipo, 'comunicacion') !== false || strpos($tipo, 'comunicación') !== false) {
                $conceptosSub[] = "por “Comunicación corta” (“short comunication”, “artículo corto”)";
            } elseif (strpos($tipo, 'revision') !== false || strpos($tipo, 'revisión') !== false) {
                $conceptosSub[] = "revisiones de tema";
            } elseif (strpos($tipo, 'editorial') !== false) {
                $conceptosSub[] = "editoriales";
            } else {
                $conceptosSub[] = $tipo;
            }
        }
        
        $strNombres = unirLista($nombresArray);
        $strDeptos = unirLista(array_values(array_unique($deptosArray)));
        $strOficios = unirLista(array_values(array_unique($oficiosArray)));
        $strConceptosSub = unirLista(array_values(array_unique($conceptosSub)));
        
        // Gramática Subgrupo
        if ($isGroup) {
            $txtProfesores = $allFemaleSub ? "Las profesoras " : "Los profesores ";
            $txtAdscritos = $allFemaleSub ? "adscritas" : "adscritos";
            $txtSolicitaron = "solicitaron";
        } else {
            $txtProfesores = $allFemaleSub ? "La profesora " : "El profesor ";
            $txtAdscritos = $allFemaleSub ? "adscrita" : "adscrito";
            $txtSolicitaron = "solicitó";
        }
        
        $txtDepto = count(array_unique($deptosArray)) > 1 ? "a los Departamentos de" : "al Departamento de";

        $run = $section->addTextRun(['alignment' => Jc::BOTH]);
        $run->addText("{$txtProfesores}{$strNombres}, {$txtAdscritos} {$txtDepto} {$strDeptos} de la {$nomFacultad}, {$txtSolicitaron} al Comité de Personal Docente de su facultad, el reconocimiento por productividad académica {$strConceptosSub}, y a su vez el CPD remitió al CIARP mediante oficio N° {$strOficios} de {$textoAno}.", 'StyleNormal');
    }

    $section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$textoFecha} de {$textoMes} de {$textoAno}, previo a la asignación de los puntajes correspondientes y con fundamento en el concepto de los Comités de Personal Docente de las facultad antes mencionada y la clasificación realizada por MINCIENCIAS, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, y {$palabraLiteralGP} {$strGlobalLiterales}, que establece el reconocimiento {$strGlobalConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("Decidiéndose por el citado Comité otorgar el puntaje que a continuación se enuncia:", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("FACULTAD DE " . mb_strtoupper($facultad_nombre, 'UTF-8'), 'StyleBold');

    $currentDeptoCons = ""; 

    // --- TABLAS DE DETALLES PARA CADA SUBGRUPO ---
    foreach ($subgrupos as $sg) {
        $deptosArray = []; $tiposNombres = [];
        foreach ($sg['profesores'] as $p) $deptosArray[] = mb_strtoupper($p['departamento'], 'UTF-8');
        foreach ($sg['articulos'] as $a) $tiposNombres[] = mb_strtolower(trim($a['tipo_articulo']), 'UTF-8');
        
        $strDeptos = unirLista(array_values(array_unique($deptosArray)));
        $strTiposNombres = mb_strtoupper(implode(" / ", array_unique($tiposNombres)), 'UTF-8');
        $lblDepto = count(array_unique($deptosArray)) > 1 ? "DEPARTAMENTOS DE " : "DEPARTAMENTO DE ";

        if ($strDeptos !== $currentDeptoCons) {
            $section->addTextBreak(1);
            $section->addText($lblDepto . $strDeptos, 'StyleBold');
            $currentDeptoCons = $strDeptos;
        }

        $section->addText("Puntaje por base salarial: [ {$strTiposNombres} ]", 'StyleBold');
        
        foreach ($sg['profesores'] as $p) {
            $section->addText(mb_strtoupper($p['profe_nombre'], 'UTF-8') . " C.C " . $p['documento_tercero'], 'StyleBold');
        }
        $section->addTextBreak(1);

        foreach ($sg['articulos'] as $art) {
            $table = $section->addTable($styleTable);
            $table->addRow();
            $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("NO. OFICIO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['numero_oficio'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText(mb_strtoupper($art['tipo_articulo'], 'UTF-8'), 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText(mb_strtoupper($art['titulo_articulo'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("REVISTA", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText(mb_strtoupper($art['nombre_revista'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("ISSN", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['issn'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("TIPO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['tipo_publindex'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("AÑO", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['ano_publicacion'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("VOL.", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['volumen'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("N°", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['numero_r'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("AUTORES", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['numero_autores'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("DOI", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['doi'], 'FontTableNormal', 'ParaTable');

            $table->addRow();
            $table->addCell(3000)->addText("RECONOCER", 'FontTableBold', 'ParaTable');
            $table->addCell(6000)->addText($art['puntaje'] . " PUNTOS", 'FontTableBold', 'ParaTable');

            $section->addTextBreak(1);
        }
    }

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
    $runR1->addText("Reconocer puntos a la base salarial {$txtResuelveProf} a continuación, conforme a los productos mencionados en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, y {$palabraLiteralGP} {$strGlobalLiterales}, que establece el reconocimiento {$strGlobalConceptos}; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');

    $section->addText("Puntaje por base salarial:", 'StyleBold');
    $section->addTextBreak(1);
    
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad_nombre, 'UTF-8'), 'StyleBold');

    $currentDeptoRes = "";

    foreach ($subgrupos as $sg) {
        $deptosArray = [];
        foreach ($sg['profesores'] as $p) $deptosArray[] = mb_strtoupper($p['departamento'], 'UTF-8');
        $strDeptos = unirLista(array_values(array_unique($deptosArray)));
        $lblDepto = count(array_unique($deptosArray)) > 1 ? "DEPARTAMENTOS DE " : "DEPARTAMENTO DE ";

        if ($strDeptos !== $currentDeptoRes) {
            $section->addTextBreak(1);
            $section->addText($lblDepto . $strDeptos, 'StyleBold');
            $currentDeptoRes = $strDeptos;
        }
        
        $ptsTotalGrupo = 0;
        foreach ($sg['articulos'] as $a) $ptsTotalGrupo += floatval($a['puntaje']);
        
        foreach ($sg['profesores'] as $p) {
            $section->addText(mb_strtoupper($p['profe_nombre'], 'UTF-8') . " C.C " . $p['documento_tercero'] . " RECONOCER " . $ptsTotalGrupo . " PUNTOS", 'StyleBold');
        }
    }

    $section->addTextBreak(1);
    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
    $runR2->addText("El puntaje asignado tendrá efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, {$palabraLiteralGP} {$strGlobalLiterales}, del Decreto 1279 de 2002.", 'StyleNormal');

    $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR3->addText("ARTÍCULO TERCERO. ", 'StyleBold');
    $runR3->addText("A la fecha del presente reconocimiento, el CIARP revisó la identification de la revista en las bases de datos: SCOPUS, MIAR, DOAJ Y SCIMAGO, como también en PUBLINDEX y su registro DOI; sin que exista evidencia oficial de su catalogación como predadora o de las malas prácticas editoriales.", 'StyleNormal');

    $runR4 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR4->addText("ARTÍCULO CUARTO. ", 'StyleBold');
    $runR4->addText("Notificar el presente acto administrativo {$txtNotificaProf}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a {$txtAuth} en el formato PM-FO-4-FOR-4, a los correos {$strGlobalEmails}; {$txtAdv} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días siguientes a la fecha de la notificación.", 'StyleNormal');

    $runR5 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR5->addText("ARTÍCULO QUINTO. ", 'StyleBold');
    $runR5->addText("Comunicar el presente acto administrativo a la División de Gestión del Talento Humano, para efectos del reconocimiento y efecto en la liquidación de la nómina.", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("Se expide en Popayán, el {$textoFecha} de {$textoMes} de {$textoAno}.", 'StyleNormal');
    $section->addTextBreak(2);

    $section->addText("COMUNÍQUESE, NOTIFÍQUESE Y CÚMPLASE", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(3);

    $section->addText("AIDA PATRICIA GONZÁLEZ NIEVA", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("Vicerrectora Académica", 'StyleNormal', ['alignment' => Jc::CENTER]);
    
    $section->addTextBreak(1);
    $section->addText("Revisó: Víctor D. Ruiz P.", 'StyleNormal');
    $section->addText("Elaboró: ElizeteR", 'StyleNormal');

    $section->addPageBreak();
}

// --- 6. DESCARGA SEGURA ---
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Facultades_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>