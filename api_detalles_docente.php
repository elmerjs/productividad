<?php
// api_detalles_docente.php
include_once('conn.php');
header('Content-Type: application/json');

if ($conn->connect_error) { http_response_code(500); echo json_encode(["error" => "Error DB"]); exit; }
if (!isset($_GET['cedula']) || !isset($_GET['anio'])) { echo json_encode([]); exit; }

$cedula = $_GET['cedula'];
$anio = intval($_GET['anio']);

// 1. TRAER LOS ARTÍCULOS
$sql = "SELECT 
            s.titulo_articulo, s.tipo_productividad, s.tipo_articulo,
            s.nombre_revista, s.issn, s.tipo_publindex, s.puntaje,
            s.numero_autores, s.ano_publicacion as fecha_publicacion, s.estado_solicitud
        FROM solicitud s
        JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
        WHERE sp.fk_id_profesor = ? AND s.ano_publicacion = ?
        ORDER BY s.puntaje DESC, s.numero_autores ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $cedula, $anio);
$stmt->execute();
$result = $stmt->get_result();

$articulos = [];
$total_ipcp = 0;
$count_full = 0;
$count_short = 0;

// 2. PROCESAR Y CALCULAR SCORE IPCP (Misma lógica que el Dashboard)
while ($row = $result->fetch_assoc()) {
    // A. Peso por Tipo
    $peso_tipo = 0.3; // Default Short
    if (stripos($row['tipo_articulo'], 'Original') !== false || stripos($row['tipo_articulo'], 'Full') !== false) {
        $peso_tipo = 1.0;
        $count_full++;
    } else {
        $count_short++;
    }

    // B. Peso por Autores (Dilución de responsabilidad)
    $num_aut = intval($row['numero_autores']);
    $peso_rol = 0.2; // Default cola
    if ($num_aut <= 1) $peso_rol = 1.0;
    elseif ($num_aut <= 3) $peso_rol = 0.5;

    // Sumar al Score Global del docente
    $total_ipcp += ($peso_tipo * $peso_rol);
    
    $articulos[] = $row;
}

// 3. GENERAR EL DICTAMEN FORENSE (LA INTERPRETACIÓN)
$diagnostico = [
    "score" => round($total_ipcp, 2),
    "total_items" => count($articulos),
    "titulo" => "PRODUCCIÓN NORMAL",
    "clase_css" => "bg-green-900/20 border-green-500 text-green-400",
    "mensaje" => "El volumen y tipo de producción es consistente con los estándares bibliométricos de un investigador activo. La carga cognitiva estimada está dentro de los límites humanos."
];

// Lógica de Umbrales (Basada en la investigación)
if ($total_ipcp > 5.0) {
    // CASO ROJO: Imposibilidad Biológica
    $diagnostico["titulo"] = "ALERTA CRÍTICA: IMPOSIBILIDAD BIOLÓGICA";
    $diagnostico["clase_css"] = "bg-red-900/30 border-red-500 text-red-200";
    $diagnostico["mensaje"] = "El Índice IPCP ($total_ipcp) supera el límite teórico de 5.0. Se han detectado $count_full artículos tipo 'Full Paper' de alta carga cognitiva. Esto requeriría más de 900 horas semestrales de investigación exclusiva, lo cual es incompatible con la carga docente estatutaria. Se recomienda solicitar evidencia de trazabilidad (Git/Bitácoras) para descartar maquila académica.";
} 
elseif (count($articulos) > 5 && $total_ipcp <= 2.5) {
    // CASO AMARILLO: Salami Slicing
    $diagnostico["titulo"] = "ALERTA PREVENTIVA: POSIBLE FRACCIONAMIENTO";
    $diagnostico["clase_css"] = "bg-yellow-900/30 border-yellow-500 text-yellow-200";
    $diagnostico["mensaje"] = "Aunque el volumen es alto (" . count($articulos) . " productos), el Score IPCP es bajo ($total_ipcp). Se detecta una prevalencia de $count_short productos de menor extensión ('Short Papers' o notas). Esto sugiere un posible patrón de 'Salami Slicing' (fraccionamiento de resultados) para maximizar puntos sin aumentar la contribución científica sustancial.";
}

// 4. DEVOLVER TODO JUNTO
echo json_encode([
    "lista" => $articulos,
    "analisis" => $diagnostico
]);
?>