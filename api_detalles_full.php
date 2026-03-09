<?php
// api_detalles_full.php
include_once('conn.php');
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

$cedula = $_GET['cedula'];
$anio = intval($_GET['anio']);

$sql = "SELECT * FROM matriz_productividad WHERE fk_profesor = ? AND anio_vigencia = ? ORDER BY clasificacion_pago DESC, puntaje_final DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $cedula, $anio);
$stmt->execute();
$res = $stmt->get_result();

$lista = [];
$stats = [
    'libros_total' => 0,
    'total_puntos' => 0,
    'total_items' => 0,
    'max_brecha' => 0,
    'tiene_rezago' => false
];

while($row = $res->fetch_assoc()) {
    // Calcular brecha (Año de solicitud - Año de producción)
    $brecha = intval($row['anio_vigencia']) - intval($row['anio_produccion_real']);
    $row['brecha'] = $brecha;
    
    if ($brecha > 0) {
        $stats['tiene_rezago'] = true;
        if ($brecha > $stats['max_brecha']) $stats['max_brecha'] = $brecha;
    }

    $lista[] = $row;
    
    if ($row['clasificacion_pago'] === 'PUNTOS_SALARIALES') {
        $stats['total_puntos'] += floatval($row['puntaje_final']);
        $stats['total_items']++;
        if ($row['tipo_producto'] == 'LIBRO') $stats['libros_total']++;
    }
}

// --- GENERADOR DE DICTAMEN TÉCNICO INFORMATIVO ---

// 1. Prioridad Máxima: Volumen de Puntos (Mantener alerta visual pero con texto técnico)
if ($stats['total_puntos'] > 50) {
    $analisis = [
        "titulo" => "📊 REVISIÓN DE BALANCE SALARIAL",
        "clase_css" => "border-l-4 border-red-600 bg-red-900/20 text-red-200",
        "mensaje" => "Se observa un acumulado de " . $stats['total_puntos'] . " puntos en la vigencia. Se recomienda la revisión rutinaria de folios para el cierre del acta correspondiente."
    ];
} 
// 2. Prioridad Media: Diferencia de fechas (Púrpura - Informativo)
elseif ($stats['tiene_rezago']) {
    $analisis = [
        "titulo" => "⏳ INFORMACIÓN: RADICACIÓN DE PRODUCCIÓN PREVIA",
        "clase_css" => "border-l-4 border-purple-500 bg-purple-900/20 text-purple-300",
        "mensaje" => "Se identifica un diferencial cronológico en el reporte. El docente presenta productos con una anterioridad de hasta " . $stats['max_brecha'] . " años respecto a la fecha de radicación actual. Esta información se registra para fines de trazabilidad histórica en el sistema."
    ];
}
// 3. Caso Normal (Verde)
else {
    $analisis = [
        "titulo" => "✅ VALIDACIÓN CRONOLÓGICA",
        "clase_css" => "border-l-4 border-green-500 bg-green-900/10 text-green-400",
        "mensaje" => "Los periodos de producción real y radicación administrativa coinciden dentro de la vigencia seleccionada."
    ];
}

echo json_encode(["lista" => $lista, "analisis" => $analisis]);
?>