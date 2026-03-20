<?php
// Requerir la conexión a la base de datos
include_once  'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    p.id,
    p.identificador,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`, 
    p.numero_oficio,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    p.nombre_evento AS `EVENTO_PREMIO`,
    p.ambito AS `AMBITO`,
    p.categoria_premio AS `CATEGORIA_PREMIO`,
    p.nivel_ganado AS `NIVEL_GANADO`,
    p.lugar_fecha AS `LUGAR_Y_FECHA`, 
    p.estado,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`,
    p.numero_oficio AS `OFICIO`, 
    p.puntos
FROM 
    premios p 
JOIN 
    premios_profesor pp ON pp.id_premio = p.id
JOIN 
    tercero t ON pp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 
    1 = 1
";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND p.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(p.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados y ordenar descendente
$sql .= " GROUP BY p.id, f.nombre_fac_min, d.depto_nom_propio, p.nombre_evento, p.ambito, p.categoria_premio, p.nivel_ganado, p.lugar_fecha, p.numero_oficio";
$sql .= " ORDER BY p.id DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

// Obtener los identificadores de solicitud para los filtros
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM premios ORDER BY identificador DESC");
$identificadores = [];
$unique_years = [];

while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
    $year = substr($row['identificador'], 0, 4);
    if (!empty($year) && is_numeric($year) && !in_array($year, $unique_years)) {
        $unique_years[] = $year;
    }
}
rsort($unique_years);

// --- OBTENER LOS ÚLTIMOS 6 LOTES PARA EL CARRUSEL ---
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador FROM premios WHERE identificador IS NOT NULL AND identificador != '' ORDER BY identificador DESC LIMIT 6");
$ultimos_lotes = [];
if ($ultimos_lotes_result) {
    while ($row = $ultimos_lotes_result->fetch_assoc()) {
        $ultimos_lotes[] = $row['identificador'];
    }
}
?>

<style>
    /* 1. AJUSTES DE INTEGRACIÓN CON PREFIJO */
    .premios-module-wrapper {
        background-color: transparent;
        padding: 0;
        color: #334155;
    }

    .premios-page-header {
        margin-bottom: 1.2rem;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 0.8rem;
    }

    /* 5. TABLA ULTRA-COMPRIMIDA */
    #premios_tabla_principal thead th {
        background-color: #f8fafc;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.7rem; 
        font-weight: 700;
        padding: 6px 8px; 
        text-transform: uppercase;
    }
    #premios_tabla_principal tbody td {
        vertical-align: middle;
        font-size: 0.78rem; 
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        padding: 3px 8px !important; 
        line-height: 1.15; 
    }

    /* 7. MODALES CON ID ÚNICO */
    .premios-modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background-color: rgba(15, 23, 42, 0.4); 
        backdrop-filter: blur(6px);
        padding-top: 6vh;
    }
    .premios-modal-content {
        background-color: #ffffff;
        margin: auto;
        padding: 2rem;
        width: 90%; 
        max-width: 600px;
        border-radius: 20px; 
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
</style>

<div class="premios-module-wrapper">
    
    <div class="premios-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title-inner" style="font-weight: 700; color: #0f172a; font-size: 1.4rem;">Registro de Premios</h1>
            <p class="page-subtitle-inner" style="color: #64748b; font-size: 0.85rem;">Listado maestro de reconocimientos y galardones académicos</p>
        </div>
        
        <div class="d-flex gap-2 flex-wrap">
            <button id="premios_openModalXls" class="btn-modern btn-m-xls">
                <i class="fas fa-file-excel"></i> Exportar XLS
            </button>
            <button id="premios_openModalCuadros" class="btn-modern btn-m-cuadros">
                <i class="fas fa-table"></i> Generar Cuadros
            </button>
            <button id="premios_openModalResoluciones" class="btn-modern btn-m-res">
                <i class="fas fa-file-signature"></i> Resoluciones
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="premios_tabla_principal" class="table table-hover align-middle" style="width:100%">
            <thead>
                <tr>                
                    <th class="text-center">ID</th>
                    <th>IDENTIF.</th>
                    <th>DEPARTAMENTO</th>
                    <th>OFICIO</th>
                    <th>PROFESORES</th>
                    <th>EVENTO / PREMIO</th>
                    <th>ÁMBITO</th>
                    <th>CATEGORÍA</th>
                    <th>NIVEL</th>
                    <th class="text-center">PTS</th>
                    <th>ESTADO</th>
                    <th class="text-center">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    $facultad = str_replace("Facultad de ", "", $row['FACULTAD']);
                    $estadoOriginal = strtolower(trim($row['estado']));
                    $estadoTexto = strtoupper($estadoOriginal ?: 'SIN ESTADO');
                    
                    if ($estadoOriginal === 'an' || strpos($estadoOriginal, 'anulado') !== false) {
                        $estadoTexto = 'ANULADO';
                        $htmlEstado = '<span class="status-pill status-anulado"><span class="status-dot bg-secondary"></span>' . $estadoTexto . '</span>';
                    } else {
                        $dotColor = 'bg-secondary'; 
                        if ($estadoOriginal === 'ac' || strpos($estadoOriginal, 'aprobado') !== false) {
                            $dotColor = 'bg-success'; 
                            $estadoTexto = 'ACTIVO';
                        }
                        elseif (strpos($estadoOriginal, 're') !== false) $dotColor = 'bg-danger';
                        elseif (strpos($estadoOriginal, 'pe') !== false) $dotColor = 'bg-warning';
                        $htmlEstado = '<span class="status-pill"><span class="status-dot ' . $dotColor . '"></span>' . $estadoTexto . '</span>';
                    }

                    echo '<tr>';
                    echo '<td class="text-center fw-bold text-primary">' . $row['id'] . '</td>';
                    echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['identificador']) . '</span></td>';
                    echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 120px;" title="Facultad: ' . htmlspecialchars($facultad) . '">' . htmlspecialchars($row['DEPARTAMENTO']) . '</div></td>';
                    echo '<td><small class="text-secondary">' . htmlspecialchars($row['numero_oficio']) . '</small></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 140px;" title="' . htmlspecialchars($row['DETALLES PROFESORES']) . '">' . htmlspecialchars($row['NOMBRES']) . '</div></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 160px;" title="' . htmlspecialchars($row['EVENTO_PREMIO']) . '">' . htmlspecialchars($row['EVENTO_PREMIO']) . '</div></td>';
                    echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['AMBITO']) . '</span></td>';
                    echo '<td><div class="text-truncate-custom" style="max-width: 100px;" title="' . htmlspecialchars($row['CATEGORIA_PREMIO']) . '">' . htmlspecialchars($row['CATEGORIA_PREMIO']) . '</div></td>';
                    echo '<td><small class="text-muted">' . htmlspecialchars($row['NIVEL_GANADO']) . '</small></td>';
                    echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntos']) . '</td>';
                    echo '<td>' . $htmlEstado . '</td>';
                    echo '<td class="text-center text-nowrap">';
                    echo '<a href="editar_premios.php?id=' . $row['id'] . '" class="btn btn-light border btn-action text-primary shadow-sm"><i class="fas fa-pen"></i></a> ';
                    echo '<button class="btn btn-light border btn-action text-danger shadow-sm" onclick="premios_confirmarAnulacion(' . $row['id'] . ')"><i class="fas fa-trash-alt"></i></button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="quick-audit-section">
        <div class="quick-audit-title"><i class="fas fa-folder-tree text-secondary me-1"></i> Auditoría de Lotes</div>
        <div class="lotes-carousel">
            <?php foreach($ultimos_lotes as $lote): ?>
                <a href="#" class="lote-card"><i class="fas fa-trophy text-warning"></i> <?php echo htmlspecialchars($lote); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="premios_modalXls" class="premios-modal">
    <div class="premios-modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS Premios</h4>
             <span class="close premios_closeModalXls">&times;</span>
        </div>
        <form action="report_premios.php" method="GET">
            <div class="mb-3">
                <label class="form-label text-secondary fw-semibold">Identificador:</label>
                <select name="identificador_solicitud" id="premios_identificador_xls" class="form-select">
                    <option value="">Selecciona un identificador</option>
                    <?php foreach ($identificadores as $id_sol): ?>
                        <option value="<?= htmlspecialchars($id_sol['identificador']) ?>"><?= htmlspecialchars($id_sol['identificador']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Año:</label>
                <input type="number" name="ano" id="premios_ano_xls" class="form-control" placeholder="Ej: 2024">
            </div>
            <button type="submit" class="btn btn-success w-100">Generar Reporte</button>
        </form>
    </div>
</div>

<div id="premios_modalCuadros" class="premios-modal">
    <div class="premios-modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros</h4>
             <span class="close premios_closeModalCuadros">&times;</span>
        </div>
        <form action="cuadros_premios.php" method="GET">
            <div class="mb-3">
                <label class="form-label text-secondary fw-semibold">Identificador:</label>
                <select name="cuadro_identificador" id="premios_identificador_cuadros" class="form-select">
                    <option value="">Selecciona...</option>
                    <?php foreach ($identificadores as $id_sol): ?>
                        <option value="<?= htmlspecialchars($id_sol['identificador']) ?>"><?= htmlspecialchars($id_sol['identificador']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary fw-semibold">Año:</label>
                <input type="number" name="cuadro_ano" id="premios_ano_cuadros" class="form-control" placeholder="Ej: 2024">
            </div>
            <button type="submit" class="btn btn-primary w-100">Generar Cuadro</button>
        </form>
    </div>
</div>

<div id="premios_modalResoluciones" class="premios-modal">
        <div class="modal-content" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-info m-0" style="color: #0284c7 !important;"><i class="fas fa-file-word me-2"></i>Resoluciones Premios</h4>
             <span class="close close-resoluciones-pr">&times;</span>
        </div>
        <div class="modal-body p-0">
            <form action="resoluciones_premios.php" method="GET">
                
                <div class="row bg-light p-3 mb-4 border rounded-3 mx-0">
                    <div class="col-md-6 mb-2">
                        <label for="filtro_ano_premios" class="form-label text-secondary fw-semibold">Año del Paquete:</label>
                        <select id="filtro_ano_premios" class="form-select">
                            <option value="todos">Todos los años</option>
                            <?php
                            foreach ($unique_years as $ano_val) {
                                echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="cuadro_identificador_premio" class="form-label text-secondary fw-semibold">Identificador:</label>
                        <select name="cuadro_identificador_premio" id="cuadro_identificador_premio" class="form-select" required>
                            <option value="">Selecciona un identificador</option>
                            <?php
                            foreach ($identificadores as $row_ident) {
                                echo '<option value="' . htmlspecialchars($row_ident['identificador']) . '" data-ano="' . substr($row_ident['identificador'], 0, 4) . '">'
                                    . htmlspecialchars($row_ident['identificador']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <h6 class="mb-3 text-secondary border-bottom pb-2 fw-bold">Datos de la Resolución (Opcionales)</h6>
                <div class="row px-2">
                    <div class="col-md-6 mb-3">
                        <label for="num_resolucion" class="form-label text-muted">Número de resolución:</label>
                        <input type="text" name="num_resolucion" id="num_resolucion" class="form-control" placeholder="Ej: 045">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fecha_resolucion" class="form-label text-muted">Fecha de la resolución:</label>
                        <input type="date" name="fecha_resolucion" id="fecha_resolucion" class="form-control">
                    </div>
                </div>

                <div class="row px-2">
                    <div class="col-md-8 mb-3">
                        <label for="nombre_vicerrector" class="form-label text-muted">Firma (Vicerrector/a):</label>
                        <input type="text" name="nombre_vicerrector" id="nombre_vicerrector" class="form-control" value="AIDA PATRICIA GONZÁLEZ NIEVA" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="genero_vicerrector" class="form-label text-muted">Género:</label>
                        <select name="genero_vicerrector" id="genero_vicerrector" class="form-select" required>
                            <option value="F">Femenino</option>
                            <option value="M">Masculino</option>
                        </select>
                    </div>
                </div>

                <div class="row px-2">
                    <div class="col-md-6 mb-4">
                        <label for="nombre_reviso" class="form-label text-muted">Revisó:</label>
                        <input type="text" name="nombre_reviso" id="nombre_reviso" class="form-control" value="Marjhory Castro" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="nombre_elaboro" class="form-label text-muted">Elaboró:</label>
                        <input type="text" name="nombre_elaboro" id="nombre_elaboro" class="form-control" value="Elizete Rivera" required>
                    </div>
                </div>

                <div class="d-grid mt-2">
                    <button type="submit" class="btn btn-info text-white btn-lg shadow-sm rounded-3" style="background-color: #0ea5e9; border-color: #0ea5e9;"><i class="fas fa-file-word me-2"></i>Generar Word</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Encapsulamiento del Módulo de Premios
 */
(function() {
    function initModulePremios() {
        $(function() {
            // Referencias a modales
            const $mXls = $('#premios_modalXls');
            const $mCuadros = $('#premios_modalCuadros');
            const $mRes = $('#premios_modalResoluciones');

            // Abrir Modales
            $('#premios_openModalXls').on('click', function() { $mXls.fadeIn(200); });
            $('#premios_openModalCuadros').on('click', function() { $mCuadros.fadeIn(200); });
            $('#premios_openModalResoluciones').on('click', function() { $mRes.fadeIn(200); });

            // Cerrar Modales
            $('.premios_closeModalXls').on('click', function() { $mXls.fadeOut(200); });
            $('.premios_closeModalCuadros').on('click', function() { $mCuadros.fadeOut(200); });
            $('.premios_closeModalResoluciones').on('click', function() { $mRes.fadeOut(200); });

            $(window).on('click', function(e) {
                if ($(e.target).hasClass('premios-modal')) {
                    $('.premios-modal').fadeOut(200);
                }
            });

            // Lógica de filtrado de años en el modal de resoluciones
            $('#premios_filtro_ano_res').on('change', function() {
                const year = $(this).val();
                $('#premios_identificador_res').val("");
                $('#premios_identificador_res option').each(function() {
                    const optYear = $(this).data('ano');
                    if (year === "todos" || !optYear || optYear == year) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Exponer función de eliminación al ámbito global con prefijo
            window.premios_confirmarAnulacion = function(id) {
                if (confirm("¿Estás seguro de que quieres eliminar esta solicitud?")) {
                    const motivo = prompt("Indique el motivo de la anulación:");
                    if (motivo && motivo.trim() !== "") {
                        window.location.href = 'eliminar_solicitud_premio.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
                    } else if (motivo !== null) {
                        alert("El motivo es obligatorio.");
                    }
                }
            };
        });
    }

    // Mecanismo de espera de jQuery (Polling)
    if (window.jQuery) {
        initModulePremios();
    } else {
        const checkJQ = setInterval(function() {
            if (window.jQuery) {
                clearInterval(checkJQ);
                initModulePremios();
            }
        }, 20);
    }
})();
</script>