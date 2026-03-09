<?php
/**
 * Generador de Resoluciones CIARP - Bonificación por Publicaciones
 * Versión Inteligente (Plan A): 
 * - Consolida múltiples publicaciones por docente.
 * - Agrupa coautores.
 * - Gramática de Género y Nombres Propios.
 * - Basado en el Acuerdo 078 de 2002 (Bonificaciones).
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

$identificador = isset($_GET['cuadro_identificador_pub_bon']) ? trim($_GET['cuadro_identificador_pub_bon']) : '';
if (empty($identificador)) die("Identificador requerido.");

// --- 2. CONSULTA PLANA ---
$sql = "
    SELECT 
        pb.id AS id_pub,
        pb.numeroOficio,
        pb.tipo_producto,
        pb.nombre_revista,
        pb.producto,
        pb.isbn,
        pb.fecha_publicacion,
        pb.lugar_publicacion,
        pb.autores AS numero_autores,
        pb.evaluacion1,
        pb.evaluacion2,
        pb.puntaje_final AS puntos,
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo,
        t.email,
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento
    FROM publicacion_bon pb
    JOIN publicacion_bon_profesor pbp ON pb.id = pbp.id_publicacion_bon
    JOIN tercero t ON pbp.documento_profesor = t.documento_tercero
    JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE pb.identificador_completo = '" . $conn->real_escape_string($identificador) . "'
    ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.nombre_completo
";

$res = $conn->query($sql);
if ($res->num_rows === 0) die("No se encontraron registros activos para el identificador: " . htmlspecialchars($identificador));

// --- 3. MOTOR DE CLASIFICACIÓN (PLAN A) ---
$prof_records = [];
$seen_pubs = [];

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    $id_pub = $row['id_pub'];
    
    if (!isset($prof_records[$cc])) {
        $prof_records[$cc] = [];
        $seen_pubs[$cc] = [];
    }
    if (!in_array($id_pub, $seen_pubs[$cc])) {
        $prof_records[$cc][] = $row;
        $seen_pubs[$cc][] = $id_pub;
    }
}

$prof_multiples = [];
$pubs_grupales = [];

foreach ($prof_records as $cc => $pubs) {
    if (count($pubs) > 1) {
        $prof_multiples[$cc] = $pubs;
    } else {
        $row = $pubs[0];
        $id_pub = $row['id_pub'];
        $fac = $row['facultad'];
        
        if (!isset($pubs_grupales[$id_pub])) $pubs_grupales[$id_pub] = [];
        if (!isset($pubs_grupales[$id_pub][$fac])) $pubs_grupales[$id_pub][$fac] = [];
        
        $pubs_grupales[$id_pub][$fac][] = $row;
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
foreach ($prof_multiples as $cc => $pubs) {
    $docentes_list = [[
        'documento_tercero' => $cc,
        'profe_nombre' => $pubs[0]['profe_nombre'],
        'sexo' => $pubs[0]['sexo'],
        'email' => $pubs[0]['email'],
        'departamento' => $pubs[0]['departamento']
    ]];
    generarResolucionBonificacion($phpWord, $docentes_list, $pubs, $pubs[0]['facultad'], $styleTable);
}

foreach ($pubs_grupales as $id_pub => $facultades) {
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
        $pubs_list = [$profesores[0]]; 
        generarResolucionBonificacion($phpWord, $docentes_list, $pubs_list, $fac, $styleTable);
    }
}

// --- 6. FUNCIÓN DE RENDERIZADO ---
function generarResolucionBonificacion($phpWord, $docentes_list, $pubs_list, $facultad, $styleTable) {
    
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

    // PREPARACIÓN DATOS PUBLICACIONES Y CONCEPTOS
    $oficios = []; 
    $puntajeTotal = 0;
    $conceptos_bd = [];

    foreach ($pubs_list as $pub) {
        $puntajeTotal += floatval($pub['puntos']);
        $oficios[] = $pub['numeroOficio'];
        
        $t = mb_strtolower(trim($pub['tipo_producto']), 'UTF-8');
        if (strpos($t, 'material_soporte') !== false) {
            $conceptos_bd[] = "Material de Soporte a la Docencia";
        } elseif (strpos($t, 'articulo_no_indexado') !== false) {
            $conceptos_bd[] = "Artículos No Indexados";
        } else {
            $conceptos_bd[] = "Publicaciones impresas universitarias";
        }
    }
    $oficios = array_unique($oficios);
    $textoOficios = unirLista($oficios);
    $strConceptos = unirLista(array_values(array_unique($conceptos_bd)));

    // PREPARACIÓN DATOS PROFESORES (GÉNERO Y NOMBRE PROPIO)
    $nombresTextArray = []; $deptos = []; $emails = [];
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
    
    $isGroup = count($docentes_list) > 1;

    // Gramática Dinámica
    if ($isGroup) {
        if ($allFemale) {
            $textoUnProfesor = "unas profesoras";
            $textoProfesores = "Las profesoras ";
            $textoAdscrito = "adscritas";
            $txtAlProfesor = "a las profesoras";
            $txtPresentaron = "presentaron";
        } else {
            $textoUnProfesor = "unos profesores";
            $textoProfesores = "Los profesores ";
            $textoAdscrito = "adscritos";
            $txtAlProfesor = "a los profesores";
            $txtPresentaron = "presentaron";
        }
        $adv = "advirtiéndoles";
        $auth = "sus autorizaciones expresas";
        $txtLaSiguiente = count($pubs_list) > 1 ? "las siguientes publicaciones:" : "la siguiente publicación:";
    } else {
        if ($allFemale) {
            $textoUnProfesor = "una profesora";
            $textoProfesores = "La profesora ";
            $textoAdscrito = "adscrita";
            $txtAlProfesor = "a la profesora";
            $txtPresentaron = "presentó";
        } else {
            $textoUnProfesor = "un profesor";
            $textoProfesores = "El profesor ";
            $textoAdscrito = "adscrito";
            $txtAlProfesor = "al profesor";
            $txtPresentaron = "presentó";
        }
        $adv = "advirtiéndole";
        $auth = "su autorización expresa";
        $txtLaSiguiente = count($pubs_list) > 1 ? "las siguientes publicaciones:" : "la siguiente publicación:";
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

    $section->addText("Por la cual se reconocen puntos por Bonificación a {$textoUnProfesor} de la Universidad del Cauca, por concepto de {$strConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("El Estatuto del Profesor Universitario – Acuerdo 024 de 1993, reglamenta los integrantes, funciones y criterios de asignación y reconocimiento de puntos del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, dispone en su capítulo IV que corresponde a los Consejos Superiores Universitarios establecer un sistema de bonificaciones no constitutivas de salario, las cuales se reconocen por una sola vez.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("El Consejo Superior mediante Acuerdo 078 de 2002, definió el sistema de bonificaciones por productividad académica para los profesores de planta de la Universidad del Cauca, prescribiendo en su artículo 4, literal c, la bonificación por {$strConceptos}.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$textoProfesores} {$textoNombresList}, {$textoAdscrito} {$palabraDepto} {$textoDeptos} de la {$nomFacultad}, {$txtPresentaron} para reconocimiento de puntos por bonificación {$txtLaSiguiente}", 'StyleNormal');
    $section->addTextBreak(1);

    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'], 'StyleBold');
    }
    $section->addTextBreak(1);

    // TABLAS PARA BONIFICACIONES
    foreach ($pubs_list as $pub) {
        $table = $section->addTable($styleTable);
        
        $table->addRow();
        $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NÚMERO DE OFICIO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pub['numeroOficio'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("TIPO DE PRODUCTO", 'FontTableBold', 'ParaTable');
        $t = mb_strtolower(trim($pub['tipo_producto']), 'UTF-8');
        $tipoStr = (strpos($t, 'material') !== false) ? "MATERIAL DE SOPORTE A LA DOCENCIA" : "PUBLICACIÓN IMPRESA / ARTÍCULO";
        $table->addCell(6000)->addText($tipoStr, 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NOMBRE DE LA REVISTA", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($pub['nombre_revista'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("PRODUCTO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($pub['producto'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("ISBN / ISSN", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pub['isbn'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("FECHA DE PUBLICACIÓN", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pub['fecha_publicacion'], 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("LUGAR DE PUBLICACIÓN", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($pub['lugar_publicacion'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("AUTORES", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pub['numero_autores'], 'FontTableNormal', 'ParaTable');

        // Evaluaciones (Promedio)
        $ev1 = floatval($pub['evaluacion1']);
        $ev2 = floatval($pub['evaluacion2']);
        $suma = $ev1 + $ev2;
        $promedio = $suma / 2;
        $textoEvaluacion = "{$ev1} + {$ev2} = {$suma} / 2 = {$promedio}%";

        $table->addRow();
        $table->addCell(3000)->addText("EVALUACIONES", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($textoEvaluacion, 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("RECONOCER", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($pub['puntos'] . " PUNTOS", 'FontTableBold', 'ParaTable');

        $section->addTextBreak(1);
    }

    $section->addText("El mencionado trabajo fue aprobado por los expertos designados por el Comité de Personal Docente de la {$nomFacultad}, quien remitió al CIARP mediante Oficio N° {$textoOficios}, la solicitud para lo concerniente al otorgamiento de puntos de conformidad con lo previsto en el Acuerdo 078 de 2002 artículo 4, literal c.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Comité Interno de Asignación y Reconocimiento de Puntaje – CIARP en sesión del {$textoFecha} de {$textoMes} de {$textoAno}, con fundamento en el concepto del Comité de Personal Docente de la facultad, decidió reconocer los puntos como lo dispone la norma.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO : ", 'StyleBold');
    $runR1->addText("Reconocer puntos por bonificación {$txtAlProfesor}, por concepto de {$strConceptos}, de conformidad con lo previsto en el artículo 4 literal c del Acuerdo 078 de 2002 y en la parte considerativa de la presente resolución.", 'StyleNormal');

    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        // Se añade espacio en blanco para que se pueda rellenar el valor en pesos manualmente
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . " Reconocer  " . $puntajeTotal . "  PUNTOS, por valor de $ ________________", 'StyleBold');
    }
    $section->addTextBreak(1);

    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO : ", 'StyleBold');
    $runR2->addText("Dicha bonificación se reconocerá al concluir la vigencia fiscal del año {$textoAno}.", 'StyleNormal');

    $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR3->addText("ARTÍCULO TERCERO : ", 'StyleBold');
    // Para bonificaciones se usa el formato PM-FO-4-FOR-3 (según plantilla)
    $runR3->addText("Notificar el presente acto administrativo {$txtAlProfesor}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a {$auth} en el formato PM-FO-4-FOR-3 al correo {$correosTexto}; {$adv} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días hábiles siguientes a la fecha de la presente notificación.", 'StyleNormal');

    $runR4 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR4->addText("ARTÍCULO CUARTO. ", 'StyleBold');
    $runR4->addText("Enviar copia del presente acto administrativo a la División de Gestión del Talento para el trámite correspondiente acorde a su competencia.", 'StyleNormal');
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

// --- 7. DESCARGA SEGURA ---
if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_Bonificacion_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>