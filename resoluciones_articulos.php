<?php
/**
 * Generador de Resoluciones CIARP - Artículos Especializados
 * Versión Inteligente (Plan A): 
 * - Agrupación estricta por TIPO DE ARTÍCULO.
 * - Desglose de múltiples puntajes.
 * - Oculta MDPI si no está marcada.
 * - VARIABLES DINÁMICAS: Número, Fecha, Nombres y Cargos de firmas personalizables.
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

function getCategoriaArticulo($tipo_articulo) {
    $tipo = mb_strtolower(trim($tipo_articulo), 'UTF-8');
    if (strpos($tipo, 'full') !== false || strpos($tipo, 'especializada') !== false) {
        return 'full';
    } elseif (strpos($tipo, 'corta') !== false || strpos($tipo, 'short') !== false || strpos($tipo, 'comunicacion') !== false || strpos($tipo, 'comunicación') !== false) {
        return 'corta';
    } elseif (strpos($tipo, 'revision') !== false || strpos($tipo, 'revisión') !== false) {
        return 'revision';
    } elseif (strpos($tipo, 'editorial') !== false) {
        return 'editorial';
    } else {
        return 'otro';
    }
}

$identificador = isset($_GET['cuadro_identificador_solicitud']) ? trim($_GET['cuadro_identificador_solicitud']) : '';
if (empty($identificador)) die("Identificador requerido.");

// =========================================================================
// RECEPCIÓN DE NUEVAS VARIABLES DINÁMICAS (FORMULARIO)
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

// =========================================================================
// NUEVO: GUARDAR DATOS DE LA RESOLUCIÓN EN LA BASE DE DATOS
// =========================================================================
if (isset($_GET['num_resolucion']) || isset($_GET['fecha_resolucion'])) {
    
    $db_num_res = (isset($_GET['num_resolucion']) && trim($_GET['num_resolucion']) !== '') ? trim($_GET['num_resolucion']) : null;
    $db_fecha_res = (isset($_GET['fecha_resolucion']) && trim($_GET['fecha_resolucion']) !== '') ? trim($_GET['fecha_resolucion']) : null;
    
    $sql_update = "UPDATE solicitud SET 
                    num_resolucion = ?, 
                    fecha_resolucion = ?, 
                    nombre_vicerrector = ?, 
                    genero_vicerrector = ?, 
                    nombre_reviso = ?, 
                    nombre_elaboro = ? 
                   WHERE identificador_solicitud = ?";
                   
    if ($stmt_upd = $conn->prepare($sql_update)) {
        $stmt_upd->bind_param(
            "sssssss", 
            $db_num_res, 
            $db_fecha_res, 
            $nombre_vicerrector, 
            $genero_vicerrector, 
            $nombre_reviso, 
            $nombre_elaboro, 
            $identificador
        );
        $stmt_upd->execute();
        $stmt_upd->close();
    }
}
// =========================================================================

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
        s.mdpi_pred, 
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo, 
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

// --- 3. MOTOR DE CLASIFICACIÓN ---
$prof_records = [];
$seen_articles = [];

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    $id_art = $row['id_solicitud_articulo'];
    $cat = getCategoriaArticulo($row['tipo_articulo']); 
    
    if (!isset($prof_records[$cc][$cat])) {
        $prof_records[$cc][$cat] = [];
        $seen_articles[$cc][$cat] = [];
    }
    if (!in_array($id_art, $seen_articles[$cc][$cat])) {
        $prof_records[$cc][$cat][] = $row;
        $seen_articles[$cc][$cat][] = $id_art;
    }
}

$prof_multiples = [];
$articulos_grupales = [];

foreach ($prof_records as $cc => $categorias) {
    foreach ($categorias as $cat => $arts) {
        if (count($arts) > 1) {
            $prof_multiples[$cc . '_' . $cat] = $arts;
        } else {
            $row = $arts[0];
            $id_art = $row['id_solicitud_articulo'];
            $fac = $row['facultad'];
            
            if (!isset($articulos_grupales[$id_art][$fac])) {
                $articulos_grupales[$id_art][$fac] = [];
            }
            $articulos_grupales[$id_art][$fac][] = $row;
        }
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

// Convertimos variables a string para pasarlas a la función
$vars = [
    'num_res' => $num_resolucion,
    'dia' => $textoFecha,
    'mes' => $textoMes,
    'ano' => $textoAno,
    'nom_vicerrector' => $nombre_vicerrector,
    'car_vicerrector' => $cargo_vicerrector,
    'car_presidente' => $cargo_presidente,
    'reviso' => $nombre_reviso,
    'elaboro' => $nombre_elaboro
];

// --- 5. BUCLE DE GENERACIÓN ---
foreach ($prof_multiples as $key => $arts) {
    $cc = explode('_', $key)[0];
    $docentes_list = [[
        'documento_tercero' => $cc,
        'profe_nombre' => $arts[0]['profe_nombre'],
        'sexo' => $arts[0]['sexo'],
        'email' => $arts[0]['email'],
        'departamento' => $arts[0]['departamento']
    ]];
    generarResolucion($phpWord, $docentes_list, $arts, $arts[0]['facultad'], $styleTable, $vars);
}

foreach ($articulos_grupales as $id_art => $facultades) {
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
        $articulos_list = [$profesores[0]]; 
        generarResolucion($phpWord, $docentes_list, $articulos_list, $fac, $styleTable, $vars);
    }
}

// --- 6. FUNCIÓN DE RENDERIZADO ---
function generarResolucion($phpWord, $docentes_list, $articulos_list, $facultad, $styleTable, $vars) {
    
    $section = $phpWord->addSection([
        'paperSize' => 'Folio', 'marginTop' => 3000, 'marginLeft' => 1701, 'marginRight' => 1701, 'marginBottom' => 1417, 'footerHeight' => 500
    ]);

    $header = $section->addHeader();
    $tableHeader = $header->addTable();
    $tableHeader->addRow();
    $tableHeader->addCell(8000)->addImage('img/encabezadob.png', ['width' => 170, 'alignment' => Jc::LEFT]);

    $footer = $section->addFooter();
    $tableFooter = $footer->addTable();
    $tableFooter->addRow();
    $tableFooter->addCell(10000)->addImage('img/PIEb.png', ['width' => 430, 'alignment' => Jc::LEFT]);

    $conceptos = []; $literales = []; $tiposArray = []; $oficios = []; 
    $puntajeTotal = 0; $puntosArray = []; 
    $has_mdpi = false; 

    foreach ($articulos_list as $art) {
        $pt = floatval($art['puntaje']);
        $puntajeTotal += $pt;
        $puntosArray[] = $pt; 
        $oficios[] = $art['numero_oficio'];
        
        if (isset($art['mdpi_pred']) && $art['mdpi_pred'] == 1) {
            $has_mdpi = true;
        }

        $tipo = mb_strtolower(trim($art['tipo_articulo']), 'UTF-8');
        $tiposArray[] = $tipo;
        
        if (strpos($tipo, 'full') !== false || strpos($tipo, 'especializada') !== false) {
            $conceptos[] = "en revistas especializadas"; $literales[] = "a";
        } elseif (strpos($tipo, 'corta') !== false || strpos($tipo, 'short') !== false || strpos($tipo, 'comunicacion') !== false || strpos($tipo, 'comunicación') !== false) {
            $conceptos[] = "por “Comunicación corta” (“short comunication”, “artículo corto”)"; $literales[] = "b";
        } elseif (strpos($tipo, 'revision') !== false || strpos($tipo, 'revisión') !== false) {
            $conceptos[] = "revisiones de tema"; $literales[] = "b";
        } elseif (strpos($tipo, 'editorial') !== false) {
            $conceptos[] = "editoriales"; $literales[] = "b";
        } else {
            $conceptos[] = $tipo; $literales[] = "b";
        }
    }
    
    $conceptos = array_values(array_unique($conceptos));
    $literales = array_unique($literales); sort($literales);
    $oficios = array_unique($oficios);
    $tiposArray = array_unique($tiposArray);
    
    $textoConcepto = unirLista($conceptos);
    $textoLiteral = unirLista($literales);
    $textoTiposNombres = mb_strtoupper(implode(" / ", $tiposArray), 'UTF-8');
    $textoOficios = unirLista($oficios);
    
    $palabraLiteral = count($literales) > 1 ? "sus literales" : "su literal";
    $palabraLiteralP = count($literales) > 1 ? "literales" : "literal";

    $nombresTextArray = []; $deptos = []; $emails = [];
    $isGroup = count($docentes_list) > 1;
    $allFemale = true; 

    foreach ($docentes_list as $d) {
        $sexo = strtoupper(trim($d['sexo'] ?? ''));
        if ($sexo !== 'F') $allFemale = false; 

        $txtIdentificado = ($sexo === 'F') ? "identificada" : "identificado";
        $nombresTextArray[] = mb_strtoupper($d['profe_nombre'], 'UTF-8') . " {$txtIdentificado} con cédula de ciudadanía N°" . $d['documento_tercero'];
        
        $nomDepto = mb_convert_case(mb_strtolower(trim($d['departamento']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $nomDepto = str_replace([' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], $nomDepto);
        $deptos[] = $nomDepto;

        if (!empty($d['email'])) $emails[] = $d['email'];
    }

    if ($isGroup) {
        if ($allFemale) {
            $textoProfesores = "Las profesoras "; $textoUnProfesor = "unas profesoras";
            $textoDelProfesor = "de las profesoras relacionadas"; $textoAdscrito = "adscritas";
            $txtAlProfesor = "a las profesoras";
        } else {
            $textoProfesores = "Los profesores "; $textoUnProfesor = "unos profesores";
            $textoDelProfesor = "de los profesores relacionados"; $textoAdscrito = "adscritos";
            $txtAlProfesor = "a los profesores";
        }
        $palabraSolicito = "solicitaron"; $adv = "advirtiéndoles"; $auth = "sus autorizaciones expresas";
    } else {
        if ($allFemale) {
            $textoProfesores = "La profesora "; $textoUnProfesor = "una profesora";
            $textoDelProfesor = "de la profesora relacionada"; $textoAdscrito = "adscrita";
            $txtAlProfesor = "a la profesora";
        } else {
            $textoProfesores = "El profesor "; $textoUnProfesor = "un profesor";
            $textoDelProfesor = "del profesor relacionado"; $textoAdscrito = "adscrito";
            $txtAlProfesor = "al profesor";
        }
        $palabraSolicito = "solicitó"; $adv = "advirtiéndole"; $auth = "su autorización expresa";
    }

    $nomFacultad = mb_convert_case(mb_strtolower(trim($facultad), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nomFacultad = str_replace([' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], $nomFacultad);
    
    $textoNombresList = unirLista($nombresTextArray);
    $deptosUnique = array_values(array_unique($deptos));
    $textoDeptos = unirLista($deptosUnique);
    $palabraDepto = count($deptosUnique) > 1 ? "a los Departamentos de" : "al Departamento de";
    $lblDepto = count($deptosUnique) > 1 ? "DEPARTAMENTOS DE " : "DEPARTAMENTO DE ";
    
    $correosTexto = empty($emails) ? "No registrado" : implode("; ", array_unique($emails));

    // --- IMPRESIÓN DEL DOCUMENTO ---
    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº {$vars['num_res']} DE {$vars['ano']}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$vars['dia']} de {$vars['mes']})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1); // RESTAURADO A 1

    $section->addText("Por la cual se reconocen puntos a la Base – Salarial a {$textoUnProfesor} de la Universidad del Cauca, por concepto de productividad académica {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1); // RESTAURADO A 1

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1); // RESTAURADO A 1
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1); // RESTAURADO A 1

    $section->addText("El Estatuto del Profesor Universitario – Acuerdo 024 de 1993, reglamenta los integrantes, funciones y criterios de asignación y reconocimiento de puntos del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectoría Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en {$palabraLiteral} “{$textoLiteral}” los topes por producción {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$textoProfesores}{$textoNombresList}, {$textoAdscrito} {$palabraDepto} {$textoDeptos} de la {$nomFacultad}, {$palabraSolicito} al Comité de Personal Docente de su facultad, el reconocimiento por productividad académica {$textoConcepto}, y a su vez el CPD remitió al CIARP mediante oficio N° {$textoOficios}.", 'StyleNormal');

    $section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$vars['dia']} de {$vars['mes']} de {$vars['ano']}, previo a la asignación de los puntajes correspondientes y con fundamento en el concepto de los Comités de Personal Docente de las facultad antes mencionada y la clasificación realizada por MINCIENCIAS, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, y {$palabraLiteralP} {$textoLiteral}, que establece el reconocimiento {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("Decidiéndose por el citado Comité otorgar el puntaje que a continuación se enuncia:", 'StyleNormal');

    $section->addText("Puntaje por base salarial: [ {$textoTiposNombres} ]", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold'); 
    
    if (count($puntosArray) > 1) {
        $strPuntos = "RECONOCER " . unirLista($puntosArray) . " PUNTOS. TOTAL A RECONOCER " . $puntajeTotal . " PUNTOS.";
    } else {
        $strPuntos = "RECONOCER " . $puntajeTotal . " PUNTOS.";
    }

    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . " " . $strPuntos, 'StyleNormal');
    }
    $section->addTextBreak(1); // RESTAURADO A 1

    // TABLAS (Separadas correctamente)
    foreach ($articulos_list as $art) {
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

        // ESTE ERA EL PROBLEMA: ESTABA EN 0, AHORA ES 1 PARA SEPARAR TABLAS
        $section->addTextBreak(1); 
    }

    // --- PARTE RESOLUTIVA (AUTO-NUMERACIÓN) ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $numStr = [1 => 'PRIMERO', 2 => 'SEGUNDO', 3 => 'TERCERO', 4 => 'CUARTO', 5 => 'QUINTO', 6 => 'SEXTO'];
    $n = 1;

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO " . $numStr[$n++] . ". ", 'StyleBold');
    $runR1->addText("Reconocer puntos a la base salarial {$textoDelProfesor} a continuación, conforme al producto mencionado en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, y {$palabraLiteralP} {$textoLiteral}, que establece el reconocimiento {$textoConcepto}; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');

    $section->addText("Puntaje por base salarial:", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . " " . $strPuntos, 'StyleNormal');
    }
    $section->addTextBreak(1); // RESTAURADO A 1

    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO " . $numStr[$n++] . ". ", 'StyleBold');
    $runR2->addText("El puntaje asignado {$txtAlProfesor} tendrá efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, {$palabraLiteralP} {$textoLiteral}, del Decreto 1279 de 2002.", 'StyleNormal');

    if ($has_mdpi) {
        $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
        $runR3->addText("ARTÍCULO " . $numStr[$n++] . ". ", 'StyleBold');
        $runR3->addText("A la fecha del presente reconocimiento, el CIARP revisó la identificación de la revista en las bases de datos: SCOPUS, MIAR, DOAJ Y SCIMAGO, como también en PUBLINDEX y su registro DOI; sin que exista evidencia oficial de su catalogación como predadora o de las malas prácticas editoriales.", 'StyleNormal');
    }

    $runR_Notif = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR_Notif->addText("ARTÍCULO " . $numStr[$n++] . ". ", 'StyleBold');
    $runR_Notif->addText("Notificar el presente acto administrativo {$txtAlProfesor}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a {$auth} en el formato PM-FO-4-FOR-4, al correo {$correosTexto}; {$adv} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días hábiles siguientes a la fecha de la notificación.", 'StyleNormal');

    $runR_Com = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR_Com->addText("ARTÍCULO " . $numStr[$n++] . ". ", 'StyleBold');
    $runR_Com->addText("Comunicar el presente acto administrativo a la División de Gestión del Talento Humano, para efectos del reconocimiento y efecto en la liquidación de la nómina.", 'StyleNormal');
    $section->addTextBreak(1); // RESTAURADO A 1

    // FECHA Y FIRMAS DINÁMICAS (Con los espacios correctos)
    $section->addText("Se expide en Popayán, el {$vars['dia']} de {$vars['mes']} de {$vars['ano']}.", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("COMUNÍQUESE, NOTIFÍQUESE Y CÚMPLASE", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(2);

    // --- ESTILOS DE PÁRRAFO SIN ESPACIADO Y FUENTE PEQUEÑA CURSIVA ---
    $styleFirmaCenter = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
    $styleFirmaLeft = ['spaceAfter' => 0];
    $fontFirmaPequena = ['name' => 'Arial', 'size' => 8, 'italic' => true];

    // Imprimir firmas del Vicerrector/a (centradas y pegadas)
    $section->addText(mb_strtoupper($vars['nom_vicerrector'], 'UTF-8'), 'StyleBold', $styleFirmaCenter);
    $section->addText($vars['car_vicerrector'], 'StyleNormal', $styleFirmaCenter);
    $section->addText($vars['car_presidente'], 'StyleNormal', $styleFirmaCenter);
    
    $section->addTextBreak(1);
    
    // Imprimir Revisó y Elaboró (alineadas a la izquierda, pegadas, tamaño 8 y cursiva)
    $section->addText("Revisó: " . $vars['reviso'], $fontFirmaPequena, $styleFirmaLeft);
    $section->addText("Elaboró: " . $vars['elaboro'], $fontFirmaPequena, $styleFirmaLeft);

    $section->addPageBreak();
}

// 7. Descarga Segura
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Articulos_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>