<?php
/**
 * Generador de Resoluciones CIARP - Productividad por PREMIOS
 * Versión Inteligente (Plan A): 
 * - Consolida múltiples premios por docente.
 * - Agrupa a varios profesores si ganaron el mismo premio (coautores/equipo).
 * - Gramática de Género (F/M) y Nombres Propios para Departamentos/Facultades.
 * - VARIABLES DINÁMICAS Y CONSECUTIVOS AUTOMÁTICOS.
 */
require 'conn.php';
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

if (!function_exists('unirLista')) {
    function unirLista($items) {
        if (count($items) == 0) return "";
        if (count($items) == 1) return $items[0];
        if (count($items) == 2) return $items[0] . " y " . $items[1];
        $ultimo = array_pop($items);
        return implode(", ", $items) . " y " . $ultimo;
    }
}

$identificador = isset($_GET['cuadro_identificador_premio']) ? trim($_GET['cuadro_identificador_premio']) : '';
if (empty($identificador)) die("Identificador requerido.");

// =========================================================================
// 1. CAPTURA DE VARIABLES BASE DEL MODAL Y CONSECUTIVO
// =========================================================================
$base_num = null;
$len_num = 3; 
if (isset($_GET['num_resolucion']) && is_numeric(trim($_GET['num_resolucion']))) {
    $str_num = trim($_GET['num_resolucion']);
    $base_num = intval($str_num);
    $len_num = strlen($str_num); // Guarda la longitud para mantener los ceros (ej: 045)
}

$fecha_input = isset($_GET['fecha_resolucion']) ? trim($_GET['fecha_resolucion']) : '';
$textoFecha = "____";
$textoMes = "________";
$textoAno = "____";
$db_fecha_res = null;

if (!empty($fecha_input)) {
    $db_fecha_res = $fecha_input;
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


// --- 2. CONSULTA PLANA ---
$sql = "
    SELECT 
        p.id AS id_premio,
        p.numero_oficio,
        p.fecha_solicitud, 
        p.nombre_evento,
        p.ambito,
        p.categoria_premio,
        p.nivel_ganado,
        p.lugar_fecha,
        p.puntos,
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo,
        t.email,
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento
    FROM premios p
    JOIN premios_profesor pp ON p.id = pp.id_premio
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
$seen_premios = [];

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    $id_premio = $row['id_premio'];
    
    if (!isset($prof_records[$cc])) {
        $prof_records[$cc] = [];
        $seen_premios[$cc] = [];
    }
    if (!in_array($id_premio, $seen_premios[$cc])) {
        $prof_records[$cc][] = $row;
        $seen_premios[$cc][] = $id_premio;
    }
}

$prof_multiples = [];
$premios_grupales = [];

foreach ($prof_records as $cc => $premios) {
    if (count($premios) > 1) {
        $prof_multiples[$cc] = $premios;
    } else {
        $row = $premios[0];
        $id_premio = $row['id_premio'];
        $fac = $row['facultad'];
        
        if (!isset($premios_grupales[$id_premio])) $premios_grupales[$id_premio] = [];
        if (!isset($premios_grupales[$id_premio][$fac])) $premios_grupales[$id_premio][$fac] = [];
        
        $premios_grupales[$id_premio][$fac][] = $row;
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

// Empaquetamos variables para pasarlas a la función
$vars = [
    'dia' => $textoFecha, 'mes' => $textoMes, 'ano' => $textoAno,
    'nom_vicerrector' => $nombre_vicerrector, 'car_vicerrector' => $cargo_vicerrector,
    'car_presidente' => $cargo_presidente, 'reviso' => $nombre_reviso, 'elaboro' => $nombre_elaboro
];

$stmt_upd = $conn->prepare("UPDATE premios SET num_resolucion = ?, fecha_resolucion = ?, nombre_vicerrector = ?, genero_vicerrector = ?, nombre_reviso = ?, nombre_elaboro = ? WHERE id = ?");

// --- 5. BUCLE DE GENERACIÓN ---

// Múltiples premios por profesor
foreach ($prof_multiples as $cc => $premios) {
    
    $assigned_num_str = '____';
    $param_num = null;
    if ($base_num !== null) {
        $assigned_num_str = str_pad($base_num, $len_num, "0", STR_PAD_LEFT);
        $param_num = $assigned_num_str;
        $base_num++; 
    }

    foreach ($premios as $pre) {
        $id_update = $pre['id_premio'];
        $stmt_upd->bind_param("ssssssi", $param_num, $db_fecha_res, $nombre_vicerrector, $genero_vicerrector, $nombre_reviso, $nombre_elaboro, $id_update);
        $stmt_upd->execute();
    }

    $vars['num_res'] = $assigned_num_str;

    $docentes_list = [[
        'documento_tercero' => $cc,
        'profe_nombre' => $premios[0]['profe_nombre'],
        'sexo' => $premios[0]['sexo'],
        'email' => $premios[0]['email'],
        'departamento' => $premios[0]['departamento']
    ]];
    generarResolucionPremio($phpWord, $docentes_list, $premios, $premios[0]['facultad'], $styleTable, $vars);
}

// Premios grupales/individuales
foreach ($premios_grupales as $id_premio => $facultades) {
    foreach ($facultades as $fac => $profesores) {
        
        $assigned_num_str = '____';
        $param_num = null;
        if ($base_num !== null) {
            $assigned_num_str = str_pad($base_num, $len_num, "0", STR_PAD_LEFT);
            $param_num = $assigned_num_str;
            $base_num++; 
        }

        $stmt_upd->bind_param("ssssssi", $param_num, $db_fecha_res, $nombre_vicerrector, $genero_vicerrector, $nombre_reviso, $nombre_elaboro, $id_premio);
        $stmt_upd->execute();

        $vars['num_res'] = $assigned_num_str;

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
        $premios_list = [$profesores[0]]; // La data del premio es igual para todos
        generarResolucionPremio($phpWord, $docentes_list, $premios_list, $fac, $styleTable, $vars);
    }
}
if ($stmt_upd) $stmt_upd->close();

// --- 6. FUNCIÓN DE RENDERIZADO ---
function generarResolucionPremio($phpWord, $docentes_list, $premios_list, $facultad, $styleTable, $vars) {
    $section = $phpWord->addSection(['paperSize' => 'Folio', 'marginTop' => 3000, 'marginLeft' => 1701, 'marginRight' => 1701, 'marginBottom' => 1417, 'footerHeight' => 500]);

    $header = $section->addHeader(); $tableHeader = $header->addTable(); $tableHeader->addRow();
    $tableHeader->addCell(8000)->addImage('img/encabezadob.png', ['width' => 170, 'alignment' => Jc::LEFT]);

    $footer = $section->addFooter(); $tableFooter = $footer->addTable(); $tableFooter->addRow();
    $tableFooter->addCell(10000)->addImage('img/PIEb.png', ['width' => 430, 'alignment' => Jc::LEFT]);

    $oficios_formateados = []; 
    $puntajeTotal = 0;

    foreach ($premios_list as $pre) {
        $puntajeTotal += floatval($pre['puntos']);
        
        $fechaStr = "";
        if (!empty($pre['fecha_solicitud']) && $pre['fecha_solicitud'] !== '0000-00-00') {
            $time = strtotime($pre['fecha_solicitud']);
            $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
            $fechaStr = " del " . date('d', $time) . " de " . $meses[date('n', $time) - 1] . " de " . date('Y', $time);
        }
        
        $strOficio = trim($pre['numero_oficio']) . $fechaStr;
        if (!in_array($strOficio, $oficios_formateados)) $oficios_formateados[] = $strOficio;
    }
    
    $textoOficios = unirLista($oficios_formateados);
    $palabraOficio = count($oficios_formateados) > 1 ? "los oficios N°" : "el oficio N°";

    $nombresTextArray = []; $deptos = []; $emails = [];
    $isGroup = count($docentes_list) > 1; $allFemale = true; 
    
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
    
    if ($isGroup) {
        $textoProfesores = $allFemale ? "Las profesoras " : "Los profesores ";
        $textoUnProfesor = $allFemale ? "unas profesoras" : "unos profesores";
        $textoDelProfesor = $allFemale ? "de las profesoras relacionadas" : "de los profesores relacionados";
        $textoAdscrito = $allFemale ? "adscritas" : "adscritos";
        $txtAlProfesor = $allFemale ? "a las profesoras" : "a los profesores";
        $palabraSolicito = "solicitaron al Comité de Personal Docente de su facultad, reconocimiento por concepto de Premio, quienes remitieron";
        $adv = "advirtiéndoles"; $auth = "sus autorizaciones expresas";
    } else {
        $textoProfesores = $allFemale ? "La profesora " : "El profesor ";
        $textoUnProfesor = $allFemale ? "una profesora" : "un profesor";
        $textoDelProfesor = $allFemale ? "de la profesora relacionada" : "del profesor relacionado";
        $textoAdscrito = $allFemale ? "adscrita" : "adscrito";
        $txtAlProfesor = $allFemale ? "a la profesora" : "al profesor";
        $palabraSolicito = "solicitó al Comité de Personal Docente de su facultad, reconocimiento por concepto de Premio, quien remitió";
        $adv = "advirtiéndole"; $auth = "su autorización expresa";
    }

    $nomFacultad = mb_convert_case(mb_strtolower(trim($facultad), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nomFacultad = str_replace([' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], $nomFacultad);
    
    $textoNombresList = unirLista($nombresTextArray);
    $deptosUnique = array_values(array_unique($deptos));
    $textoDeptos = unirLista($deptosUnique);
    $palabraDepto = count($deptosUnique) > 1 ? "a los Departamentos de" : "al Departamento de";
    $lblDepto = count($deptosUnique) > 1 ? "DEPARTAMENTOS DE " : "DEPARTAMENTO DE ";
    $correosTexto = empty($emails) ? "No registrado" : implode("; ", array_unique($emails));

    // IMPRESIÓN DEL DOCUMENTO
    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº {$vars['num_res']} DE {$vars['ano']}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$vars['dia']} de {$vars['mes']})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("Por la cual se reconocen puntos a la Base –Salarial a {$textoUnProfesor} de la Universidad del Cauca, por concepto de productividad académica por Premios.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("El Estatuto del Profesor Universitario – Acuerdo 024 de 1993, reglamenta las funciones del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en su literal f, los topes por Premios Nacionales o Internacionales.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$textoProfesores}{$textoNombresList}, {$textoAdscrito} {$palabraDepto} {$textoDeptos} de la {$nomFacultad}, {$palabraSolicito} al CIARP mediante {$palabraOficio} {$textoOficios}.", 'StyleNormal');

    $section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$vars['dia']} de {$vars['mes']} de {$vars['ano']}, previo a la asignación de los puntajes correspondientes y con fundamento en el concepto del Comité de Personal Docente de la facultad antes mencionada y la clasificación realizada por MINCIENCIAS, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, literal f, que establece el reconocimiento por Premios Nacionales o Internacionales.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("Decidiéndose por el citado Comité otorgar el puntaje que a continuación se enuncian:", 'StyleNormal');

    $section->addText("Puntaje por base salarial: Premios", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'], 'StyleBold');
    }
    $section->addTextBreak(1);

    // TABLAS PARA PREMIOS (Con addTextBreak en 1 para separarlas)
    foreach ($premios_list as $premio) {
        $table = $section->addTable($styleTable);
        
        $table->addRow();
        $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NO. OFICIO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($premio['numero_oficio'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("EVENTO PREMIO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($premio['nombre_evento'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("ÁMBITO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($premio['ambito'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("CATEGORÍA PREMIO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($premio['categoria_premio'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NIVEL GANADO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($premio['nivel_ganado'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("LUGAR Y FECHA", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($premio['lugar_fecha'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("RECONOCER", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(floatval($premio['puntos']) . " PUNTOS", 'FontTableBold', 'ParaTable');

        $section->addTextBreak(1); // Mantiene las tablas separadas
    }

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
    $runR1->addText("Reconocer puntos a la base salarial {$textoDelProfesor} a continuación, conforme al producto mencionado en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, literal f, que establece el reconocimiento por Premios Nacionales o Internacionales; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');

    $section->addText("Puntaje por base salarial: Premios", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . " RECONOCER " . $puntajeTotal . " PUNTOS", 'StyleBold');
    }
    $section->addTextBreak(1);

    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
    $runR2->addText("El puntaje asignado tendrá efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, literal f, del Decreto 1279 de 2002.", 'StyleNormal');

    $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR3->addText("ARTÍCULO TERCERO. ", 'StyleBold');
    $runR3->addText("Notificar el presente acto administrativo {$txtAlProfesor}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico conforme a {$auth} en el formato PM-FO-4-FOR-4, al correo {$correosTexto}; {$adv} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días hábiles siguientes a la fecha de la notificación.", 'StyleNormal');

    $runR4 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR4->addText("ARTÍCULO CUARTO. ", 'StyleBold');
    $runR4->addText("Comunicar el presente acto administrativo a la División de Gestión del Talento Humano, para efectos del reconocimiento y efecto en la liquidación de la nómina.", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("Se expide en Popayán, el {$vars['dia']} de {$vars['mes']} de {$vars['ano']}.", 'StyleNormal');
    $section->addTextBreak(2);

    $section->addText("COMUNÍQUESE, NOTIFÍQUESE Y CÚMPLASE", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(3);

    // ESTILOS DE FIRMA
    $styleFirmaCenter = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
    $styleFirmaLeft = ['spaceAfter' => 0];
    $fontFirmaPequena = ['name' => 'Arial', 'size' => 8, 'italic' => true]; 

    $section->addText(mb_strtoupper($vars['nom_vicerrector'], 'UTF-8'), 'StyleBold', $styleFirmaCenter);
    $section->addText($vars['car_vicerrector'], 'StyleNormal', $styleFirmaCenter);
    $section->addText($vars['car_presidente'], 'StyleNormal', $styleFirmaCenter);
    
    $section->addTextBreak(1);
    
    $section->addText("Revisó: " . $vars['reviso'], $fontFirmaPequena, $styleFirmaLeft);
    $section->addText("Elaboró: " . $vars['elaboro'], $fontFirmaPequena, $styleFirmaLeft);

    $section->addPageBreak();
}

// --- 7. DESCARGA SEGURA ---
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Premios_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>