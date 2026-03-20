<?php
// Requerir la conexión a la base de datos
include_once('conn.php');

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL a prueba de fallos usando LEFT JOIN y MAX()
$sql = "
SELECT 
    t.id_titulo AS id,
    MAX(f.nombre_fac_min) AS `FACULTAD`, 
    MAX(d.depto_nom_propio) AS `DEPARTAMENTO`,
    t.identificador,
    t.numero_oficio,
    t.titulo_obtenido,
    t.tipo,
    t.tipo_estudio,
    t.institucion,
    t.fecha_terminacion,
    t.resolucion_convalidacion,
    t.puntaje,
    t.tipo_productividad,
    t.estado_titulo AS estado,
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' - ', ter.documento_tercero)
        ORDER BY ter.nombre_completo
        SEPARATOR '\n'
    ) AS profesores
FROM 
    titulos t
LEFT JOIN 
    titulo_profesor tp ON tp.id_titulo = t.id_titulo
LEFT JOIN 
    tercero ter ON tp.fk_tercero = ter.documento_tercero
LEFT JOIN 
    deparmanentos d ON d.PK_DEPTO = ter.fk_depto
LEFT JOIN 
    facultad f ON f.PK_FAC = d.FK_FAC
WHERE 1 = 1";

if (!empty($identificador)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

$sql .= " GROUP BY t.id_titulo ORDER BY t.id_titulo DESC";
$result = $conn->query($sql);

// Extracción de identificadores y años
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM titulos WHERE identificador IS NOT NULL ORDER BY identificador DESC");
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
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador FROM titulos WHERE identificador IS NOT NULL AND identificador != '' ORDER BY identificador DESC LIMIT 6");
$ultimos_lotes = [];
if ($ultimos_lotes_result) {
    while ($row = $ultimos_lotes_result->fetch_assoc()) {
        $ultimos_lotes[] = $row['identificador'];
    }
}
?>

<style>
    /* PREFIJO: titulos_ */
    .titulos-module-wrapper { background-color: transparent; padding: 0; color: #334155; }
    .titulos-page-header { margin-bottom: 1.2rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.8rem; }
    
    #titulos_tabla_principal thead th {
        background-color: #f8fafc; color: #475569; border-bottom: 2px solid #e2e8f0;
        font-size: 0.7rem; font-weight: 700; padding: 6px 8px; text-transform: uppercase;
    }
    #titulos_tabla_principal tbody td {
        vertical-align: middle; font-size: 0.78rem; color: #334155;
        border-bottom: 1px solid #f1f5f9; padding: 3px 8px !important; line-height: 1.15; 
    }

    .titulos-modal {
        display: none; position: fixed; z-index: 1050; left: 0; top: 0;
        width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.4); 
        backdrop-filter: blur(6px); padding-top: 6vh;
    }
    .titulos-modal-content {
        background-color: #ffffff; margin: auto; padding: 2rem;
        width: 90%; max-width: 600px; border-radius: 20px; 
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
</style>

<div class="titulos-module-wrapper">
    <div class="titulos-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title-inner" style="font-weight: 700; color: #0f172a; font-size: 1.4rem;">Títulos Obtenidos</h1>
            <p class="page-subtitle-inner" style="color: #64748b; font-size: 0.85rem;">Listado maestro de formaciones de posgrado y reconocimientos</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button id="titulos_btn_xls" class="btn-modern btn-m-xls"><i class="fas fa-file-excel"></i> Exportar XLS</button>
            <button id="titulos_btn_cuadros" class="btn-modern btn-m-cuadros"><i class="fas fa-table"></i> Generar Cuadros</button>
            <button id="titulos_btn_resoluciones" class="btn-modern btn-m-res"><i class="fas fa-file-signature"></i> Resoluciones</button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="titulos_tabla_principal" class="table table-hover align-middle" style="width:100%">
            <thead>
                <tr> 
                    <th class="text-center">ID</th>                
                    <th>IDENTIF.</th>
                    <th>DEPARTAMENTO</th>
                    <th>OFICIO</th>
                    <th>PROFESORES</th>
                    <th>TÍTULO OBTENIDO</th>
                    <th>TIPO EST.</th>
                    <th>INSTITUCIÓN</th>
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
                    echo '<td class="text-center fw-bold text-primary">' . $row['id'] . '</td>';
                    echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['identificador']) . '</span></td>';
                    echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 120px;">' . htmlspecialchars($row['DEPARTAMENTO'] ?? 'N/A') . '</div></td>';
                    echo '<td><small class="text-secondary">' . htmlspecialchars($row['numero_oficio']) . '</small></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 130px;" title="' . htmlspecialchars($row['profesores']) . '">' . htmlspecialchars(substr($row['profesores'], 0, 25)) . '...</div></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 160px;">' . htmlspecialchars($row['titulo_obtenido']) . '</div></td>';
                    echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['tipo_estudio']) . '</span></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 120px;">' . htmlspecialchars($row['institucion']) . '</div></td>';
                    echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntaje']) . '</td>';
                    echo '<td>' . $htmlEstado . '</td>';
                    echo '<td class="text-center text-nowrap">';
                    echo '<a href="editar_titulos.php?id=' . $row['id'] . '" class="btn btn-light border btn-action text-primary shadow-sm"><i class="fas fa-pen"></i></a> ';
                    echo '<button class="btn btn-light border btn-action text-danger shadow-sm" onclick="titulos_confirmDelete(' . $row['id'] . ')"><i class="fas fa-trash-alt"></i></button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="quick-audit-section">
        <div class="quick-audit-title"><i class="fas fa-folder-tree text-secondary me-1"></i> Auditoría de Lotes (Títulos)</div>
        <div class="lotes-carousel">
            <?php foreach($ultimos_lotes as $lote): ?>
                <a href="auditoria_lote.php?lote=<?= urlencode($lote) ?>" class="lote-card"><i class="fas fa-graduation-cap text-success"></i> <?= htmlspecialchars($lote) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="titulos_modalXls" class="titulos-modal">
    <div class="titulos-modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS Títulos</h4>
             <span class="close titulos_closeModalXls">&times;</span>
        </div>
        <form action="report_titulos.php" method="GET">
            <div class="mb-3">
                <label class="form-label text-secondary fw-semibold">Año:</label>
                <select name="ano" id="titulos_ano_xls" class="form-select">
                    <option value="">Todos...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Identificador:</label>
                <select name="identificador_solicitud" id="titulos_id_xls" class="form-select">
                    <option value="">Selecciona un paquete...</option>
                    <?php foreach ($identificadores as $id) echo '<option value="'.htmlspecialchars($id).'" data-ano="'.substr($id,0,4).'">'.htmlspecialchars($id).'</option>'; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100">Generar Reporte</button>
        </form>
    </div>
</div>

<div id="titulos_modalCuadros" class="titulos-modal">
    <div class="titulos-modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros</h4>
             <span class="close titulos_closeModalCuadros">&times;</span>
        </div>
        <form action="cuadros_titulos.php" method="GET">
            <div class="mb-3">
                <label class="form-label text-secondary fw-semibold">Año:</label>
                <select name="cuadro_ano" id="titulos_ano_cuadros" class="form-select">
                    <option value="">Todos...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Identificador:</label>
                <select name="cuadro_identificador_solicitud" id="titulos_id_cuadros" class="form-select">
                    <option value="">Selecciona...</option>
                    <?php foreach ($identificadores as $id) echo '<option value="'.htmlspecialchars($id).'" data-ano="'.substr($id,0,4).'">'.htmlspecialchars($id).'</option>'; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Generar Cuadro</button>
        </form>
    </div>
</div>

<div id="titulos_modalResoluciones" class="titulos-modal">
    <div class="titulos-modal-content" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-info m-0" style="color: #0284c7 !important;"><i class="fas fa-file-word me-2"></i>Resoluciones Títulos</h4>
             <span class="close titulos_closeModalResoluciones">&times;</span>
        </div>
        <form action="resoluciones_titulos.php" method="GET">
            <div class="row bg-light p-3 mb-4 border rounded-3 mx-0">
                <div class="col-md-6 mb-2">
                    <label class="form-label text-secondary fw-semibold">Año del Paquete:</label>
                    <select id="titulos_filtro_ano_res" class="form-select">
                        <option value="todos">Todos los años</option>
                        <?php foreach ($unique_years as $y) echo '<option value="'.$y.'">'.$y.'</option>'; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label text-secondary fw-semibold">Identificador:</label>
                    <select name="cuadro_identificador_titulo" id="titulos_id_res" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($identificadores as $id) echo '<option value="'.htmlspecialchars($id).'" data-ano="'.substr($id,0,4).'">'.htmlspecialchars($id).'</option>'; ?>
                    </select>
                </div>
            </div>
            <div class="row px-2">
                <div class="col-md-6 mb-3"><label class="form-label text-muted">Num. Resolución:</label><input type="text" name="num_resolucion" class="form-control" placeholder="Ej: 045"></div>
                <div class="col-md-6 mb-3"><label class="form-label text-muted">Fecha:</label><input type="date" name="fecha_resolucion" class="form-control"></div>
            </div>
            <div class="row px-2">
                <div class="col-md-8 mb-3"><label class="form-label text-muted">Firma:</label><input type="text" name="nombre_vicerrector" class="form-control" value="AIDA PATRICIA GONZÁLEZ NIEVA" required></div>
                <div class="col-md-4 mb-3"><label class="form-label text-muted">Género:</label><select name="genero_vicerrector" class="form-select"><option value="F">Femenino</option><option value="M">Masculino</option></select></div>
            </div>
            <div class="row px-2">
                <div class="col-md-6 mb-4"><label class="form-label text-muted">Revisó:</label><input type="text" name="nombre_reviso" class="form-control" value="Marjhory Castro" required></div>
                <div class="col-md-6 mb-4"><label class="form-label text-muted">Elaboró:</label><input type="text" name="nombre_elaboro" class="form-control" value="Elizete Rivera" required></div>
            </div>
            <div class="d-grid mt-2"><button type="submit" class="btn btn-info text-white">Generar Word</button></div>
        </form>
    </div>
</div>

<script>
(function() {
    function initTitulos() {
        $(function() {
            // Control de apertura
            $('#titulos_btn_xls').on('click', function() { $('#titulos_modalXls').fadeIn(200); });
            $('#titulos_btn_cuadros').on('click', function() { $('#titulos_modalCuadros').fadeIn(200); });
            $('#titulos_btn_resoluciones').on('click', function() { $('#titulos_modalResoluciones').fadeIn(200); });

            // Control de cierre
            $('.titulos_closeModalXls').on('click', function() { $('#titulos_modalXls').fadeOut(200); });
            $('.titulos_closeModalCuadros').on('click', function() { $('#titulos_modalCuadros').fadeOut(200); });
            $('.titulos_closeModalResoluciones').on('click', function() { $('#titulos_modalResoluciones').fadeOut(200); });

            // Cerrar al click afuera
            $(window).on('click', function(e) { if ($(e.target).hasClass('titulos-modal')) $('.titulos-modal').fadeOut(200); });

            // Filtrado de años
            function applyYearFilter(yearId, targetId) {
                const year = $('#' + yearId).val();
                $('#' + targetId + ' option').each(function() {
                    const optYear = $(this).data('ano');
                    if (year === "todos" || year === "" || !optYear || optYear == year) $(this).show();
                    else $(this).hide();
                });
                $('#' + targetId).val("");
            }

            $('#titulos_ano_xls').on('change', function() { applyYearFilter('titulos_ano_xls', 'titulos_id_xls'); });
            $('#titulos_ano_cuadros').on('change', function() { applyYearFilter('titulos_ano_cuadros', 'titulos_id_cuadros'); });
            $('#titulos_filtro_ano_res').on('change', function() { applyYearFilter('titulos_filtro_ano_res', 'titulos_id_res'); });

            // Acción de eliminar
            window.titulos_confirmDelete = function(id) {
                if (confirm("¿Seguro que desea eliminar este título?")) {
                    const motivo = prompt("Motivo de anulación:");
                    if (motivo && motivo.trim() !== "") {
                        window.location.href = 'eliminar_titulo.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
                    } else if (motivo !== null) alert("Motivo obligatorio.");
                }
            };
        });
    }

    if (window.jQuery) initTitulos();
    else {
        const checkJQ = setInterval(function() {
            if (window.jQuery) { clearInterval(checkJQ); initTitulos(); }
        }, 20);
    }
})();
</script>