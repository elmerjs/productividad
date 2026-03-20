<?php
/**
 * Generador de Resoluciones CIARP - Dirección de Tesis (Bonificación)
 * Versión Final: Género refinado, desglose de puntos y corrección de símbolos.
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
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

$identificador = isset($_GET['cuadro_identificador_solicitud']) ? trim($_GET['cuadro_identificador_solicitud']) : '';
if (empty($identificador)) die("Identificador del paquete requerido.");

// 1. CAPTURA DE VARIABLES Y FOLIADO
$base_num = null;
$len_num = 3; 
if (isset($_GET['num_resolucion']) && is_numeric(trim($_GET['num_resolucion']))) {
    $str_num = trim($_GET['num_resolucion']);
    $base_num = intval($str_num);
    $len_num = strlen($str_num); 
    $stmt_clean = $conn->prepare("UPDATE direccion_tesis SET num_resolucion = NULL WHERE identificador = ?");
    $stmt_clean->bind_param("s", $identificador);
    $stmt_clean->execute();
}

$fecha_input = $_GET['fecha_resolucion'] ?? '';
$textoFecha = "____"; $textoMes = "________"; $textoAno = date('Y');
if (!empty($fecha_input)) {
    $timestamp = strtotime($fecha_input);
    $textoFecha = date('d', $timestamp);
    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $textoMes = $meses[date('n', $timestamp) - 1];
    $textoAno = date('Y', $timestamp);
}

$nombre_vicerrector = !empty($_GET['nombre_vicerrector']) ? trim($_GET['nombre_vicerrector']) : 'AIDA PATRICIA GONZÁLEZ NIEVA';
$genero_vicerrector = $_GET['genero_vicerrector'] ?? 'F';
$cargo_vicerrector = ($genero_vicerrector === 'M') ? "Vicerrector Académico" : "Vicerrectora Académica";
$cargo_presidente = ($genero_vicerrector === 'M') ? "Presidente CIARP" : "Presidenta CIARP";
$nombre_reviso = $_GET['nombre_reviso'] ?: 'Marjhory Castro';
$nombre_elaboro = $_GET['nombre_elaboro'] ?: 'Elizete Rivera';

// 2. CONSULTA Y AGRUPAMIENTO
$sql = "
    SELECT dt.*, t.nombre_completo AS profe_nombre, t.sexo, t.email, f.nombre_fac_min AS facultad, d.depto_nom_propio AS departamento
    FROM direccion_tesis dt
    JOIN direccion_t_profesor dtp ON dt.id = dtp.id_titulo
    JOIN tercero t ON dtp.fk_tercero = t.documento_tercero
    JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE dt.identificador = '" . $conn->real_escape_string($identificador) . "'
    AND (dt.estado IS NULL OR dt.estado = 'ac')
    ORDER BY dt.id, t.nombre_completo
";

$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) die("No hay registros activos.");

$productos = [];
$autores_por_tesis = [];
while ($row = $res->fetch_assoc()) {
    $id_t = $row['id'];
    if (!isset($productos[$id_t])) {
        $productos[$id_t] = $row;
        $autores_por_tesis[$id_t] = [];
    }
    $autores_por_tesis[$id_t][] = $row;
}

$resoluciones_finales = [];
foreach ($productos as $id_t => $info) {
    $autores = $autores_por_tesis[$id_t];
    $ids = array_column($autores, 'documento_profesor');
    sort($ids);
    $key_grupo = implode('_', $ids);
    if (!isset($resoluciones_finales[$key_grupo])) {
        $resoluciones_finales[$key_grupo] = ['docentes' => $autores, 'tesis_lista' => []];
    }
    $resoluciones_finales[$key_grupo]['tesis_lista'][] = $info;
}

// 3. RENDERIZADO
$phpWord = new PhpWord();
$phpWord->addFontStyle('StyleBold', ['bold' => true, 'name' => 'Arial', 'size' => 11]);
$phpWord->addFontStyle('StyleNormal', ['name' => 'Arial', 'size' => 11]);

$sql_update = "UPDATE direccion_tesis SET num_resolucion = ?, fecha_resolucion = ?, nombre_vicerrector = ?, genero_vicerrector = ?, nombre_reviso = ?, nombre_elaboro = ? WHERE id = ?";
$stmt_upd = $conn->prepare($sql_update);

foreach ($resoluciones_finales as $grupo) {
    $num_res = '____'; $p_num = null;
    if ($base_num !== null) {
        $num_res = str_pad($base_num, $len_num, "0", STR_PAD_LEFT);
        $p_num = $num_res; $base_num++;
    }
    foreach ($grupo['tesis_lista'] as $t) {
        $stmt_upd->bind_param("ssssssi", $p_num, $fecha_input, $nombre_vicerrector, $genero_vicerrector, $nombre_reviso, $nombre_elaboro, $t['id']);
        $stmt_upd->execute();
    }
    $vars = [
        'num_res' => $num_res, 'dia' => $textoFecha, 'mes' => $textoMes, 'ano' => $textoAno,
        'nom_vicerrector' => $nombre_vicerrector, 'car_vicerrector' => $cargo_vicerrector,
        'car_presidente' => $cargo_presidente, 'reviso' => $nombre_reviso, 'elaboro' => $nombre_elaboro
    ];
    renderizarResolucion($phpWord, $grupo['docentes'], $grupo['tesis_lista'], $vars);
}

function renderizarResolucion($phpWord, $docentes, $tesis_list, $vars) {
    $section = $phpWord->addSection(['paperSize' => 'Folio', 'marginTop' => 3000, 'marginLeft' => 1701, 'marginRight' => 1701, 'marginBottom' => 1417]);
    $header = $section->addHeader();
    $header->addTable()->addRow()->addCell(8000)->addImage('img/encabezadob.png', ['width' => 170]);
    $section->addFooter()->addTable()->addRow()->addCell(10000)->addImage('img/PIEb.png', ['width' => 430]);

    $section->addText("4-4.5", 'StyleNormal');
    $section->addText("RESOLUCIÓN CIARP Nº {$vars['num_res']} DE {$vars['ano']}", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText("({$vars['dia']} de {$vars['mes']})", 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);
    $section->addText("Por la cual se reconocen puntos por Bonificación a profesores de la Universidad del Cauca, por concepto de dirección de tesis.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(0);
    $section->addText("EL COMITÉ INTERNO DE ASIGNACIÓN Y RECONOCIMIENTO DE PUNTAJE DE LA UNIVERSIDAD DEL CAUCA en ejercicio de la competencia conferida por el artículo 25 del Decreto 1279 de 2002 y artículo 50 del Acuerdo Superior 024 de 1993 y,", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(0);
    $section->addText("C O N S I D E R A N D O QUE:", 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0);

    // Lógica Género y Plural
    $isMultDoc = count($docentes) > 1;
    $allF = true; $nombresTxt = []; $emails = [];
    foreach ($docentes as $d) {
        if (strtoupper($d['sexo'] ?? '') !== 'F') $allF = false;
        $txtIdent = (strtoupper($d['sexo'] ?? '') === 'F') ? "identificada" : "identificado";
        $nombresTxt[] = mb_strtoupper($d['profe_nombre'], 'UTF-8') . " {$txtIdent} con cédula de ciudadanía N° " . $d['documento_profesor'];
        if (!empty($d['email'])) $emails[] = $d['email'];
    }
    
    $prefix = $isMultDoc ? ($allF ? "Las profesoras " : "Los profesores ") : ($allF ? "La profesora " : "El profesor ");
    $adjAds = $isMultDoc ? ($allF ? "adscritas" : "adscritos") : ($allF ? "adscrita" : "adscrito");
    $quien = $isMultDoc ? "quienes presentaron" : "quien presentó";
    $laTesis = (count($tesis_list) > 1) ? "las siguientes direcciones de tesis:" : "la siguiente dirección de tesis:";
    $alProfe = $isMultDoc ? ($allF ? "a las profesoras" : "a los profesores") : ($allF ? "a la profesora" : "al profesor");
    $adv = $isMultDoc ? "advirtiéndoles" : "advirtiéndole";

    $section->addText("{$prefix}" . unirLista($nombresTxt) . " {$adjAds} al Departamento de " . $docentes[0]['departamento'] . " de la Facultad de " . $docentes[0]['facultad'] . ", {$quien} para reconocimiento de puntos por bonificación {$laTesis}", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addTextBreak(0);

    // Tablas sin cortes
    $pTotal = 0; $oficios = []; $desglosePuntos = [];
// 1. Antes del bucle, define un estilo de párrafo ultra-comprimido
$phpWord->addParagraphStyle('ParaTable', [
    'spaceBefore' => 0, 
    'spaceAfter' => 0, 
    'lineHeight' => 1.0,
    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
]);

// 2. Asegúrate de que el estilo de la tabla tenga márgenes mínimos
$tableStyle = [
    'borderSize' => 6, 
    'borderColor' => '000000', 
    'cellMarginTop' => 0,     // Margen superior eliminado
    'cellMarginBottom' => 0,  // Margen inferior eliminado
    'cellMarginLeft' => 80, 
    'cellMarginRight' => 80
];

foreach ($tesis_list as $tes) {
    $pTotal += floatval($tes['puntaje']);
    $desglosePuntos[] = $tes['puntaje'];
    $oficios[] = $tes['numero_oficio'];
    
    $table = $section->addTable($tableStyle);
    
    // 3. Aplicamos el estilo 'ParaTable' como tercer parámetro en addText
    $table->addRow(); 
    $table->addCell(3000)->addText("NÚMERO DE OFICIO", ['bold'=>true, 'size'=>9], 'ParaTable'); 
    $table->addCell(6000)->addText($tes['numero_oficio'], ['size'=>9], 'ParaTable');
    
    $table->addRow(); 
    $table->addCell(3000)->addText("TRABAJO", ['bold'=>true, 'size'=>9], 'ParaTable'); 
    $table->addCell(6000)->addText(mb_strtoupper($tes['titulo_obtenido'], 'UTF-8'), ['size'=>9], 'ParaTable');
    
    $table->addRow(); 
    $table->addCell(3000)->addText("TIPO", ['bold'=>true, 'size'=>9], 'ParaTable'); 
    $table->addCell(6000)->addText(mb_strtoupper($tes['tipo'], 'UTF-8'), ['size'=>9], 'ParaTable');
    
    $table->addRow(); 
    $table->addCell(3000)->addText("NOMBRE ESTUDIANTE", ['bold'=>true, 'size'=>9], 'ParaTable'); 
    $table->addCell(6000)->addText(mb_strtoupper($tes['nombre_estudiante'], 'UTF-8'), ['size'=>9], 'ParaTable');
    
    $table->addRow(); 
    $table->addCell(3000)->addText("FECHA SUSTENTACION", ['bold'=>true, 'size'=>9], 'ParaTable'); 
    $table->addCell(6000)->addText($tes['fecha_sustentacion'], ['size'=>9], 'ParaTable');
    
    $table->addRow(); 
    $table->addCell(3000)->addText("RESOLUCIÓN", ['bold'=>true, 'size'=>9], 'ParaTable'); 
    $table->addCell(6000)->addText($tes['resolucion'], ['size'=>9], 'ParaTable');
    
    $table->addRow(); 
    $table->addCell(3000)->addText("RECONOCER", ['bold'=>true, 'size'=>9], 'ParaTable'); 
    $table->addCell(6000)->addText($tes['puntaje'] . " PUNTOS", ['bold'=>true, 'size'=>9], 'ParaTable');

    // Reducimos el espacio entre tablas (antes era addTextBreak)
    $section->addText("", null, ['spaceBefore' => 0, 'spaceAfter' => 0]); 
}

    $section->addText("Los mencionados trabajos fueron aprobados por los expertos designados por el Comité de Personal Docente de la Facultad de Facultad de Ingeniería Electrónica y  Telecomunicaciones, quienes remitieron al CIARP mediante  Oficio(s) N° " . unirLista(array_unique($oficios)) . ", las solicitudes para lo concerniente al otorgamiento de puntos de conformidad con lo previsto en el Acuerdo 078 de 2002 artículo 4 literal h.", 'StyleNormal', ['alignment' => Jc::BOTH]);
     $section->addText("El Comité Interno de Asignación y Reconocimiento de Puntaje en sesión del 13 de enero de 2025 con fundamento en el concepto del Comité de Personal Docente de la Facultad en mención, decidió reconocer los puntos como lo dispone la norma.", 'StyleNormal', ['alignment' => Jc::BOTH]);
    $section->addText("En consideración a lo expuesto,", 'StyleNormal', ['alignment' => Jc::BOTH]);    
    
    
    
    
    $section->addText("RESUELVE:", 'StyleBold', ['alignment' => Jc::CENTER]);

    $run1 = $section->addTextRun(['alignment' => Jc::BOTH]);
$run1->addText("ARTÍCULO PRIMERO: ", 'StyleBold');
$run1->addText("Reconocer puntos por bonificación {$alProfe}, por concepto de dirección de tesis, de conformidad con lo previsto en el artículo 4 literal h del Acuerdo 078 de 2002 y en la parte considerativa de la presente resolución.", 'StyleNormal');
    
    $section->addText("FACULTAD DE " . mb_strtoupper($docentes[0]['facultad'], 'UTF-8'), 'StyleBold');
    $textoDesglose = (count($desglosePuntos) > 1) ? implode(" + ", $desglosePuntos) . " = " . $pTotal : $pTotal;
    foreach($docentes as $d) {
        $section->addText(mb_strtoupper($d['profe_nombre'], 'UTF-8') . " C.C " . $d['documento_profesor'] . " Reconocer " . $textoDesglose . " PUNTOS, por valor de " . htmlspecialchars('$') . "________", 'StyleNormal');
    }
    
    $section->addTextBreak(0);
   $run2 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $run2->addText("ARTÍCULO SEGUNDO: ", 'StyleBold');
    $run2->addText("Dicha bonificación se reconocerá al concluir la vigencia fiscal del año {$vars['ano']}.", 'StyleNormal');
    $run3 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $run3->addText("ARTÍCULO TERCERO: ", 'StyleBold');

    // Lógica de género para la notificación
    $notifAlProfe = $isMultDoc ? ($allF ? "a las profesoras" : "a los profesores") : ($allF ? "a la profesora" : "al profesor");
    $advierteLe = $isMultDoc ? "advirtiéndoles" : "advirtiéndole";

    $run3->addText("Notificar el presente acto administrativo {$notifAlProfe}, bajo los parámetros de la Ley 1437 de 2011, a través de medio electrónico, conforme a su autorización expresa en el formato PM-FO-4-FOR-3 al correo " . implode(", ", array_unique($emails)) . "; {$advierteLe} que contra ésta procede el Recurso de Reposición ante la Vicerrectoría Académica y en subsidio el de Apelación ante el Consejo Académico de la Universidad del Cauca dentro de los diez (10) días hábiles siguientes a la fecha de la presente notificación.", 'StyleNormal');
    
    $section->addTextBreak(0);
    $run4 = $section->addTextRun(['alignment' => Jc::BOTH]);
    $run4->addText("ARTÍCULO CUARTO: ", 'StyleBold');
    $run4->addText("Enviar copia del presente acto administrativo a la División de Gestión del Talento Humano para el trámite correspondiente acorde a su competencia.", 'StyleNormal');
     $section->addTextBreak(0);
    $section->addText("Se expide en Popayán, el {$vars['dia']} de {$vars['mes']} de {$vars['ano']}.", 'StyleNormal');
    $section->addText("COMUNÍQUESE, NOTIFÍQUESE Y CÚMPLASE", 'StyleBold', ['alignment' => Jc::CENTER]);
    
    $section->addTextBreak(2);
    $section->addText(mb_strtoupper($vars['nom_vicerrector'], 'UTF-8'), 'StyleBold', ['alignment' => Jc::CENTER]);
    $section->addText($vars['car_vicerrector'], 'StyleNormal', ['alignment' => Jc::CENTER]);
    $section->addText($vars['car_presidente'], 'StyleNormal', ['alignment' => Jc::CENTER]);
}

if (ob_get_contents()) ob_end_clean();
$nombreFile = "Resolucion_DirTesis_" . date('Ymd_His') . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$nombreFile.'"');
IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
exit;