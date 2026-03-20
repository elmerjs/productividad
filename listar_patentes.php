<?php
// Requerir la conexión a la base de datos
include_once 'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Consulta SQL optimizada
$sql = "
SELECT 
    p.id_patente,
    MAX(f.nombre_fac_min) AS `FACULTAD`,
    MAX(d.depto_nom_propio) AS `DEPARTAMENTO`,
    p.numero_oficio,
    p.identificador,
    p.fecha_solicitud,
    p.producto,
    p.numero_profesores,
    p.puntaje,
    p.estado,
    p.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    patentes p
LEFT JOIN 
    patente_profesor pp ON pp.id_patente = p.id_patente
LEFT JOIN 
    tercero t ON pp.id_profesor = t.documento_tercero
LEFT JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
LEFT JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

if (!empty($identificador)) {
    $sql .= " AND p.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND p.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

$sql .= " GROUP BY p.id_patente ORDER BY p.id_patente DESC";
$result = $conn->query($sql);

// Consulta para identificadores y años
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM patentes WHERE identificador IS NOT NULL ORDER BY identificador DESC"); 
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

// Últimos lotes
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador FROM patentes WHERE identificador IS NOT NULL AND identificador != '' ORDER BY identificador DESC LIMIT 6");
$ultimos_lotes = [];
if ($ultimos_lotes_result) {
    while ($row = $ultimos_lotes_result->fetch_assoc()) {
        $ultimos_lotes[] = $row['identificador'];
    }
}
?>

<style>
    /* PREFIJO: patentes_ */
    .patentes-module-wrapper {
        background-color: transparent;
        padding: 0;
        color: #334155;
    }
    .patentes-page-header {
        margin-bottom: 1.2rem;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 0.8rem;
    }
    #patentes_tabla_principal thead th {
        background-color: #f8fafc;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.7rem; 
        font-weight: 700;
        padding: 6px 8px; 
        text-transform: uppercase;
    }
    #patentes_tabla_principal tbody td {
        vertical-align: middle;
        font-size: 0.78rem; 
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        padding: 3px 8px !important; 
        line-height: 1.15; 
    }
    .patentes-modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background-color: rgba(15, 23, 42, 0.4); 
        backdrop-filter: blur(6px);
        padding-top: 6vh;
    }
    .patentes-modal-content {
        background-color: #ffffff;
        margin: auto;
        padding: 2rem;
        width: 90%; 
        max-width: 600px;
        border-radius: 20px; 
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
</style>

<div class="patentes-module-wrapper">
    <div class="patentes-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title-inner" style="font-weight: 700; color: #0f172a; font-size: 1.4rem;">Patentes Registradas</h1>
            <p class="page-subtitle-inner" style="color: #64748b; font-size: 0.85rem;">Listado maestro de innovación y propiedad intelectual</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button id="patentes_btn_xls" class="btn-modern btn-m-xls"><i class="fas fa-file-excel"></i> Exportar XLS</button>
            <button id="patentes_btn_cuadros" class="btn-modern btn-m-cuadros"><i class="fas fa-table"></i> Generar Cuadros</button>
            <button id="patentes_btn_resoluciones" class="btn-modern btn-m-res"><i class="fas fa-file-signature"></i> Resoluciones</button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="patentes_tabla_principal" class="table table-hover align-middle" style="width:100%">
            <thead>
                <tr>  
                    <th class="text-center">ID</th>                
                    <th>IDENTIF.</th>
                    <th>DEPARTAMENTO</th>
                    <th>OFICIO</th>
                    <th>PROFESORES</th>
                    <th>PRODUCTO</th>
                    <th class="text-center">PTS</th>  
                    <th>ESTADO</th>
                    <th class="text-center">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    $estadoOriginal = strtolower(trim($row['estado'] ?? ''));
                    $estadoTexto = strtoupper($estadoOriginal ?: 'SIN ESTADO');
                    
                    if ($estadoOriginal === 'an' || strpos($estadoOriginal, 'anulado') !== false) {
                        $htmlEstado = '<span class="status-pill status-anulado"><span class="status-dot bg-secondary"></span>ANULADO</span>';
                    } else {
                        $dotColor = 'bg-secondary'; 
                        if ($estadoOriginal === 'ac' || strpos($estadoOriginal, 'aprobado') !== false) $dotColor = 'bg-success';
                        elseif (strpos($estadoOriginal, 're') !== false) $dotColor = 'bg-danger';
                        elseif (strpos($estadoOriginal, 'pe') !== false) $dotColor = 'bg-warning';
                        $htmlEstado = '<span class="status-pill"><span class="status-dot ' . $dotColor . '"></span>' . $estadoTexto . '</span>';
                    }

                    echo '<tr>';
                    echo '<td class="text-center fw-bold text-primary">' . $row['id_patente'] . '</td>';
                    echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['identificador']) . '</span></td>';
                    echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 140px;">' . htmlspecialchars($row['DEPARTAMENTO']) . '</div></td>';
                    echo '<td><small class="text-secondary">' . htmlspecialchars($row['numero_oficio']) . '</small></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 160px;" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">' . htmlspecialchars(substr($row['DETALLES_PROFESORES'], 0, 30)) . '...</div></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 200px;">' . htmlspecialchars($row['producto']) . '</div></td>';
                    echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntaje']) . '</td>';
                    echo '<td>' . $htmlEstado . '</td>';
                    echo '<td class="text-center text-nowrap">';
                    echo '<a href="editar_patentes.php?id=' . $row['id_patente'] . '" class="btn btn-light border btn-action text-primary shadow-sm"><i class="fas fa-pen"></i></a> ';
                    echo '<button class="btn btn-light border btn-action text-danger shadow-sm" onclick="patentes_confirmDelete(' . $row['id_patente'] . ')"><i class="fas fa-trash-alt"></i></button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="quick-audit-section">
        <div class="quick-audit-title"><i class="fas fa-folder-tree text-secondary me-1"></i> Auditoría de Lotes (Patentes)</div>
        <div class="lotes-carousel">
            <?php foreach($ultimos_lotes as $lote): ?>
                <a href="auditoria_lote.php?lote=<?= urlencode($lote) ?>" class="lote-card"><i class="fas fa-lightbulb text-warning"></i> <?= htmlspecialchars($lote) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="patentes_modalXls" class="patentes-modal">
    <div class="patentes-modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS Patentes</h4>
             <span class="close patentes_closeModalXls">&times;</span>
        </div>
        <form action="report_patentes.php" method="GET">
            <div class="mb-3">
                <label class="form-label text-secondary fw-semibold">Año:</label>
                <select name="ano" id="patentes_ano_xls" class="form-select">
                    <option value="">Todos los años...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Identificador:</label>
                <select name="identificador_solicitud" id="patentes_identificador_xls" class="form-select">
                    <option value="">Selecciona...</option>
                    <?php foreach ($identificadores as $id) echo '<option value="'.htmlspecialchars($id).'" data-ano="'.substr($id,0,4).'">'.htmlspecialchars($id).'</option>'; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100">Generar Reporte</button>
        </form>
    </div>
</div>

<div id="patentes_modalCuadros" class="patentes-modal">
    <div class="patentes-modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros</h4>
             <span class="close patentes_closeModalCuadros">&times;</span>
        </div>
        <form action="cuadros_patentes.php" method="GET">
            <div class="mb-3">
                <label class="form-label text-secondary fw-semibold">Año:</label>
                <select name="cuadro_ano" id="patentes_ano_cuadros" class="form-select">
                    <option value="">Todos...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Identificador:</label>
                <select name="cuadro_identificador_solicitud" id="patentes_identificador_cuadros" class="form-select">
                    <option value="">Selecciona...</option>
                    <?php foreach ($identificadores as $id) echo '<option value="'.htmlspecialchars($id).'" data-ano="'.substr($id,0,4).'">'.htmlspecialchars($id).'</option>'; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Generar Cuadro</button>
        </form>
    </div>
</div>

<div id="patentes_modalResoluciones" class="patentes-modal">
    <div class="patentes-modal-content" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-info m-0" style="color: #0284c7 !important;"><i class="fas fa-file-word me-2"></i>Resoluciones Patentes</h4>
             <span class="close patentes_closeModalResoluciones">&times;</span>
        </div>
        <form action="resoluciones_patentes.php" method="GET">
            <div class="row bg-light p-3 mb-4 border rounded-3 mx-0">
                <div class="col-md-6 mb-2">
                    <label for="patentes_filtro_ano_res" class="form-label text-secondary fw-semibold">Año del Paquete:</label>
                    <select id="patentes_filtro_ano_res" class="form-select">
                        <option value="todos">Todos los años</option>
                        <?php foreach ($unique_years as $ano_val) echo '<option value="'.htmlspecialchars($ano_val).'">'.htmlspecialchars($ano_val).'</option>'; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="patentes_identificador_res" class="form-label text-secondary fw-semibold">Identificador:</label>
                    <select name="cuadro_identificador_patente" id="patentes_identificador_res" class="form-select" required>
                        <option value="">Selecciona un identificador</option>
                        <?php foreach ($identificadores as $id) echo '<option value="'.htmlspecialchars($id).'" data-ano="'.substr($id,0,4).'">'.htmlspecialchars($id).'</option>'; ?>
                    </select>
                </div>
            </div>

            <h6 class="mb-3 text-secondary border-bottom pb-2 fw-bold">Datos de la Resolución (Opcionales)</h6>
            <div class="row px-2">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Número de resolución:</label>
                    <input type="text" name="num_resolucion" class="form-control" placeholder="Ej: 045">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Fecha de la resolución:</label>
                    <input type="date" name="fecha_resolucion" class="form-control">
                </div>
            </div>

            <div class="row px-2">
                <div class="col-md-8 mb-3">
                    <label class="form-label text-muted">Firma (Vicerrector/a):</label>
                    <input type="text" name="nombre_vicerrector" class="form-control" value="AIDA PATRICIA GONZÁLEZ NIEVA" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Género:</label>
                    <select name="genero_vicerrector" class="form-select" required>
                        <option value="F">Femenino</option>
                        <option value="M">Masculino</option>
                    </select>
                </div>
            </div>

            <div class="row px-2">
                <div class="col-md-6 mb-4">
                    <label class="form-label text-muted">Revisó:</label>
                    <input type="text" name="nombre_reviso" class="form-control" value="Marjhory Castro" required>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label text-muted">Elaboró:</label>
                    <input type="text" name="nombre_elaboro" class="form-control" value="Elizete Rivera" required>
                </div>
            </div>

            <div class="d-grid mt-2">
                <button type="submit" class="btn btn-info text-white btn-lg shadow-sm rounded-3" style="background-color: #0ea5e9; border-color: #0ea5e9;"><i class="fas fa-file-word me-2"></i>Generar Word</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    function initPatentes() {
        $(function() {
            // Modales
            $('#patentes_btn_xls').on('click', function() { $('#patentes_modalXls').fadeIn(200); });
            $('#patentes_btn_cuadros').on('click', function() { $('#patentes_modalCuadros').fadeIn(200); });
            $('#patentes_btn_resoluciones').on('click', function() { $('#patentes_modalResoluciones').fadeIn(200); });

            $('.patentes_closeModalXls').on('click', function() { $('#patentes_modalXls').fadeOut(200); });
            $('.patentes_closeModalCuadros').on('click', function() { $('#patentes_modalCuadros').fadeOut(200); });
            $('.patentes_closeModalResoluciones').on('click', function() { $('#patentes_modalResoluciones').fadeOut(200); });

            $(window).on('click', function(e) {
                if ($(e.target).hasClass('patentes-modal')) $('.patentes-modal').fadeOut(200);
            });

            // Lógica de filtrado por año
            function applyFilter(yearId, targetId) {
                const year = $('#' + yearId).val();
                $('#' + targetId + ' option').each(function() {
                    const optYear = $(this).data('ano');
                    if (year === "todos" || year === "" || !optYear || optYear == year) $(this).show();
                    else $(this).hide();
                });
                $('#' + targetId).val("");
            }

            $('#patentes_ano_xls').on('change', function() { applyFilter('patentes_ano_xls', 'patentes_identificador_xls'); });
            $('#patentes_ano_cuadros').on('change', function() { applyFilter('patentes_ano_cuadros', 'patentes_identificador_cuadros'); });
            $('#patentes_filtro_ano_res').on('change', function() { applyFilter('patentes_filtro_ano_res', 'patentes_identificador_res'); });

            // Función Global
            window.patentes_confirmDelete = function(id) {
                if (confirm("¿Estás seguro de que quieres eliminar esta patente?")) {
                    const motivo = prompt("Motivo de la anulación:");
                    if (motivo && motivo.trim() !== "") {
                        window.location.href = 'eliminar_solicitud_patente.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
                    } else if (motivo !== null) {
                        alert("El motivo es obligatorio.");
                    }
                }
            };
        });
    }

    if (window.jQuery) initPatentes();
    else {
        const checkJQ = setInterval(function() {
            if (window.jQuery) { clearInterval(checkJQ); initPatentes(); }
        }, 20);
    }
})();
</script>