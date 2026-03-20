<?php
// Incluir el archivo de conexión
include_once ('conn.php'); 

// Obtener los filtros desde el formulario
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Consulta SQL con LEFT JOIN y columnas de resolución incluidas
$sql = "
    SELECT 
        dt.id AS id,
        dt.identificador AS identificador,
        dt.numero_oficio AS numero_oficio,
        dt.documento_profesor AS documento_profesor,
        dt.titulo_obtenido AS titulo_obtenido,
        dt.tipo AS tipo,
        dt.nombre_estudiante AS nombre_estudiante,
        dt.fecha_sustentacion AS fecha_sustentacion,
        dt.fecha_terminacion AS fecha_terminacion,
        dt.resolucion AS resolucion_original,
        dt.puntaje AS puntaje,
        dt.tipo_productividad AS tipo_productividad,
        dt.estado,
        dt.num_resolucion,
        dt.fecha_resolucion,
        dt.nombre_vicerrector,
        dt.genero_vicerrector,
        dt.nombre_reviso,
        dt.nombre_elaboro,
        
        -- Facultad y Departamento
        MAX(f.nombre_fac_min) AS facultad,
        MAX(d.depto_nom_propio) AS departamento,
        
        -- Detalles de los profesores
        GROUP_CONCAT(
            DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
            ORDER BY ter.documento_tercero
            SEPARATOR '\n'
        ) AS detalles_profesores
    FROM 
        direccion_tesis dt
    LEFT JOIN 
        direccion_t_profesor dtp ON dtp.id_titulo = dt.id
    LEFT JOIN 
        tercero ter ON dtp.fk_tercero = ter.documento_tercero
    LEFT JOIN 
        deparmanentos d ON ter.fk_depto = d.PK_DEPTO
    LEFT JOIN 
        facultad f ON d.FK_FAC = f.PK_FAC
    WHERE 1 = 1";

if (!empty($identificador)) {
    $sql .= " AND dt.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND dt.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

$sql .= " 
    GROUP BY 
        dt.id, dt.identificador, dt.numero_oficio, dt.documento_profesor, 
        dt.titulo_obtenido, dt.tipo, dt.nombre_estudiante, dt.fecha_sustentacion, 
        dt.fecha_terminacion, dt.resolucion, dt.puntaje, dt.tipo_productividad,
        dt.estado, dt.num_resolucion, dt.fecha_resolucion, dt.nombre_vicerrector, 
        dt.genero_vicerrector, dt.nombre_reviso, dt.nombre_elaboro
    ORDER BY 
        dt.id DESC;
";

$result = $conn->query($sql);

if (!$result) {
    echo "<div class='alert alert-danger m-3'>Error SQL en Dirección Tesis: " . $conn->error . "</div>";
}

// Identificadores y años para modales
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM direccion_tesis WHERE identificador IS NOT NULL ORDER BY identificador DESC");
$identificadores = [];
$unique_years = [];

if ($identificadores_result) {
    while ($row = $identificadores_result->fetch_assoc()) {
        $id_str = $row['identificador'];
        $identificadores[] = $id_str;
        $year = substr($id_str, 0, 4);
        if (!empty($year) && is_numeric($year) && !in_array($year, $unique_years)) {
            $unique_years[] = $year;
        }
    }
    rsort($unique_years);
}

// Últimos lotes para el carrusel
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador FROM direccion_tesis WHERE identificador IS NOT NULL AND identificador != '' ORDER BY identificador DESC LIMIT 6");
$ultimos_lotes = [];
if ($ultimos_lotes_result) {
    while ($row = $ultimos_lotes_result->fetch_assoc()) {
        $ultimos_lotes[] = $row['identificador'];
    }
}
?>

<style>
    .page-header-inner { margin-bottom: 1.2rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.8rem; }
    .page-title-inner { font-weight: 700; color: #0f172a; font-size: 1.4rem; letter-spacing: -0.5px; margin: 0; }
    .page-subtitle-inner { color: #10b981; font-size: 0.85rem; margin-top: 2px; font-weight: 600; }

    .btn-modern {
        font-weight: 600; border-radius: 8px; padding: 0.4rem 0.9rem; font-size: 0.8rem;
        transition: all 0.2s ease; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
    }
    .btn-m-xls { background: #f0fdf4; color: #059669; border-color: #bbf7d0; }
    .btn-m-xls:hover { background: #059669; color: white; }
    .btn-m-cuadros { background: #eef2ff; color: #4f46e5; border-color: #c7d2fe; }
    .btn-m-cuadros:hover { background: #4f46e5; color: white; }
    .btn-m-res { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .btn-m-res:hover { background: #2563eb; color: white; }

    .status-pill {
        display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: 4px;
        font-size: 0.65rem; font-weight: 600; background-color: #f8fafc; border: 1px solid #e2e8f0;
        color: #475569; white-space: nowrap; letter-spacing: 0.2px;
    }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; }
    .status-anulado { background-color: transparent; border-color: transparent; color: #94a3b8; text-decoration: line-through; padding-left: 0; }

    #direccionTesisTable { margin-bottom: 0 !important; width: 100% !important; }
    #direccionTesisTable thead th { background-color: #f8fafc; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; padding: 6px 8px; text-transform: uppercase; }
    #direccionTesisTable tbody td { vertical-align: middle; font-size: 0.78rem; color: #334155; border-bottom: 1px solid #f1f5f9; padding: 3px 8px !important; line-height: 1.15; }
    
    .text-truncate-custom { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: middle; }
    .btn-action { border-radius: 4px; padding: 2px 6px !important; font-size: 0.75rem; margin: 0 1px; }
    
    .quick-audit-section { background: #f8fafc; border-radius: 12px; padding: 1rem 1.5rem; margin-top: 2rem; border: 1px dashed #cbd5e1; }
    .quick-audit-title { font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
    .lotes-carousel { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 6px; scrollbar-width: thin; }
    .lote-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px; font-size: 0.8rem; font-weight: 600; color: #334155; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: all 0.2s ease; }
    .lote-card:hover { border-color: #10b981; background-color: #ecfdf5; color: #047857; }

    .modal-dt-bon { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(6px); padding-top: 6vh; }
    .modal-content-dt-bon { background-color: #ffffff; margin: auto; padding: 2rem; border: 1px solid rgba(255,255,255,0.2); width: 90%; max-width: 600px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .close-modal { color: #94a3b8; float: right; font-size: 24px; font-weight: bold; cursor: pointer; background: #f1f5f9; width: 32px; height: 32px; display: flex; justify-content: center; align-items: center; border-radius: 50%; }
    .modal-content-dt-bon .form-control, .modal-content-dt-bon .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; font-size: 0.9rem; }
</style>

<div class="module-wrapper">
    <div class="page-header-inner d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title-inner">Dirección de Tesis</h1>
            <p class="page-subtitle-inner"><i class="fas fa-gift me-1"></i> Módulo de Bonificación Académica</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button id="openModaldt" class="btn-modern btn-m-xls"><i class="fas fa-file-excel"></i> XLS</button>
            <button id="openModalCuadrosdt" class="btn-modern btn-m-cuadros"><i class="fas fa-table"></i> Cuadros</button>
            <button id="openModalResolucionesdt" class="btn-modern btn-m-res"><i class="fas fa-file-signature"></i> Resoluciones</button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="direccionTesisTable" class="table table-hover align-middle">
            <thead>
                <tr> 
                    <th style="width: 4%">ID</th>
                    <th style="width: 8%">IDENTIF.</th>
                    <th style="width: 12%">DEPTO.</th>
                    <th style="width: 12%">PROFESORES</th>
                    <th style="width: 14%">TÍTULO</th>
                    <th style="width: 14%">ESTUDIANTE</th>
                    <th style="width: 8%">SUSTENT.</th>
                    <th style="width: 8%">TIPO</th>
                    <th style="width: 5%">PTS</th>
                    <th style="width: 7%">ESTADO</th>
                    <th style="width: 6%">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $facultad = str_replace("Facultad de ", "", $row['facultad'] ?? '');
                        $departamento = $row['departamento'] ?? 'SIN ASIGNAR';
                        
                        $estadoOriginal = strtolower(trim($row['estado'] ?? ''));
                        if ($estadoOriginal === '' || strpos($estadoOriginal, 'ac') !== false) {
                            $htmlEstado = '<span class="status-pill"><span class="status-dot bg-success"></span>ACTIVO</span>';
                        } elseif (strpos($estadoOriginal, 'an') !== false) {
                            $htmlEstado = '<span class="status-pill status-anulado"><span class="status-dot bg-secondary"></span>ANULADO</span>';
                        } else {
                            $htmlEstado = '<span class="status-pill"><span class="status-dot bg-warning"></span>' . strtoupper($estadoOriginal) . '</span>';
                        }

                        echo '<tr>';
                        echo '<td>' . $row['id'] . '</td>';
                        echo '<td><span class="badge bg-light text-secondary border px-1">' . $row['identificador'] . '</span></td>';
                        echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 120px;" title="Facultad: ' . $facultad . '">' . $departamento . '</div></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 130px;" title="' . htmlspecialchars($row['detalles_profesores']) . '">' . substr($row['detalles_profesores'], 0, 25) . '...</div></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 140px;" title="' . htmlspecialchars($row['titulo_obtenido']) . '">' . $row['titulo_obtenido'] . '</div></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 140px;">' . $row['nombre_estudiante'] . '</div></td>';
                        echo '<td><small>' . $row['fecha_sustentacion'] . '</small></td>';
                        echo '<td><small>' . $row['tipo'] . '</small></td>';
                        echo '<td class="fw-bold text-success">' . $row['puntaje'] . '</td>';
                        echo '<td>' . $htmlEstado . '</td>';
                        echo '<td class="text-nowrap">';
                        echo '<a href="editar_dir_tesis.php?id=' . $row['id'] . '" class="btn btn-light border btn-action text-primary shadow-sm"><i class="fas fa-pen"></i></a> ';
                        echo '<button class="btn btn-light border btn-action text-danger shadow-sm" onclick="confirmDeleteDT(' . $row['id'] . ')"><i class="fas fa-trash-alt"></i></button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="quick-audit-section">
        <div class="quick-audit-title"><i class="fas fa-folder-tree text-secondary me-1"></i> Lotes Recientes - Dirección de Tesis</div>
        <div class="lotes-carousel">
            <?php foreach($ultimos_lotes as $lote): ?>
                <a href="auditoria_lote_bon.php?lote=<?php echo urlencode($lote); ?>" class="lote-card"><i class="fas fa-scroll text-success"></i> <?php echo $lote; ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="modaldt" class="modal-dt-bon">
    <div class="modal-content-dt-bon">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS</h4>
             <span class="close-modal" onclick="$(this).closest('.modal-dt-bon').fadeOut(200);">&times;</span>
        </div>
        <form action="report_dir_tesis.php" method="GET">
            <div class="mb-3">
                <label class="form-label">Año:</label>
                <select name="ano" id="ano_xls_dt" class="form-select">
                    <option value="">Todos...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Identificador:</label>
                <select name="identificador_solicitud" id="identificador_solicitud_dt" class="form-select">
                    <option value="">Seleccione...</option>
                    <?php foreach ($identificadores as $id) echo '<option value="' . $id . '" data-ano="' . substr($id, 0, 4) . '">' . $id . '</option>'; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100 btn-lg rounded-3">Generar Reporte</button>
        </form>
    </div>
</div>

<div id="modalCuadrosdt" class="modal-dt-bon">
    <div class="modal-content-dt-bon">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros</h4>
             <span class="close-modal" onclick="$(this).closest('.modal-dt-bon').fadeOut(200);">&times;</span>
        </div>
        <form action="cuadros_direccion_t.php" method="GET">
            <div class="mb-3">
                <label class="form-label">Año:</label>
                <select name="cuadro_ano" id="ano_cuadros_dt" class="form-select">
                    <option value="">Todos...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Identificador:</label>
                <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud_dt" class="form-select">
                    <option value="">Seleccione...</option>
                    <?php foreach ($identificadores as $id) echo '<option value="' . $id . '" data-ano="' . substr($id, 0, 4) . '">' . $id . '</option>'; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg rounded-3">Generar Cuadro</button>
        </form>
    </div>
</div>

<div id="modalResolucionesdt" class="modal-dt-bon">
    <div class="modal-content-dt-bon" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-info m-0" style="color: #0284c7 !important;"><i class="fas fa-file-word me-2"></i>Resoluciones Word</h4>
             <span class="close-modal" onclick="$(this).closest('.modal-dt-bon').fadeOut(200);">&times;</span>
        </div>
        <form action="resoluciones_dir_tesis.php" method="GET">
            <div class="row bg-light p-3 mb-4 border rounded-3 mx-0">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Año:</label>
                    <select id="ano_res_dt" class="form-select">
                        <option value="">Seleccione...</option>
                        <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Paquete:</label>
                    <select name="cuadro_identificador_solicitud" id="cuadro_identificador_dt" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($identificadores as $id) echo '<option value="' . $id . '" data-ano="' . substr($id, 0, 4) . '">' . $id . '</option>'; ?>
                    </select>
                </div>
            </div>

            <div class="row px-2">
                <div class="col-md-6 mb-3">
                    <label class="form-label">N° Resolución:</label>
                    <input type="text" name="num_resolucion" class="form-control" placeholder="Ej: 045">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha:</label>
                    <input type="date" name="fecha_resolucion" class="form-control">
                </div>
            </div>
            
            <div class="row px-2">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Vicerrector/a:</label>
                    <input type="text" name="nombre_vicerrector" class="form-control" value="AIDA PATRICIA GONZÁLEZ NIEVA" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Género:</label>
                    <select name="genero_vicerrector" class="form-select" required>
                        <option value="F">Femenino</option>
                        <option value="M">Masculino</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-info text-white w-100 btn-lg rounded-3" style="background-color: #0ea5e9; border-color: #0ea5e9;">Generar Word</button>
        </form>
    </div>
</div>

<script>
    (function() {
        function initDTBonModule() {
            window.applyYearFilterDT = function(yearSelectId, idSelectId) {
                var year = $('#' + yearSelectId).val();
                $('#' + idSelectId + ' option').each(function() {
                    if ($(this).val() === "") return; 
                    if (year === "" || $(this).data('ano').toString() === year) {
                        $(this).show().prop('disabled', false);
                    } else {
                        $(this).hide().prop('disabled', true);
                    }
                });
                $('#' + idSelectId).val(''); 
            };

            window.confirmDeleteDT = function(id) {
                if (confirm("¿Eliminar esta dirección de tesis?")) {
                    const motivo = prompt("Motivo de anulación:");
                    if (motivo && motivo.trim() !== "") {
                        window.location.href = 'eliminar_dir_tesis.php?id=' + id + '&motivo=' + encodeURIComponent(motivo);
                    } else {
                        alert("El motivo es obligatorio.");
                    }
                }
            };

            $(document).ready(function() {
                $('#openModaldt').on('click', function() { $('#modaldt').fadeIn(200); });
                $('#openModalCuadrosdt').on('click', function() { $('#modalCuadrosdt').fadeIn(200); });
                $('#openModalResolucionesdt').on('click', function() { $('#modalResolucionesdt').fadeIn(200); }); 
                
                $(window).on('click', function(event) {
                    if ($(event.target).hasClass("modal-dt-bon")) { $(event.target).fadeOut(200); }
                });

                if (!$.fn.DataTable.isDataTable('#direccionTesisTable')) {
                    $('#direccionTesisTable').DataTable({
                        responsive: true,
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                        language: { url: '//cdn.datatables.net/plug-ins/2.0.0/i18n/es-ES.json' }
                    });
                }

                $('#ano_xls_dt').on('change', function() { applyYearFilterDT('ano_xls_dt', 'identificador_solicitud_dt'); });
                $('#ano_cuadros_dt').on('change', function() { applyYearFilterDT('ano_cuadros_dt', 'cuadro_identificador_solicitud_dt'); });
                $('#ano_res_dt').on('change', function() { applyYearFilterDT('ano_res_dt', 'cuadro_identificador_dt'); });
            });
        }

        if (window.jQuery) { initDTBonModule(); } 
        else { var checkInt = setInterval(function() { if (window.jQuery) { clearInterval(checkInt); initDTBonModule(); } }, 10); }
    })();
</script>