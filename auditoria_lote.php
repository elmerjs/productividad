<?php
include_once('conn.php');
$conn->set_charset("utf8mb4");

// 1. Recibir y sanitizar el lote a auditar
$lote = isset($_GET['lote']) ? $conn->real_escape_string($_GET['lote']) : '';

if (empty($lote)) {
    die("<div style='padding:20px; font-family:sans-serif;'><h3>Error</h3><p>Debe especificar un lote a auditar.</p><a href='index.php'>Volver</a></div>");
}

// 2. La Mega-Consulta de Auditoría (Cruza Solicitudes Recientes y el Histórico de Oracle)
$sql = "
-- PARTE 1: BÚSQUEDA EN SISTEMA RECIENTE
SELECT 
    sp1.fk_id_profesor AS CEDULA,
    t.nombre_completo AS DOCENTE,
    
    s1.identificador_solicitud AS LOTE_NUEVO,
    s1.titulo_articulo AS TITULO_NUEVO,
    s1.puntaje AS PUNTOS_NUEVOS,
    
    CONVERT(s2.identificador_solicitud USING utf8mb4) AS LOTE_HISTORICO,
    CONVERT('ARTICULO' USING utf8mb4) AS TIPO_HISTORICO,
    CONVERT(s2.titulo_articulo USING utf8mb4) AS TITULO_HISTORICO,
    s2.puntaje AS PUNTOS_YA_PAGADOS,
    
    CONVERT('SISTEMA RECIENTE' USING utf8mb4) AS FUENTE_HISTORICO

FROM solicitud s1
JOIN solicitud_profesor sp1 ON s1.id_solicitud_articulo = sp1.fk_id_solicitud
JOIN tercero t ON sp1.fk_id_profesor = t.documento_tercero
JOIN solicitud_profesor sp2 ON sp1.fk_id_profesor = sp2.fk_id_profesor
JOIN solicitud s2 ON sp2.fk_id_solicitud = s2.id_solicitud_articulo

WHERE s1.identificador_solicitud = '$lote'
  AND (s2.identificador_solicitud != '$lote' OR s1.id_solicitud_articulo > s2.id_solicitud_articulo)
  AND LENGTH(TRIM(s1.titulo_articulo)) > 15 
  AND LENGTH(TRIM(s2.titulo_articulo)) > 15
  AND (
      LOWER(TRIM(s1.titulo_articulo)) LIKE CONCAT('%', LOWER(TRIM(s2.titulo_articulo)), '%')
      OR LOWER(TRIM(s2.titulo_articulo)) LIKE CONCAT('%', LOWER(TRIM(s1.titulo_articulo)), '%')
      OR LOWER(TRIM(s1.titulo_articulo)) LIKE CONCAT('%', SUBSTRING(LOWER(TRIM(s2.titulo_articulo)), 6, 30), '%')
      OR LOWER(TRIM(s2.titulo_articulo)) LIKE CONCAT('%', SUBSTRING(LOWER(TRIM(s1.titulo_articulo)), 6, 30), '%')
  )

UNION ALL

-- PARTE 2: BÚSQUEDA EN ORACLE (CONDICIONADA)
SELECT 
    sp1.fk_id_profesor AS CEDULA,
    t.nombre_completo AS DOCENTE,
    
    s1.identificador_solicitud AS LOTE_NUEVO,
    s1.titulo_articulo AS TITULO_NUEVO,
    s1.puntaje AS PUNTOS_NUEVOS,
    
    CONVERT(CAST(hist.ANIO_VIGENCIA AS CHAR) USING utf8mb4) AS LOTE_HISTORICO,
    CONVERT(hist.TIPO_PRODUCTO USING utf8mb4) AS TIPO_HISTORICO,
    CONVERT(hist.TITULO_PRODUCTO USING utf8mb4) AS TITULO_HISTORICO,
    hist.PUNTAJE AS PUNTOS_YA_PAGADOS,
    
    CONVERT('SISTEMA ANTIGUO (Oracle)' USING utf8mb4) AS FUENTE_HISTORICO

FROM solicitud s1
JOIN solicitud_profesor sp1 ON s1.id_solicitud_articulo = sp1.fk_id_solicitud
JOIN tercero t ON sp1.fk_id_profesor = t.documento_tercero
JOIN soporte_productividad hist ON TRIM(sp1.fk_id_profesor) = TRIM(hist.CEDULA)

WHERE s1.identificador_solicitud = '$lote'
  AND LENGTH(TRIM(hist.TITULO_PRODUCTO)) > 15 
  AND LENGTH(TRIM(s1.titulo_articulo)) > 15
  AND (
      LOWER(TRIM(s1.titulo_articulo)) LIKE CONCAT('%', LOWER(TRIM(hist.TITULO_PRODUCTO)), '%')
      OR LOWER(TRIM(hist.TITULO_PRODUCTO)) LIKE CONCAT('%', LOWER(TRIM(s1.titulo_articulo)), '%')
      OR LOWER(TRIM(s1.titulo_articulo)) LIKE CONCAT('%', SUBSTRING(LOWER(TRIM(hist.TITULO_PRODUCTO)), 6, 30), '%')
      OR LOWER(TRIM(hist.TITULO_PRODUCTO)) LIKE CONCAT('%', SUBSTRING(LOWER(TRIM(s1.titulo_articulo)), 6, 30), '%')
  )
  
  -- LA MAGIA: OMITIR SI YA HUBO COINCIDENCIA EN EL SISTEMA RECIENTE
  AND NOT EXISTS (
      SELECT 1 
      FROM solicitud s_dup
      JOIN solicitud_profesor sp_dup ON s_dup.id_solicitud_articulo = sp_dup.fk_id_solicitud
      WHERE sp_dup.fk_id_profesor = sp1.fk_id_profesor
        AND (s_dup.identificador_solicitud != '$lote' OR s1.id_solicitud_articulo > s_dup.id_solicitud_articulo)
        AND LENGTH(TRIM(s_dup.titulo_articulo)) > 15
        AND (
            LOWER(TRIM(s1.titulo_articulo)) LIKE CONCAT('%', LOWER(TRIM(s_dup.titulo_articulo)), '%')
            OR LOWER(TRIM(s_dup.titulo_articulo)) LIKE CONCAT('%', LOWER(TRIM(s1.titulo_articulo)), '%')
            OR LOWER(TRIM(s1.titulo_articulo)) LIKE CONCAT('%', SUBSTRING(LOWER(TRIM(s_dup.titulo_articulo)), 6, 30), '%')
            OR LOWER(TRIM(s_dup.titulo_articulo)) LIKE CONCAT('%', SUBSTRING(LOWER(TRIM(s1.titulo_articulo)), 6, 30), '%')
        )
  )

ORDER BY DOCENTE, TITULO_NUEVO;
";

$result = $conn->query($sql);
$data = [];
$alertas_oracle = 0;
$alertas_recientes = 0;

if ($result) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
        if (strpos($row['FUENTE_HISTORICO'], 'Oracle') !== false) {
            $alertas_oracle++;
        } else {
            $alertas_recientes++;
        }
    }
}
$total_alertas = count($data);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría Forense - Lote <?php echo htmlspecialchars($lote); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            padding-bottom: 50px;
        }

        /* Cabecera Tipo Alerta */
        .audit-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 30px 40px;
            border-bottom: 4px solid #ef4444; /* Borde rojo indicando auditoría */
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .title-badge {
            background-color: #ef4444;
            color: white;
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            vertical-align: middle;
            margin-left: 15px;
        }

        /* Tarjetas de Resumen (KPIs) */
        .kpi-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #3b82f6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .kpi-card.kpi-danger { border-left-color: #ef4444; }
        .kpi-card.kpi-warning { border-left-color: #f59e0b; }
        
        .kpi-icon {
            font-size: 2rem;
            color: #94a3b8;
        }
        .kpi-card.kpi-danger .kpi-icon { color: #ef4444; }
        .kpi-card.kpi-warning .kpi-icon { color: #f59e0b; }
        
        .kpi-number {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
            color: #0f172a;
        }
        .kpi-label {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Contenedor de la Tabla */
        .table-wrapper {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        /* Estilos de Comparación (Cara a Cara) */
        .col-nueva {
            background-color: #f8fafc !important; /* Gris muy suave */
            border-right: 2px dashed #cbd5e1 !important;
        }
        .col-historica {
            background-color: #fff1f2 !important; /* Rojizo/Rosado muy suave de alerta */
        }
        
        table.dataTable thead th {
            font-size: 0.8rem;
            text-transform: uppercase;
            background-color: #f1f5f9;
            color: #475569;
            border-bottom: 2px solid #cbd5e1;
        }
        
        /* Badges de Fuentes */
        .badge-fuente-oracle {
            background-color: #fbbf24;
            color: #78350f;
            border: 1px solid #d97706;
        }
        .badge-fuente-reciente {
            background-color: #38bdf8;
            color: #0c4a6e;
            border: 1px solid #0284c7;
        }
        
        /* Botón de retroceso */
        .btn-back {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.2s;
        }
        .btn-back:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }
    </style>
</head>
<body>

    <div class="audit-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="m-0 fw-bold">
                        <i class="fas fa-shield-halved me-2"></i> Auditoría de Duplicidad
                        <span class="title-badge">LOTE: <?php echo htmlspecialchars($lote); ?></span>
                    </h1>
                    <p class="mt-2 mb-0 opacity-75">Detección de posibles fraccionamientos o dobles cobros cruzando bases de datos.</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-back fw-bold px-4 py-2 rounded-3">
                        <i class="fas fa-arrow-left me-2"></i> Volver a Solicitudes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        
        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="kpi-card kpi-danger">
                    <i class="fas fa-triangle-exclamation kpi-icon"></i>
                    <div>
                        <div class="kpi-number"><?php echo $total_alertas; ?></div>
                        <div class="kpi-label">Total Sospechosos Encontrados</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card kpi-warning">
                    <i class="fas fa-database kpi-icon"></i>
                    <div>
                        <div class="kpi-number"><?php echo $alertas_oracle; ?></div>
                        <div class="kpi-label">Coincidencias en Oracle Antiguo</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <i class="fas fa-file-contract kpi-icon"></i>
                    <div>
                        <div class="kpi-number"><?php echo $alertas_recientes; ?></div>
                        <div class="kpi-label">Coincidencias en Sist. Reciente</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <?php if ($total_alertas == 0): ?>
                <div class="alert alert-success text-center py-4 mb-0 border-0 fw-bold" style="background-color: #dcfce3; color: #166534;">
                    <i class="fas fa-check-circle fs-3 mb-2 d-block"></i>
                    ¡Excelente! El algoritmo no detectó posibles duplicados ni refritos para el lote <?php echo htmlspecialchars($lote); ?>.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="tablaAuditoria" class="table table-bordered align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 15%;">DOCENTE</th>
                                <th class="col-nueva" style="width: 35%;">
                                    <i class="fas fa-file-circle-plus text-primary me-1"></i> LO QUE SOLICITA AHORA
                                </th>
                                <th class="col-historica" style="width: 40%;">
                                    <i class="fas fa-clock-rotate-left text-danger me-1"></i> POSIBLE DUPLICADO HISTÓRICO
                                </th>
                                <th class="text-center" style="width: 10%;">ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): 
                                $isOracle = strpos($row['FUENTE_HISTORICO'], 'Oracle') !== false;
                                $badgeClass = $isOracle ? 'badge-fuente-oracle' : 'badge-fuente-reciente';
                                $iconFuente = $isOracle ? 'fa-server' : 'fa-list-check';
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['DOCENTE']); ?></div>
                                        <small class="text-muted font-monospace">CC: <?php echo htmlspecialchars($row['CEDULA']); ?></small>
                                    </td>
                                    
                                    <td class="col-nueva">
                                        <div class="fw-medium text-dark mb-1"><?php echo htmlspecialchars($row['TITULO_NUEVO']); ?></div>
                                        <span class="badge bg-light text-secondary border">Solicitud Actual</span>
                                        <span class="badge bg-primary text-white border border-primary">Pts Solicitados: <?php echo htmlspecialchars($row['PUNTOS_NUEVOS']); ?></span>
                                    </td>
                                    
                                    <td class="col-historica">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="badge <?php echo $badgeClass; ?>"><i class="fas <?php echo $iconFuente; ?> me-1"></i> <?php echo htmlspecialchars($row['FUENTE_HISTORICO']); ?></span>
                                            <small class="fw-bold text-danger">Ref/Año: <?php echo htmlspecialchars($row['LOTE_HISTORICO']); ?></small>
                                        </div>
                                        <div class="fw-medium text-danger mb-1" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($row['TITULO_HISTORICO']); ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            Tipo: <?php echo htmlspecialchars($row['TIPO_HISTORICO']); ?> | 
                                            <strong class="text-dark">Pts Pagados: <?php echo htmlspecialchars($row['PUNTOS_YA_PAGADOS']); ?></strong>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger w-100 mb-1 fw-bold" onclick="alert('Funcionalidad de rechazo por duplicidad en desarrollo.')">
                                            <i class="fas fa-ban"></i> Rechazar
                                        </button>
                                        <button class="btn btn-sm btn-light border w-100 text-muted" title="Marcar como falsa alarma">
                                            <i class="fas fa-check"></i> Omitir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            if ($('#tablaAuditoria').length) {
                $('#tablaAuditoria').DataTable({
                    "language": {
                        "url": "https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json"
                    },
                    "pageLength": 25,
                    "ordering": false // Deshabilitamos el ordenado para mantener a los profes agrupados tal como viene del SQL
                });
            }
        });
    </script>
</body>
</html>