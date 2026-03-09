<?php
// etl_llenar_matriz.php - VERSIÓN CORREGIDA (Nombres de columnas validados)
include_once('conn.php');

set_time_limit(600); 
ini_set('memory_limit', '512M'); 
error_reporting(E_ALL); 
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sincronizando S.A.V.I.A. Full</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a202c; color: #fff; padding: 40px; text-align: center; }
        .console { background: #2d3748; padding: 20px; border-radius: 10px; text-align: left; max-width: 800px; margin: 0 auto; font-family: monospace; border: 1px solid #4a5568; }
        .success { color: #48bb78; }
        .error { color: #f56565; }
        h1 { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>🚀 Sincronizando Matriz Integral (Doble Fecha)</h1>
    <div class="console">
<?php

// 1. LIMPIEZA
if($conn->query("TRUNCATE TABLE matriz_productividad")) {
    echo "<p>🧹 Matriz reiniciada correctamente.</p>";
} else {
    die("<p class='error'>❌ Error crítico al limpiar tabla: ".$conn->error."</p>");
}

// FUNCIÓN DE AYUDA
function migrar($conn, $titulo, $sql) {
    if (stripos($sql, 'INSERT IGNORE') === false) {
        $sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql);
    }
    if($conn->query($sql)) {
        echo "<p class='success'>✅ <b>$titulo:</b> " . $conn->affected_rows . " registros migrados.</p>";
    } else {
        echo "<p class='error'>❌ <b>Error en $titulo:</b> " . $conn->error . "</p>";
    }
    if (ob_get_level() > 0) { ob_flush(); }
    flush();
}

// =======================================================================
// GRUPO 1: PUNTOS SALARIALES
// =======================================================================
echo "<hr style='border-color:#4a5568'><h3>📈 Productos Salariales</h3>";

// 1. ARTÍCULOS
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT sp.fk_id_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'ARTICULO', s.tipo_articulo, s.titulo_articulo, s.nombre_revista, YEAR(s.fecha_solicitud), s.ano_publicacion, s.numero_autores, s.puntaje, 'solicitud', s.id_solicitud_articulo
FROM solicitud s JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
JOIN tercero t ON sp.fk_id_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Artículos", $sql);

// 2. LIBROS
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT lp.id_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'LIBRO', l.tipo_libro, l.producto, l.nombre_editorial, YEAR(l.fecha_solicitud),
    CASE WHEN l.mes_ano_edicion REGEXP '^[0-9]{4}' THEN LEFT(l.mes_ano_edicion, 4) ELSE YEAR(l.fecha_solicitud) END,
    l.autores, l.puntaje_final, 'libros', l.id_libro
FROM libros l JOIN libro_profesor lp ON l.id_libro = lp.id_libro
JOIN tercero t ON lp.id_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Libros", $sql);

// 3. PATENTES
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT pp.id_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'PATENTE', 'Propiedad Ind.', p.producto, '-', YEAR(p.fecha_solicitud), YEAR(p.fecha_solicitud), p.numero_profesores, p.puntaje, 'patentes', p.id_patente
FROM patentes p JOIN patente_profesor pp ON p.id_patente = pp.id_patente
JOIN tercero t ON pp.id_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Patentes", $sql);

// 4. INNOVACIÓN
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT ip.id_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'INNOVACION', 'Innovación', i.producto, '-', YEAR(i.fecha_solicitud), YEAR(i.fecha_solicitud), i.numero_profesores, i.puntaje, 'innovacion', i.id_innovacion
FROM innovacion i JOIN innovacion_profesor ip ON i.id_innovacion = ip.id_innovacion
JOIN tercero t ON ip.id_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Innovación", $sql);

// 5. PREMIOS
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT prp.id_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'PREMIO', pr.categoria_premio, pr.nombre_evento, pr.ambito, YEAR(pr.fecha_solicitud), YEAR(pr.fecha_solicitud), pr.numero_profesores, pr.puntos, 'premios', pr.id
FROM premios pr JOIN premios_profesor prp ON pr.id = prp.id_premio
JOIN tercero t ON prp.id_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Premios", $sql);

// 6. PRODUCCIÓN TÉCNICA
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT pp.id_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'PROD_TECNICA', 'Técnica', p.productop, '-', YEAR(p.fecha_solicitud), YEAR(p.fecha_solicitud), p.numero_profesores, p.puntaje, 'produccion_t_s', p.id_produccion
FROM produccion_t_s p JOIN produccionp_profesor pp ON p.id_produccion = pp.id_produccion
JOIN tercero t ON pp.id_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Prod. Técnica", $sql);

// 7. TRABAJOS CIENTÍFICOS
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT tp.profesor_id, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'TRABAJO_CIENT', tr.finalidad, tr.producto, tr.area, YEAR(tr.fecha_solicitud_tr), YEAR(tr.fecha_solicitud_tr), 1, tr.puntaje, 'trabajos_cientificos', tr.id
FROM trabajos_cientificos tr JOIN trabajo_profesor tp ON tr.id = tp.id_trabajo_cientifico
JOIN tercero t ON tp.profesor_id = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Trabajos Científicos", $sql);

// 10. DIRECCIÓN TESIS (CORREGIDO: No tiene fecha_solicitud, usamos terminacion para ambos)
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT dt.documento_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'DIRECCION_TESIS', dt.tipo, dt.titulo_obtenido, dt.nombre_estudiante, YEAR(dt.fecha_terminacion), YEAR(dt.fecha_terminacion), 1, dt.puntaje, 'direccion_tesis', dt.id
FROM direccion_tesis dt
JOIN tercero t ON dt.documento_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Dirección Tesis", $sql);

// 11. TÍTULOS (CORREGIDO: No tiene fecha_solicitud)
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT ti.documento_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'TITULO', ti.tipo, ti.titulo_obtenido, ti.institucion, YEAR(ti.fecha_terminacion), YEAR(ti.fecha_terminacion), 1, ti.puntaje, 'titulos', ti.id_titulo
FROM titulos ti
JOIN tercero t ON ti.documento_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Títulos", $sql);

// 12. POSDOCTORAL (CORREGIDO: No tiene fecha_solicitud)
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT pp.fk_tercero, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'PUNTOS_SALARIALES', 'POSDOCTORAL', 'Estancia', p.titulo_obtenido, p.institucion, YEAR(p.fecha_terminacion), YEAR(p.fecha_terminacion), 1, p.puntaje, 'posdoctoral', p.id
FROM posdoctoral p JOIN posdoctoral_profesor pp ON p.id = pp.id_titulo
JOIN tercero t ON pp.fk_tercero = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Posdoctorales", $sql);


// =======================================================================
// GRUPO 2: BONIFICACIONES
// =======================================================================
echo "<hr style='border-color:#4a5568'><h3>💰 Bonificaciones</h3>";

// B3. BONIF PUBLICACIÓN (Vigencia: Solicitud | Real: Publicación)
$sql = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id)
SELECT pp.documento_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'BONIFICACION', 'BONIF_PUBLICACION', p.tipo_producto, p.producto, p.nombre_revista, YEAR(p.fecha_solicitud), YEAR(p.fecha_publicacion), 1, p.puntaje_final, 'publicacion_bon', p.id
FROM publicacion_bon p JOIN publicacion_bon_profesor pp ON p.id = pp.id_publicacion_bon
JOIN tercero t ON pp.documento_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO";
migrar($conn, "Bonif. Publicación", $sql);

// B1, B2, B4, B5, B6 (Simplificados para brevedad, usando solicitud para ambos campos)
$sql_gen = "INSERT IGNORE INTO matriz_productividad (fk_profesor, nombre_profesor, departamento, clasificacion_pago, tipo_producto, subtipo, titulo_producto, detalle_extra, anio_vigencia, anio_produccion_real, numero_autores, puntaje_final, origen_tabla, origen_id) ";

migrar($conn, "Bonif. Creación", $sql_gen . "SELECT cp.documento_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'BONIFICACION', 'BONIF_CREACION', c.tipo_producto, c.producto, c.nombre_evento, YEAR(c.fecha_solicitud), YEAR(c.fecha_solicitud), c.autores, c.puntaje_final, 'creacion_bon', c.id FROM creacion_bon c JOIN creacion_bon_profesor cp ON c.id = cp.id_creacion_bon JOIN tercero t ON cp.documento_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO");

migrar($conn, "Bonif. Ponencias", $sql_gen . "SELECT pp.documento_profesor, t.nombre_completo, d.NOMBRE_DEPTO_CORT, 'BONIFICACION', 'BONIF_PONENCIA', p.difusion, p.producto, p.nombre_evento, YEAR(p.fecha_solicitud), YEAR(p.fecha_solicitud), p.autores, p.puntaje_final, 'ponencias_bon', p.id FROM ponencias_bon p JOIN ponencias_bon_profesor pp ON p.id = pp.id_ponencias_bon JOIN tercero t ON pp.documento_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO");

echo "</div>";
echo "<a href='dashboard_analitica_full.php' style='display:block; width:220px; margin:20px auto; padding:15px; background:#48bb78; color:white; text-decoration:none; border-radius:5px; font-weight:bold;'>Ir al Dashboard</a>";
?>
</body>
</html>