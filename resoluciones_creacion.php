<?php
/**
 * Generador de Resoluciones CIARP - Creación Artística
 * Versión Inteligente (Plan A): 
 * - Consolida múltiples obras por docente.
 * - Agrupa coautores de la misma obra.
 * - Gramática de Género (F/M) y Nombres Propios para Departamentos/Facultades.
 * - Basado en Literal i, numeral 1 (Obras Artísticas).
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

$identificador = isset($_GET['cuadro_identificador_creacion']) ? trim($_GET['cuadro_identificador_creacion']) : '';
if (empty($identificador)) die("Identificador requerido.");

// --- 2. CONSULTA PLANA ---
$sql = "
    SELECT 
        c.id AS id_creacion,
        c.numeroOficio,
        c.tipo_producto,
        c.impacto,
        c.producto,
        c.nombre_evento,
        c.evento,
        c.lugar_evento,
        c.fecha_evento,
        c.fecha_evento_f,
        c.autores AS numero_autores,
        c.evaluacion1,
        c.evaluacion2,
        c.puntaje_final AS puntos,
        t.documento_tercero,
        t.nombre_completo as profe_nombre,
        t.sexo,
        t.email,
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento
    FROM creacion c
    JOIN creacion_profesor cp ON c.id = cp.id_creacion
    JOIN tercero t ON cp.documento_profesor = t.documento_tercero
    JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE c.identificador_completo = '" . $conn->real_escape_string($identificador) . "'
    AND (c.estado_creacion IS NULL OR c.estado_creacion <> 'an')
    ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.nombre_completo
";

$res = $conn->query($sql);
if ($res->num_rows === 0) die("No se encontraron registros activos para el identificador: " . htmlspecialchars($identificador));

// --- 3. MOTOR DE CLASIFICACIÓN (PLAN A) ---
$prof_records = [];
$seen_obras = [];

while ($row = $res->fetch_assoc()) {
    $cc = $row['documento_tercero'];
    $id_obra = $row['id_creacion'];
    
    if (!isset($prof_records[$cc])) {
        $prof_records[$cc] = [];
        $seen_obras[$cc] = [];
    }
    if (!in_array($id_obra, $seen_obras[$cc])) {
        $prof_records[$cc][] = $row;
        $seen_obras[$cc][] = $id_obra;
    }
}

$prof_multiples = [];
$obras_grupales = [];

foreach ($prof_records as $cc => $obras) {
    if (count($obras) > 1) {
        $prof_multiples[$cc] = $obras;
    } else {
        $row = $obras[0];
        $id_obra = $row['id_creacion'];
        $fac = $row['facultad'];
        
        if (!isset($obras_grupales[$id_obra])) $obras_grupales[$id_obra] = [];
        if (!isset($obras_grupales[$id_obra][$fac])) $obras_grupales[$id_obra][$fac] = [];
        
        $obras_grupales[$id_obra][$fac][] = $row;
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
foreach ($prof_multiples as $cc => $obras) {
    $docentes_list = [[
        'documento_tercero' => $cc,
        'profe_nombre' => $obras[0]['profe_nombre'],
        'sexo' => $obras[0]['sexo'],
        'email' => $obras[0]['email'],
        'departamento' => $obras[0]['departamento']
    ]];
    generarResolucionCreacion($phpWord, $docentes_list, $obras, $obras[0]['facultad'], $styleTable);
}

foreach ($obras_grupales as $id_obra => $facultades) {
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
        $obras_list = [$profesores[0]]; 
        generarResolucionCreacion($phpWord, $docentes_list, $obras_list, $fac, $styleTable);
    }
}

// --- 6. FUNCIÓN DE RENDERIZADO ---
function generarResolucionCreacion($phpWord, $docentes_list, $obras_list, $facultad, $styleTable) {
    
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

    // PREPARACIÓN DATOS OBRAS
    $oficios = []; 
    $puntajeTotal = 0;
    foreach ($obras_list as $obra) {
        $puntajeTotal += floatval($obra['puntos']);
        $oficios[] = $obra['numeroOficio'];
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
        $nombresTextArray[] = mb_strtoupper($d['profe_nombre'], 'UTF-8') . " {$txtIdentificado} con C.C. N° " . $d['documento_tercero'];
        
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
        $palabraSolicito = "solicitaron al Comité de Personal Docente de su facultad, el reconocimiento de productividad académica por obras artísticas, y a su vez, el CPD remitió";
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
        $palabraSolicito = "solicitó al Comité de Personal Docente de su facultad, el reconocimiento de productividad académica por obras artísticas, y a su vez, el CPD remitió";
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
    
    $correosTexto = empty($emails) ? "No registrado" : implode(", ", array_unique($emails));
    $textoFecha = "____"; $textoMes = "________"; $textoAno = "____";

    // --- IMPRESIÓN DEL DOCUMENTO ---
    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº ____ de {$textoAno}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$textoFecha} de {$textoMes})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("Por la cual se reconocen puntos a la Base –Salarial a {$textoUnProfesor} de la Universidad del Cauca, por concepto de productividad académica por obras artísticas.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);

    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(1);
    
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $section->addText("El Estatuto del Profesor Universitario – Acuerdo 024 de 1993, reglamenta las funciones del Comité Interno de Asignación y Reconocimiento de Puntaje –CIARP, conforme a las disposiciones del Decreto 1279 de 2002, cuya competencia para las decisiones de reconocimiento y asignación de puntaje fue delegada por el Rector de la Universidad del Cauca a la Vicerrectora Académica mediante Resolución 698 de 2022, modificada por la Resolución 0243 de 2023.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    
    $section->addText("El Decreto 1279 de 2002, establece en su artículo 10 el reconocimiento y puntajes por concepto de productividad académica, previendo en su literal i, numeral 1, los topes por obras artísticas.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $c1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $c1->addText("{$textoProfesores}{$textoNombresList} {$textoAdscrito} {$palabraDepto} {$textoDeptos} de la {$nomFacultad}, {$palabraSolicito} al CIARP mediante oficio N° {$textoOficios}.", 'StyleNormal');

    $section->addText("Para tal efecto, allegaron los documentos que fueron analizados por el CIARP, en sesión del {$textoFecha} de {$textoMes} de {$textoAno}, previo a la asignación del puntaje correspondiente y con fundamento en el concepto del Comité de Personal Docente de la facultad antes mencionada y la clasificación realizada por MINCIENCIAS, decidió adicionar los puntos conforme con lo establecido en el Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, literal i, numeral 1, que establece el reconocimiento de obras artísticas.", 'StyleNormal', ['alignment' => Jc::BOTH]);

    $section->addText("Decidiéndose por el citado Comité otorgar el puntaje que a continuación se enuncian:", 'StyleNormal');

    $section->addText("Puntaje por base salarial: Obras artísticas", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'], 'StyleBold');
    }
    $section->addTextBreak(1);

    // TABLAS PARA CREACIÓN ARTÍSTICA
    foreach ($obras_list as $obra) {
        $table = $section->addTable($styleTable);
        
        $table->addRow();
        $table->addCell(3000)->addText("CAMPO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText("DETALLE", 'FontTableBold', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NÚMERO DE OFICIO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($obra['numeroOficio'], 'FontTableNormal', 'ParaTable');

        // Formatear tipo de producto (Original, complementaria, etc)
        $tipoBruto = strtolower(trim($obra['tipo_producto']));
        $tipoFormateado = "OBRA DE CREACIÓN ARTÍSTICA";
        if (strpos($tipoBruto, 'original') !== false) {
            $tipoFormateado .= " ORIGINAL";
        } elseif (strpos($tipoBruto, 'complementaria') !== false) {
            $tipoFormateado .= " COMPLEMENTARIA O DE APOYO";
        } elseif (strpos($tipoBruto, 'interpretacion') !== false || strpos($tipoBruto, 'interpretación') !== false) {
            $tipoFormateado .= " DE INTERPRETACIÓN";
        }

        $table->addRow();
        $table->addCell(3000)->addText("TIPO DE PRODUCTO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($tipoFormateado, 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("IMPACTO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($obra['impacto'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("PRODUCTO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($obra['producto'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NOMBRE DEL EVENTO", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText(mb_strtoupper($obra['nombre_evento'], 'UTF-8'), 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("EVENTO", 'FontTableBold', 'ParaTable');
        $eventoLimpio = !empty($obra['evento']) ? mb_strtoupper($obra['evento'], 'UTF-8') : mb_strtoupper($obra['impacto'], 'UTF-8');
        $table->addCell(6000)->addText($eventoLimpio, 'FontTableNormal', 'ParaTable');

        // Combinar lugar y fechas
        $lugar = mb_strtoupper($obra['lugar_evento'], 'UTF-8');
        $fechaI = $obra['fecha_evento'];
        $fechaF = $obra['fecha_evento_f'];
        $textoLugarFecha = $lugar;
        if (!empty($fechaI) && !empty($fechaF) && $fechaI != $fechaF) {
            $textoLugarFecha .= " DEL $fechaI AL $fechaF";
        } elseif (!empty($fechaI)) {
            $textoLugarFecha .= " - $fechaI";
        }

        $table->addRow();
        $table->addCell(3000)->addText("LUGAR Y FECHA", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($textoLugarFecha, 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("NÚMERO DE AUTORES", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($obra['numero_autores'], 'FontTableNormal', 'ParaTable');

        // Evaluaciones (Cálculo promedio visual)
        $ev1 = floatval($obra['evaluacion1']);
        $ev2 = floatval($obra['evaluacion2']);
        $textoEvaluacion = "NO APLICA / DIRECTO";
        if ($ev1 > 0 || $ev2 > 0) {
            $suma = $ev1 + $ev2;
            $promedio = $suma / 2;
            $textoEvaluacion = "{$ev1} + {$ev2} = {$suma} / 2 = {$promedio}%";
        }

        $table->addRow();
        $table->addCell(3000)->addText("EVALUACIONES", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($textoEvaluacion, 'FontTableNormal', 'ParaTable');

        $table->addRow();
        $table->addCell(3000)->addText("RECONOCER", 'FontTableBold', 'ParaTable');
        $table->addCell(6000)->addText($obra['puntos'] . " PUNTOS", 'FontTableBold', 'ParaTable');

        $section->addTextBreak(1);
    }

    // --- PARTE RESOLUTIVA ---
    $section->addText("En consideración a lo expuesto,", 'StyleNormal');
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    $runR1 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR1->addText("ARTÍCULO PRIMERO. ", 'StyleBold');
    $runR1->addText("Reconocer puntos a la base salarial {$textoDelProfesor} a continuación, conforme a los productos mencionados en la parte considerativa de la presente resolución y a las disposiciones del Decreto 1279 de 2002, artículo 10, respecto de la productividad académica, literal i, numeral 1, que establece el reconocimiento de obras artísticas.", 'StyleNormal');

    $section->addText("Puntaje por base salarial: obras artísticas", 'StyleBold');
    $section->addText("FACULTAD DE " . mb_strtoupper($facultad, 'UTF-8'), 'StyleBold');
    $section->addText($lblDepto . mb_strtoupper($textoDeptos, 'UTF-8'), 'StyleBold');
    
    foreach ($docentes_list as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_tercero'] . " RECONOCER " . $puntajeTotal . " PUNTOS.", 'StyleBold');
    }
    $section->addTextBreak(1);

    $runR2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR2->addText("ARTÍCULO SEGUNDO. ", 'StyleBold');
    $runR2->addText("El puntaje asignado tendrá efectos salariales a partir de la fecha de expedición del presente acto administrativo de conformidad con lo previsto en el artículo 10, literales i, numeral 1, del Decreto 1279 de 2002.", 'StyleNormal');

    $runR3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $runR3->addText("ARTÍCULO TERCERO. ", 'StyleBold');
    $runR3->addText("Notificar el presente acto administrativo {$txtAlProfesor}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico conforme a {$auth} en el formato PM-FO-4-FOR-4, al correo {$correosTexto}, {$adv} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica (Comité CIARP) y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días siguientes a la fecha de la notificación.", 'StyleNormal');

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
$nombreFile = "Resolucion_CreacionArtistica_" . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>