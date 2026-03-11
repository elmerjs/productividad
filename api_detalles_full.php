<?php
// api_detalles_full.php
include_once('conn.php');
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

$cedula = isset($_GET['cedula']) ? $_GET['cedula'] : '';
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : 0;

// Súper Consulta: Busca el "estado_real" leyendo directamente de las 13 tablas de origen
$sql = "SELECT m.*,
    CASE 
        WHEN m.origen_tabla = 'solicitud' THEN (SELECT LOWER(TRIM(estado_solicitud)) FROM solicitud WHERE id_solicitud_articulo = m.origen_id)
        WHEN m.origen_tabla = 'libros' THEN (SELECT LOWER(TRIM(estado)) FROM libros WHERE id_libro = m.origen_id)
        WHEN m.origen_tabla = 'titulos' THEN (SELECT LOWER(TRIM(estado_titulo)) FROM titulos WHERE id_titulo = m.origen_id)
        WHEN m.origen_tabla = 'premios' THEN (SELECT LOWER(TRIM(estado)) FROM premios WHERE id = m.origen_id)
        WHEN m.origen_tabla = 'patentes' THEN (SELECT LOWER(TRIM(estado)) FROM patentes WHERE id_patente = m.origen_id)
        WHEN m.origen_tabla = 'innovacion' THEN (SELECT LOWER(TRIM(estado)) FROM innovacion WHERE id_innovacion = m.origen_id)
        WHEN m.origen_tabla = 'produccion_t_s' THEN (SELECT LOWER(TRIM(estado)) FROM produccion_t_s WHERE id_produccion = m.origen_id)
        WHEN m.origen_tabla = 'trabajos_cientificos' THEN (SELECT LOWER(TRIM(estado_cient)) FROM trabajos_cientificos WHERE id = m.origen_id)
        WHEN m.origen_tabla = 'trabajos_cientificos_bon' THEN (SELECT LOWER(TRIM(estado_tcb)) FROM trabajos_cientificos_bon WHERE id = m.origen_id)
        WHEN m.origen_tabla = 'creacion' THEN (SELECT LOWER(TRIM(estado_creacion)) FROM creacion WHERE id = m.origen_id)
        WHEN m.origen_tabla = 'creacion_bon' THEN (SELECT LOWER(TRIM(estado_cb)) FROM creacion_bon WHERE id = m.origen_id)
        WHEN m.origen_tabla = 'traduccion_libros' THEN (SELECT LOWER(TRIM(estado)) FROM traduccion_libros WHERE id_traduccion = m.origen_id)
        WHEN m.origen_tabla = 'traduccion_bon' THEN (SELECT LOWER(TRIM(estado)) FROM traduccion_bon WHERE id = m.origen_id)
        ELSE 'ac'
    END AS estado_real
    FROM matriz_productividad m 
    WHERE m.fk_profesor = ? AND m.anio_vigencia = ? 
    ORDER BY estado_real ASC, m.clasificacion_pago DESC, m.puntaje_final DESC";

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
    // 1. Detectar si el registro original fue anulado
    $es_anulado = ($row['estado_real'] === 'an');
    $row['es_anulado'] = $es_anulado;
    
    // 2. Calcular brecha (Año de solicitud - Año de producción)
    $brecha = intval($row['anio_vigencia']) - intval($row['anio_produccion_real']);
    $row['brecha'] = $brecha;
    
    // 3. SOLO sumamos a las estadísticas si NO está anulado
    if (!$es_anulado) {
        if ($brecha > 0) {
            $stats['tiene_rezago'] = true;
            if ($brecha > $stats['max_brecha']) $stats['max_brecha'] = $brecha;
        }
        
        $stats['total_items']++;
        $stats['total_puntos'] += floatval($row['puntaje_final']);
        
        if ($row['tipo_producto'] == 'LIBRO') {
            $stats['libros_total']++;
        }
    }
    
    $lista[] = $row;
}

$analisis = null;

if ($stats['total_puntos'] > 50) {
    $analisis = [
        "titulo" => "📊 REVISIÓN DE BALANCE SALARIAL",
        "clase_css" => "border-l-4 border-red-600 bg-red-900/20 text-red-200",
        "mensaje" => "Se observa un acumulado de " . $stats['total_puntos'] . " puntos ACTIVOS en la vigencia. Se recomienda la revisión rutinaria de folios para el cierre del acta correspondiente."
    ];
} elseif ($stats['tiene_rezago']) {
    $analisis = [
        "titulo" => "⏳ INFORMACIÓN: RADICACIÓN DE PRODUCCIÓN PREVIA",
        "clase_css" => "border-l-4 border-purple-500 bg-purple-900/20 text-purple-300",
        "mensaje" => "Se identifica un diferencial cronológico en el reporte. El docente presenta productos activos con una anterioridad de hasta " . $stats['max_brecha'] . " años respecto a la fecha de radicación actual."
    ];
} else {
    $analisis = [
        "titulo" => "✅ PARÁMETROS DENTRO DE LA NORMA",
        "clase_css" => "border-l-4 border-green-500 bg-green-900/20 text-green-300",
        "mensaje" => "El portafolio activo actual no presenta anomalías volumétricas ni cronológicas."
    ];
}

echo json_encode([
    "lista" => $lista,
    "stats" => $stats,
    "analisis" => $analisis
]);
?>