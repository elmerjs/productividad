<?php
// Requerir la conexión a la base de datos
include_once 'conn.php';

// Consulta SQL sin restricciones: Muestra TODO (Artículos, Libros, Títulos y PREMIOS)
// Consulta SQL: Muestra todo (incluso Borradores), PERO ignora los anulados ('an')
$sql = "
    SELECT 
        IF(s.num_resolucion IS NULL OR TRIM(s.num_resolucion) = '' OR s.num_resolucion = '____', 0, CAST(s.num_resolucion AS UNSIGNED)) as num_orden,
        IF(s.num_resolucion IS NULL OR TRIM(s.num_resolucion) = '' OR s.num_resolucion = '____', 'Borrador', s.num_resolucion) AS resolucion,
        s.identificador_solicitud AS identificador,
        'Artículos' AS modulo,
        UPPER(CONCAT_WS(' - ',
            s.tipo_articulo, 
            IFNULL(REPLACE(f.nombre_fac_min, 'Facultad de ', ''), 'SIN FACULTAD'), 
            IFNULL(GROUP_CONCAT(DISTINCT t.nombre_completo SEPARATOR ' y '), 'SIN AUTOR'), 
            IFNULL(DATE_FORMAT(s.fecha_resolucion, '%d-%m-%Y'), 'SIN FECHA')
        )) AS detalle
    FROM solicitud s
    LEFT JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
    LEFT JOIN tercero t ON sp.fk_id_profesor = t.documento_tercero
    LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    LEFT JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE s.estado_solicitud IS NULL OR LOWER(TRIM(s.estado_solicitud)) <> 'an'
    GROUP BY s.num_resolucion, s.identificador_solicitud, s.tipo_articulo, f.nombre_fac_min, s.fecha_resolucion

    UNION ALL

    SELECT 
        IF(l.num_resolucion IS NULL OR TRIM(l.num_resolucion) = '' OR l.num_resolucion = '____', 0, CAST(l.num_resolucion AS UNSIGNED)) as num_orden,
        IF(l.num_resolucion IS NULL OR TRIM(l.num_resolucion) = '' OR l.num_resolucion = '____', 'Borrador', l.num_resolucion) AS resolucion,
        l.identificador AS identificador,
        'Libros' AS modulo,
        UPPER(CONCAT_WS(' - ',
            CONCAT('LIBRO DE ', IFNULL(l.tipo_libro, 'TEXTO')), 
            IFNULL(REPLACE(f.nombre_fac_min, 'Facultad de ', ''), 'SIN FACULTAD'), 
            IFNULL(GROUP_CONCAT(DISTINCT t.nombre_completo SEPARATOR ' y '), 'SIN AUTOR'), 
            IFNULL(DATE_FORMAT(l.fecha_resolucion, '%d-%m-%Y'), 'SIN FECHA')
        )) AS detalle
    FROM libros l
    LEFT JOIN libro_profesor lp ON l.id_libro = lp.id_libro
    LEFT JOIN tercero t ON lp.id_profesor = t.documento_tercero
    LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    LEFT JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE l.estado IS NULL OR LOWER(TRIM(l.estado)) <> 'an'
    GROUP BY l.num_resolucion, l.identificador, l.tipo_libro, f.nombre_fac_min, l.fecha_resolucion

    UNION ALL

    SELECT 
        IF(ti.num_resolucion IS NULL OR TRIM(ti.num_resolucion) = '' OR ti.num_resolucion = '____', 0, CAST(ti.num_resolucion AS UNSIGNED)) as num_orden,
        IF(ti.num_resolucion IS NULL OR TRIM(ti.num_resolucion) = '' OR ti.num_resolucion = '____', 'Borrador', ti.num_resolucion) AS resolucion,
        ti.identificador AS identificador,
        'Títulos' AS modulo,
        UPPER(CONCAT_WS(' - ',
            CONCAT('TÍTULO ', IFNULL(ti.tipo_estudio, '')), 
            IFNULL(REPLACE(f.nombre_fac_min, 'Facultad de ', ''), 'SIN FACULTAD'), 
            IFNULL(GROUP_CONCAT(DISTINCT t.nombre_completo SEPARATOR ' y '), 'SIN AUTOR'), 
            IFNULL(DATE_FORMAT(ti.fecha_resolucion, '%d-%m-%Y'), 'SIN FECHA')
        )) AS detalle
    FROM titulos ti
    LEFT JOIN titulo_profesor tp ON ti.id_titulo = tp.id_titulo
    LEFT JOIN tercero t ON tp.fk_tercero = t.documento_tercero
    LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    LEFT JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE ti.estado_titulo IS NULL OR LOWER(TRIM(ti.estado_titulo)) <> 'an'
    GROUP BY ti.num_resolucion, ti.identificador, ti.tipo_estudio, f.nombre_fac_min, ti.fecha_resolucion

    UNION ALL

    SELECT 
        IF(pr.num_resolucion IS NULL OR TRIM(pr.num_resolucion) = '' OR pr.num_resolucion = '____', 0, CAST(pr.num_resolucion AS UNSIGNED)) as num_orden,
        IF(pr.num_resolucion IS NULL OR TRIM(pr.num_resolucion) = '' OR pr.num_resolucion = '____', 'Borrador', pr.num_resolucion) AS resolucion,
        pr.identificador AS identificador,
        'Premios' AS modulo,
        UPPER(CONCAT_WS(' - ',
            CONCAT('PREMIO ', IFNULL(pr.ambito, 'N/A')), 
            IFNULL(REPLACE(f.nombre_fac_min, 'Facultad de ', ''), 'SIN FACULTAD'), 
            IFNULL(GROUP_CONCAT(DISTINCT t.nombre_completo SEPARATOR ' y '), 'SIN AUTOR'), 
            IFNULL(DATE_FORMAT(pr.fecha_resolucion, '%d-%m-%Y'), 'SIN FECHA')
        )) AS detalle
    FROM premios pr
    LEFT JOIN premios_profesor pp ON pr.id = pp.id_premio
    LEFT JOIN tercero t ON pp.id_profesor = t.documento_tercero
    LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
    LEFT JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE pr.estado IS NULL OR LOWER(TRIM(pr.estado)) <> 'an'
    GROUP BY pr.num_resolucion, pr.identificador, pr.ambito, f.nombre_fac_min, pr.fecha_resolucion

    ORDER BY num_orden DESC, resolucion DESC
";
$result = $conn->query($sql);

if (!$result) {
    die("<div style='padding: 20px; background: #fee2e2; color: #991b1b; font-family: sans-serif; border: 1px solid #ef4444; border-radius: 8px;'>
            <strong>Error SQL Detectado:</strong><br><br> " . $conn->error . "
         </div>");
}

$all_records = [];
$grouped_data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $all_records[] = $row; 
        
        $ident = trim($row['identificador']);
        if (empty($ident)) $ident = "SIN_PAQUETE";
        
        $vigencia = substr($ident, 0, 4);
        if (!is_numeric($vigencia)) {
            $vigencia = "Otras";
        }

        $grouped_data[$vigencia][$ident][] = $row;
    }
}

krsort($grouped_data);
foreach ($grouped_data as $vig => &$paquetes) {
    krsort($paquetes);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado de Resoluciones</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #334155; padding-top: 30px; }
        .page-header { margin-bottom: 2rem; }
        .page-title { font-weight: 700; color: #0f172a; font-size: 1.75rem; letter-spacing: -0.5px; }
        
        .badge-modulo { font-size: 0.75rem; padding: 0.4em 0.8em; border-radius: 6px; }
        .mod-articulos { background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .mod-libros { background-color: #fcf1f0; color: #db2777; border: 1px solid #fbcfe8; }
        .mod-titulos { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .mod-premios { background-color: #fef9c3; color: #b45309; border: 1px solid #fde047; }
        
        .badge-borrador { background-color: #f1f5f9; color: #64748b; border: 1px dashed #cbd5e1; }

        /* Estilos Acordeón Principal (Vigencias) */
        .accordion-button:not(.collapsed) { background-color: #eff6ff; color: #1d4ed8; box-shadow: none; }
        .accordion-button:focus { border-color: transparent; box-shadow: none; }
        
        /* Estilos Acordeón Secundario (Paquetes) */
        .accordion-paquete .accordion-button { padding: 12px 20px; font-size: 0.95rem; }
        .accordion-paquete .accordion-button:not(.collapsed) { background-color: #f8fafc; color: #0f172a; }
        
        .nav-pills .nav-link { color: #64748b; font-weight: 500; border-radius: 8px; margin-right: 5px; }
        .nav-pills .nav-link.active { background-color: #0f172a; color: white; }

        .dt-buttons .btn-sm { font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 6px; }
    </style>
</head>
<body>

<div class="container-fluid px-4 mb-5">
    
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-layer-group text-primary me-2"></i>Auditoría de Resoluciones</h1>
            <p class="text-secondary mt-1">Gestión jerárquica por vigencias y paquetes generados</p>
        </div>
        
        <a href="MENU_INI.PHP" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Volver al Menú
        </a>
    </div>

    <ul class="nav nav-pills mb-4 shadow-sm bg-white p-2 rounded-3 d-inline-flex" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="grouped-tab" data-bs-toggle="tab" data-bs-target="#grouped-view" type="button" role="tab">
                <i class="fas fa-folder-tree me-1"></i> Vista por Paquetes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="flat-tab" data-bs-toggle="tab" data-bs-target="#flat-view" type="button" role="tab">
                <i class="fas fa-list me-1"></i> Lista General (Exportable)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="reportTabsContent">
        
        <div class="tab-pane fade show active" id="grouped-view" role="tabpanel">
            
            <?php if(empty($grouped_data)): ?>
                <div class="alert alert-info text-center py-5 rounded-4 shadow-sm border-0 bg-white">
                    <i class="fas fa-info-circle fs-1 text-info mb-3"></i>
                    <h4>Aún no hay resoluciones registradas</h4>
                    <p class="text-secondary">Asegúrate de haber generado las resoluciones desde los módulos e ingresado un número en el modal.</p>
                </div>
            <?php else: ?>
                
                <div class="accordion" id="accordionVigencias">
                    <?php 
                    $firstVig = true;
                    foreach ($grouped_data as $vigencia => $paquetes): 
                        $collapseVigId = "collapseVig_" . preg_replace('/[^a-zA-Z0-9]/', '', $vigencia);
                    ?>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm rounded-3 bg-white">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold fs-5 rounded-3 <?= $firstVig ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseVigId ?>">
                                <i class="fas fa-calendar-check text-primary me-2"></i> Vigencia <?= htmlspecialchars($vigencia) ?>
                                <span class="badge bg-secondary ms-3 rounded-pill"><?= count($paquetes) ?> Paquete(s)</span>
                            </button>
                        </h2>
                        <div id="<?= $collapseVigId ?>" class="accordion-collapse collapse <?= $firstVig ? 'show' : '' ?>" data-bs-parent="#accordionVigencias">
                            <div class="accordion-body bg-light p-4">
                                
                                <div class="accordion accordion-paquete" id="accordionPaq_<?= $collapseVigId ?>">
                                    <?php 
                                    foreach ($paquetes as $identificador => $registros): 
                                        // ID único para cada paquete dentro de la vigencia
                                        $paqId = "collapsePaq_" . preg_replace('/[^a-zA-Z0-9]/', '', $vigencia) . "_" . preg_replace('/[^a-zA-Z0-9]/', '', $identificador);
                                    ?>
                                    
                                    <div class="accordion-item shadow-sm bg-white mb-3 border rounded-3 overflow-hidden">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed bg-white border-bottom" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $paqId ?>">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                    <span class="fw-bold text-dark"><i class="fas fa-box text-warning me-2"></i> Identificador (Lote): <?= htmlspecialchars($identificador) ?></span>
                                                    <span class="badge bg-primary rounded-pill"><?= count($registros) ?> resoluciones</span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="<?= $paqId ?>" class="accordion-collapse collapse" data-bs-parent="#accordionPaq_<?= $collapseVigId ?>">
                                            <div class="accordion-body p-0">
                                                
                                                <div class="table-responsive p-0">
                                                    <table class="table table-hover align-middle mb-0 m-0 border-0 tabla-paquete" data-paquete="<?= htmlspecialchars($identificador) ?>" style="width: 100%;">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="text-center border-0 text-secondary" style="width: 15%">RES. N°</th>
                                                                <th class="text-center border-0 text-secondary" style="width: 15%">ORIGEN</th>
                                                                <th class="border-0 text-secondary">DETALLE DE LA RESOLUCIÓN</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($registros as $reg): 
                                                                $modulo = $reg['modulo'];
                                                                $badgeClass = '';
                                                                if ($modulo == 'Artículos') $badgeClass = 'mod-articulos';
                                                                if ($modulo == 'Libros') $badgeClass = 'mod-libros';
                                                                if ($modulo == 'Títulos') $badgeClass = 'mod-titulos';
                                                                if ($modulo == 'Premios') $badgeClass = 'mod-premios';
                                                                
                                                                $resText = htmlspecialchars($reg['resolucion']);
                                                                $resStyle = ($resText == 'Borrador') ? "<span class='badge badge-borrador'>Borrador (Sin Num)</span>" : "<span class='fw-bold fs-6 text-dark'>{$resText}</span>";
                                                            ?>
                                                            <tr>
                                                                <td class="text-center border-bottom-0 pb-3 pt-3"><?= $resStyle ?></td>
                                                                <td class="text-center border-bottom-0 pb-3 pt-3"><span class="badge-modulo <?= $badgeClass ?>"><?= mb_strtoupper($modulo, 'UTF-8') ?></span></td>
                                                                <td class="fw-medium text-secondary border-bottom-0 pb-3 pt-3" style="font-size: 0.9rem;"><?= htmlspecialchars($reg['detalle']) ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php endforeach; ?>
                                </div>
                                </div>
                        </div>
                    </div>

                    <?php 
                    $firstVig = false;
                    endforeach; 
                    ?>
                </div>

            <?php endif; ?>

        </div>

        <div class="tab-pane fade" id="flat-view" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                <div class="table-responsive">
                    <table id="tablaGeneral" class="table table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">N° RESOLUCIÓN</th>
                                <th class="text-center">LOTE/PAQUETE</th>
                                <th class="text-center">ORIGEN</th>
                                <th>DETALLE (TIPO - FACULTAD - PROFESOR - FECHA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_records as $row): 
                                $modulo = $row['modulo'];
                                $badgeClass = '';
                                if ($modulo == 'Artículos') $badgeClass = 'mod-articulos';
                                if ($modulo == 'Libros') $badgeClass = 'mod-libros';
                                if ($modulo == 'Títulos') $badgeClass = 'mod-titulos';
                                if ($modulo == 'Premios') $badgeClass = 'mod-premios';
                                
                                $resText = htmlspecialchars($row['resolucion']);
                                $resStyle = ($resText == 'Borrador') ? "<span class='badge badge-borrador'>Borrador</span>" : "<span class='fw-bold fs-5 text-dark'>{$resText}</span>";
                            ?>
                            <tr>
                                <td class="text-center"><?= $resStyle ?></td>
                                <td class="text-center text-muted fw-semibold"><?= htmlspecialchars($row['identificador']) ?></td>
                                <td class="text-center"><span class="badge-modulo <?= $badgeClass ?>"><?= mb_strtoupper($modulo, 'UTF-8') ?></span></td>
                                <td class="fw-medium text-secondary"><?= htmlspecialchars($row['detalle']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        
        // 1. INICIALIZAR TABLAS INDIVIDUALES DE CADA PAQUETE (CON BOTÓN DE EXCEL PROPIO)
        $('.tabla-paquete').each(function() {
            var nombrePaquete = $(this).data('paquete');

            $(this).DataTable({
                responsive: true,
                paging: false,       
                searching: false,    
                info: false,         
                ordering: true,
                dom: '<"d-flex justify-content-end p-2 bg-light border-bottom"B>rt',
                buttons: [
                    { 
                        extend: 'excelHtml5', 
                        className: 'btn btn-success btn-sm shadow-sm', 
                        text: '<i class="fas fa-file-excel me-1"></i> Excel de este Lote',
                        title: 'Resoluciones_Lote_' + nombrePaquete 
                    }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
                }
            });
        });

        // RE-CALCULAR ANCHO DE LA TABLA CUANDO SE ABRE EL ACORDEÓN DEL PAQUETE
        // Evita que DataTables se vea "aplastado" si se inicializa oculto
        $('.accordion-paquete .accordion-collapse').on('shown.bs.collapse', function () {
            $(this).find('.tabla-paquete').DataTable().columns.adjust().responsive.recalc();
        });

        // 2. INICIALIZAR TABLA GENERAL COMPLETA (CON BUSCADOR, PAGINACIÓN Y EXCEL)
        $('#tablaGeneral').DataTable({
            responsive: true,
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between mt-3"ip>',
            "order": [[ 0, "desc" ]], 
            buttons: [
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm rounded-3 shadow-sm', 
                    text: '<i class="fas fa-file-excel me-1"></i> Descargar Consolidado Total',
                    title: 'Reporte_Global_Resoluciones_CIARP'
                }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });
    });
</script>

</body>
</html>