<?php
/**
 * Generador de Resoluciones CIARP - Libros de Investigación y de Texto
 * Versión Inteligente (Plan A - Enterprise): 
 * - AGRUPACIÓN POR FIRMA COLECTIVA: Agrupa estrictamente por la combinación exacta de autores.
 * - Gramática dinámica para literales C y D.
 * - VARIABLES DINÁMICAS (NÚMEROS CONSECUTIVOS AUTOMÁTICOS).
 * - INCORPORA FECHA EXACTA DEL OFICIO (fecha_solicitud).
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

// =========================================================================
// 1. CAPTURA DE VARIABLES BASE DEL MODAL Y CONSECUTIVO
// =========================================================================

$base_num = null;
$len_num = 3; 
if (isset($_GET['num_resolucion']) && is_numeric(trim($_GET['num_resolucion']))) {
    $str_num = trim($_GET['num_resolucion']);
    $base_num = intval($str_num);
    $len_num = strlen($str_num); 
    
    // LIMPIEZA DE LOTE: Borramos los números anteriores de este paquete para empezar en limpio
    $stmt_clean = $conn->prepare("UPDATE libros SET num_resolucion = NULL WHERE identificador = ?");
    $stmt_clean->bind_param("s", $identificador);
    $stmt_clean->execute();
    $stmt_clean->close();
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
        l.id_libro,
        l.numero_oficio,
        l.fecha_solicitud,
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
    AND (l.estado IS NULL OR LOWER(TRIM(l.estado)) <> 'an')
    ORDER BY l.id_libro, t.nombre_completo
";

$res = $conn->query($sql);
if ($res->num_rows === 0) die("No se encontraron registros activos para el identificador: " . htmlspecialchars($identificador));

// --- 3. MOTOR DE CLASIFICACIÓN (NUEVA REGLA: POR FIRMA COLECTIVA EXACTA) ---
$book_authors = [];
$book_data = [];

while ($row = $res->fetch_assoc()) {
    $id_l = $row['id_libro'];
    
    if (!isset($book_data[$id_l])) {
        $book_data[$id_l] = $row; 
        $book_authors[$id_l] = [];
    }
    
    $book_authors[$id_l][] = [
        'documento_tercero' => $row['documento_tercero'],
        'profe_nombre' => $row['profe_nombre'],
        'sexo' => $row['sexo'],
        'email' => $row['email'],
        'departamento' => $row['departamento'],
        'facultad' => $row['facultad']
    ];
}

$resoluciones_grupos = [];

foreach ($book_data as $id_l => $lib_info) {
    $autores = $book_authors[$id_l];
    
    // Huella Digital de los Autores (Ej: 10292635_98396856)
    $ids = array_column($autores, 'documento_tercero');
    sort($ids);
    $author_key = implode('_', $ids);
    
    $tipo_libro = mb_strtolower(trim($lib_info['tipo_libro']), 'UTF-8');
    $facultad = $autores[0]['facultad']; 
    
    // Llave maestra del grupo
    $grupo_key = $tipo_libro . '|' . $facultad . '|' . $author_key;
    
    if (!isset($resoluciones_grupos[$grupo_key])) {
        $resoluciones_grupos[$grupo_key] = [
            'docentes' => $autores,
            'libros' => [],
            'facultad' => $facultad
        ];
    }
    
    $resoluciones_grupos[$grupo_key]['libros'][] = $lib_info;
}

// --- 4. CONFIGURACIÓN BASE DE WORD ---
$phpWord = new PhpWord();
$phpWord->addFontStyle('StyleBold', ['bold' => true, 'name' => 'Arial', 'size' => 11]);
$phpWord->addFontStyle('StyleNormal', ['name' => 'Arial', 'size' => 11]);
$styleTable = ['borderSize' => 6, 'borderColor' => '000000', 'cellMarginTop' => 20, 'cellMarginBottom' => 20, 'cellMarginLeft' => 80, 'cellMarginRight' => 80]; 
$phpWord->addFontStyle('FontTableBold', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
$phpWord->addFontStyle('FontTableNormal', ['name' => 'Arial', 'size' => 9]);
$phpWord->addParagraphStyle('ParaTable', ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0]);

$vars = [
    'dia' => $textoFecha, 'mes' => $textoMes, 'ano' => $textoAno,
    'nom_vicerrector' => $nombre_vicerrector, 'car_vicerrector' => $cargo_vicerrector,
    'car_presidente' => $cargo_presidente, 'reviso' => $nombre_reviso, 'elaboro' => $nombre_elaboro
];

// PREPARACIÓN SQL LIMPIA (7 Parámetros = ssssssi)
$sql_update = "UPDATE libros SET num_resolucion = ?, fecha_resolucion = ?, nombre_vicerrector = ?, genero_vicerrector = ?, nombre_reviso = ?, nombre_elaboro = ? WHERE id_libro = ?";
$stmt_upd = $conn->prepare($sql_update);

// --- 5. BUCLE DE GENERACIÓN Y ACTUALIZACIÓN CONSECUTIVA ---
foreach ($resoluciones_grupos as $grupo_key => $grupo) {
    
    // Calcular el consecutivo actual
    $assigned_num_str = '____';
    $param_num = null;
    if ($base_num !== null) {
        $assigned_num_str = str_pad($base_num, $len_num, "0", STR_PAD_LEFT);
        $param_num = $assigned_num_str;
        $base_num++; 
    }

    // Actualizamos la base de datos SÓLO para los libros de este grupo exacto
    foreach ($grupo['libros'] as $l) {
        $id_update = $l['id_libro'];
        // 6 variables string, 1 variable int = "ssssssi"
        $stmt_upd->bind_param("ssssssi", $param_num, $db_fecha_res, $nombre_vicerrector, $genero_vicerrector, $nombre_reviso, $nombre_elaboro, $id_update);
        $stmt_upd->execute();
    }

    $vars['num_res'] = $assigned_num_str;

    generarResolucionLibro($phpWord, $grupo['docentes'], $grupo['libros'], $grupo['facultad'], $styleTable, $vars);
}

if ($stmt_upd) $stmt_upd->close();

// --- 6. FUNCIÓN DE RENDERIZADO ---
function generarResolucionLibro($phpWord, $docentes_list, $libros_list, $facultad, $styleTable, $vars) {
    
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

    // --- LÓGICA DE DETECCIÓN DE LITERALES Y OFICIOS (CON FECHA) ---
    $conceptos = [];
    $literales = [];
    $oficios_formateados = []; 
    $puntajeTotal = 0;

    foreach ($libros_list as $lib) {
        $puntajeTotal += floatval($lib['puntaje']);
        
        // Conversión de fecha del oficio a texto natural
        $fechaStr = "";
        if (!empty($lib['fecha_solicitud']) && $lib['fecha_solicitud'] !== '0000-00-00') {
            $time = strtotime($lib['fecha_solicitud']);
            $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
            $d = date('d', $time);
            $m = $meses[date('n', $time) - 1];
            $y = date('Y', $time);
            $fechaStr = " del $d de $m de $y";
        }
        
        $strOficio = trim($lib['numero_oficio']) . $fechaStr;
        if (!in_array($strOficio, $oficios_formateados)) {
            $oficios_formateados[] = $strOficio;
        }
        
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
    
    $textoConcepto = unirLista($conceptos);
    $textoLiteral = unirLista($literales);
    $textoOficios = unirLista($oficios_formateados);
    $palabraOficio = count($oficios_formateados) > 1 ? "los oficios N°" : "el oficio N°";

    $palabraLiteral = count($literales) > 1 ? "sus literales" : "su literal";
    $palabraLiteralP = count($literales) > 1 ? "literales" : "literal";

    // --- PREPARACIÓN DE DATOS CON NOMBRE PROPIO Y GRAMÁTICA DE GÉNERO ---
    $nombresTextArray = []; 
    $deptos = []; 
    $emails = [];
    $isGroup = count($docentes_list) > 1;
    $allFemale = true; 
    
    foreach ($docentes_list as $d) {
        $sexo = strtoupper(trim($d['sexo'] ?? ''));
        if ($sexo !== 'F') {
            $allFemale = false; 
        }
        
        $txtIdentificado = ($sexo === 'F') ? "identificada" : "identificado";
        $nombresTextArray[] = mb_strtoupper($d['profe_nombre'], 'UTF-8') . " {$txtIdentificado} con cédula de ciudadanía N°" . $d['documento_tercero'];
        
        $nomDepto = mb_convert_case(mb_strtolower(trim($d['departamento']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $nomDepto = str_replace(
            [' De ', ' Del ', ' Y ', ' La ', ' Las ', ' El ', ' Los ', ' En '], 
            [' de ', ' del ', ' y ', ' la ', ' las ', ' el ', ' los ', ' en '], 
            $nomDepto
        );
        $deptos[] = $nomDepto;

        if (!empty($d['email'])) $emails[] = $d['email'];
    }
    
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

    // --- IMPRESIÓN DEL DOCUMENTO ---
    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº {$vars['num_res']} DE {$vars['ano']}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$vars['dia']} de {$vars['mes']})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);

    $section->addText("Por la cual se reconocen puntos a la Base – Salarial a {$textoUnProfesor} de la Universidad del Cauca, por concepto de {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(0);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(0);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);

    $section->addText("El Estatuto del profesor Universitario – Acuerdo 024 de 1993, reglamenta las funciones del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en {$palabraLiteral} {$textoLiteral}, topes por producción en {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    // PÁRRAFO CON LA FECHA EXACTA DEL OFICIO 
    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$textoProfesores}{$textoNombresList}, {$textoAdscrito} {$palabraDepto} {$textoDeptos} de la {$nomFacultad}, {$palabraSolicito} al Comité de Personal Docente de la facultad el reconocimiento por productividad académica, y a su vez, el CPD remitió al CIARP mediante {$palabraOficio} {$textoOficios}.", 'StyleNormal');

    $section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$vars['dia']} de {$vars['mes']} de {$vars['ano']}, previo a la asignación del puntaje correspondiente y con fundamento en el concepto del Comité de Personal Docente de la facultad antes mencionada, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, {$palabraLiteralP} {$textoLiteral}, que establece el reconocimiento de {$textoConcepto}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("Decidiéndose por el citado Comité otorgar los puntajes que a continuación se enuncian:", 'StyleNormal');

    $section->addText("Puntaje por base salarial:", 'StyleBold');
    
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'], 'StyleNormal');
    }
    $section->addTextBreak(0);

    // TABLAS (Sin negrita en los campos laterales)
    foreach ($libros_list as $lib) {
        $table = $section->addTable($styleTable);
        
        $table->addRow();
        $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NO. OFICIO", 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText($lib['numero_oficio'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $tipoStr = !empty($lib['tipo_libro']) ? mb_strtoupper($lib['tipo_libro'], 'UTF-8') : "INVESTIGACIÓN";
        $table->addCell(3000)->addText("LIBRO DE " . $tipoStr, 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($lib['titulo_libro'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("ISBN DIGITAL", 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText($lib['isbn'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("EDITORIAL", 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($lib['nombre_editorial'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("AÑO", 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText($lib['ano_publicacion'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("AUTORES", 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText($lib['numero_autores'], 'FontTableNormal', 'ParaTable');

        $ev1 = floatval($lib['evaluacion_1']);
        $ev2 = floatval($lib['evaluacion_2']);
        $suma = $ev1 + $ev2;
        $promedio = $suma / 2;
        $textoEvaluacion = "{$ev1} + {$ev2} = {$suma} / 2 = {$promedio}%";

        $table->addRow();
        $table->addCell(3000)->addText("EVALUACIONES", 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText($textoEvaluacion, 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("RECONOCER", 'FontTableNormal', 'ParaTable');
        $table->addCell(6000)->addText($lib['puntaje'] . " PUNTOS", 'FontTableBold', 'ParaTable');

        $section->addTextBreak(0);
    }

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
    $runR1->addText("Reconocer puntos a la base salarial {$textoDelProfesor} a continuación, conforme al producto mencionado en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, {$palabraLiteralP} {$textoLiteral}, que establece el reconocimiento de {$textoConcepto}; cuyos efectos fiscales surtirán a partir de la expedición del presente acto administrativo.", 'StyleNormal');

    $section->addText("Puntaje por base salarial:", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . " RECONOCER " . $puntajeTotal . " PUNTOS", 'StyleNormal');
    }
    $section->addTextBreak(0);

    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
    $runR2->addText("El puntaje asignado {$txtAlProfesor} tendrá efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, {$palabraLiteralP} {$textoLiteral}, del Decreto 1279 de 2002.", 'StyleNormal');

    $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR3->addText("ARTÍCULO TERCERO. ", 'StyleBold');
    $runR3->addText("Notificar el presente acto administrativo {$txtAlProfesor}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a {$auth} en el formato PM-FO-4-FOR-4, al correo {$correosTexto}; {$adv} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días hábiles siguientes a la fecha de la notificación.", 'StyleNormal');

    $runR4 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR4->addText("ARTÍCULO CUARTO. ", 'StyleBold');
    $runR4->addText("Comunicar el presente acto administrativo a la División de Gestión del Talento Humano, para efectos del reconocimiento y efecto en la liquidación de la nómina.", 'StyleNormal');
    $section->addTextBreak(0);

    // FIRMAS Y FECHA DINÁMICA
    $section->addText("Se expide en Popayán, el {$vars['dia']} de {$vars['mes']} de {$vars['ano']}.", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("COMUNÍQUESE, NOTIFÍQUESE Y CÚMPLASE", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(2);

    // --- ESTILOS DE PÁRRAFO SIN ESPACIADO Y FUENTE PEQUEÑA CURSIVA ---
    $styleFirmaCenter = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
    $styleFirmaLeft = ['spaceAfter' => 0];
    $fontFirmaPequena = ['name' => 'Arial', 'size' => 8, 'italic' => true];

    // Imprimir firmas del Vicerrector (centradas y pegadas)
    $section->addText(mb_strtoupper($vars['nom_vicerrector'], 'UTF-8'), 'StyleBold', $styleFirmaCenter);
    $section->addText($vars['car_vicerrector'], 'StyleNormal', $styleFirmaCenter);
    $section->addText($vars['car_presidente'], 'StyleNormal', $styleFirmaCenter);
    
    $section->addTextBreak(1); 
    
    // Imprimir Revisó y Elaboró (alineadas a la izquierda, pegadas, tamaño 8 y cursiva)
    $section->addText("Revisó: " . $vars['reviso'], $fontFirmaPequena, $styleFirmaLeft);
    $section->addText("Elaboró: " . $vars['elaboro'], $fontFirmaPequena, $styleFirmaLeft);

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