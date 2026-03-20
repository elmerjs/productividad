<?php
// Incluir el archivo de conexión
include_once('conn.php'); 

// Obtener los filtros desde el formulario (si existen)
$identificador_completo = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numeroOficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Consulta SQL con LEFT JOIN, manejo seguro de NULLs y columnas de resolución incluidas
$sql = "
    SELECT 
        pb.id AS id,
        MAX(f.nombre_fac_min) AS `FACULTAD`,
        MAX(d.depto_nom_propio) AS `DEPARTAMENTO`,
        pb.identificador_completo,
        pb.numeroOficio,
        pb.fecha_solicitud,
        pb.tipo_producto,
        pb.nombre_revista,
        pb.producto,
        pb.isbn,
        pb.fecha_publicacion,
        pb.lugar_publicacion,
        pb.autores,
        pb.evaluacion1,
        pb.evaluacion2,
        pb.puntaje,
        pb.puntaje_final,
        pb.tipo_productividad,
        pb.estado,
        pb.num_resolucion,
        pb.fecha_resolucion,
        pb.nombre_vicerrector,
        pb.genero_vicerrector,
        pb.nombre_reviso,
        pb.nombre_elaboro,
        GROUP_CONCAT(
            DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
            ORDER BY ter.documento_tercero
            SEPARATOR '\n'
        ) AS `DETALLES_PROFESORES`
    FROM 
        publicacion_bon pb
    LEFT JOIN 
        publicacion_bon_profesor pbp ON pbp.id_publicacion_bon = pb.id
    LEFT JOIN 
        tercero ter ON pbp.documento_profesor = ter.documento_tercero
    LEFT JOIN 
        deparmanentos d ON ter.fk_depto = d.PK_DEPTO
    LEFT JOIN 
        facultad f ON d.FK_FAC = f.PK_FAC
    WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador_completo)) {
    $sql .= " AND pb.identificador_completo = '" . $conn->real_escape_string($identificador_completo) . "'";
}
if (!empty($numeroOficio)) {
    $sql .= " AND pb.numeroOficio = '" . $conn->real_escape_string($numeroOficio) . "'";
}

// Agrupar para evitar error ONLY_FULL_GROUP_BY
$sql .= " 
    GROUP BY 
        pb.id, pb.identificador_completo, pb.numeroOficio, pb.fecha_solicitud, 
        pb.tipo_producto, pb.nombre_revista, pb.producto, pb.isbn, 
        pb.fecha_publicacion, pb.lugar_publicacion, pb.autores, 
        pb.evaluacion1, pb.evaluacion2, pb.puntaje, pb.puntaje_final, 
        pb.tipo_productividad, pb.estado, pb.num_resolucion, pb.fecha_resolucion, 
        pb.nombre_vicerrector, pb.genero_vicerrector, pb.nombre_reviso, pb.nombre_elaboro
    ORDER BY 
        pb.id DESC;
";

// Ejecutar la consulta
$result = $conn->query($sql);

if (!$result) {
    echo "<div class='alert alert-danger m-3'>Error SQL en Publicaciones Bonificación: " . $conn->error . "</div>";
}

// Obtener los identificadores y extraer años para modales
$identificadores_result = $conn->query("SELECT DISTINCT identificador_completo FROM publicacion_bon WHERE identificador_completo IS NOT NULL ORDER BY identificador_completo DESC");
$identificadores = [];
$unique_years = [];

if ($identificadores_result) {
    while ($row = $identificadores_result->fetch_assoc()) {
        $id_str = $row['identificador_completo'];
        $identificadores[] = $id_str;
        
        $year = substr($id_str, 0, 4);
        if (!empty($year) && is_numeric($year) && !in_array($year, $unique_years)) {
            $unique_years[] = $year;
        }
    }
    rsort($unique_years); // Ordenar años de mayor a menor
}

// Obtener los últimos 6 lotes para el carrusel
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador_completo FROM publicacion_bon WHERE identificador_completo IS NOT NULL AND identificador_completo != '' ORDER BY identificador_completo DESC LIMIT 6");
$ultimos_lotes = [];
if ($ultimos_lotes_result) {
    while ($row = $ultimos_lotes_result->fetch_assoc()) {
        $ultimos_lotes[] = $row['identificador_completo'];
    }
}
?>

<style>
    /* ESTILOS INTERNOS DEL MÓDULO (Alta Densidad) */
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

    #publicacionBonTable { margin-bottom: 0 !important; width: 100% !important; }
    #publicacionBonTable thead th { background-color: #f8fafc; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; padding: 6px 8px; text-transform: uppercase; }
    #publicacionBonTable tbody td { vertical-align: middle; font-size: 0.78rem; color: #334155; border-bottom: 1px solid #f1f5f9; padding: 3px 8px !important; line-height: 1.15; }
    
    .text-truncate-custom { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: middle; }
    .btn-action { border-radius: 4px; padding: 2px 6px !important; font-size: 0.75rem; margin: 0 1px; }
    
    .quick-audit-section { background: #f8fafc; border-radius: 12px; padding: 1rem 1.5rem; margin-top: 2rem; border: 1px dashed #cbd5e1; }
    .quick-audit-title { font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
    .lotes-carousel { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 6px; scrollbar-width: thin; }
    .lotes-carousel::-webkit-scrollbar { height: 4px; }
    .lotes-carousel::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .lote-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px; font-size: 0.8rem; font-weight: 600; color: #334155; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: all 0.2s ease; }
    .lote-card:hover { border-color: #10b981; background-color: #ecfdf5; color: #047857; }

    /* MODALES MÓDULO */
    .modal-pub-bon { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(6px); padding-top: 6vh; }
    .modal-content-pub-bon { background-color: #ffffff; margin: auto; padding: 2rem; border: 1px solid rgba(255,255,255,0.2); width: 90%; max-width: 600px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes modalFadeIn { from { opacity: 0; transform: translateY(-30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .close-modal { color: #94a3b8; float: right; font-size: 24px; font-weight: bold; line-height: 1; transition: color 0.2s; cursor: pointer; background: #f1f5f9; width: 32px; height: 32px; display: flex; justify-content: center; align-items: center; border-radius: 50%; }
    .close-modal:hover { color: #0f172a; background: #e2e8f0; }
    .modal-content-pub-bon .form-control, .modal-content-pub-bon .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; font-size: 0.9rem; }
    .modal-content-pub-bon .form-control:focus, .modal-content-pub-bon .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
</style>

<div class="module-wrapper">
    
    <div class="page-header-inner d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title-inner">Publicaciones Impresas</h1>
            <p class="page-subtitle-inner"><i class="fas fa-gift me-1"></i> Módulo de Bonificación Académica</p>
        </div>
        
        <div class="d-flex gap-2 flex-wrap">
            <button id="openModalpb" class="btn-modern btn-m-xls">
                <i class="fas fa-file-excel"></i> Exportar XLS
            </button>
            <button id="openModalCuadrospb" class="btn-modern btn-m-cuadros">
                <i class="fas fa-table"></i> Generar Cuadros
            </button>
            <button id="openModalResolucionespb" class="btn-modern btn-m-res">
                <i class="fas fa-file-signature"></i> Resoluciones
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="publicacionBonTable" class="table table-hover align-middle" data-order='[[ 0, "desc" ]]' style="width:100%">
            <thead>
                <tr> 
                    <th class="text-center" style="width: 4%">ID</th>
                    <th style="width: 8%">IDENTIF.</th>
                    <th style="width: 10%">DEPTO.</th>
                    <th style="width: 12%">PROFESORES</th>
                    <th style="width: 15%">REVISTA</th>
                    <th style="width: 15%">PRODUCTO</th>
                    <th style="width: 7%">EVAL 1</th>
                    <th style="width: 7%">EVAL 2</th>
                    <th class="text-center" style="width: 5%">PTS</th>
                    <th style="width: 7%">ESTADO</th>
                    <th class="text-center" style="width: 6%">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        
                        $facultad_raw = $row['FACULTAD'] ?? '';
                        $facultad = str_replace("Facultad de ", "", $facultad_raw);
                        $departamento = $row['DEPARTAMENTO'] ?? 'SIN ASIGNAR';
                        $nombres = !empty($row['DETALLES_PROFESORES']) ? $row['DETALLES_PROFESORES'] : 'Sin Profesores';
                        $revista = $row['nombre_revista'] ?? 'N/A';
                        $producto = $row['producto'] ?? 'N/A';
                        
                        // ESTADOS
                        $estadoOriginal = strtolower(trim($row['estado'] ?? ''));
                        
                        if ($estadoOriginal === '' || strpos($estadoOriginal, 'ac') !== false || strpos($estadoOriginal, 'aprobado') !== false) {
                            $dotColor = 'bg-success'; 
                            $estadoTexto = 'ACTIVO';
                            $htmlEstado = '<span class="status-pill"><span class="status-dot ' . $dotColor . '"></span>' . $estadoTexto . '</span>';
                        }
                        elseif (strpos($estadoOriginal, 'an') !== false || strpos($estadoOriginal, 'anulado') !== false) {
                            $estadoTexto = 'ANULADO';
                            $htmlEstado = '<span class="status-pill status-anulado"><span class="status-dot bg-secondary"></span>' . $estadoTexto . '</span>';
                        }
                        elseif (strpos($estadoOriginal, 're') !== false || strpos($estadoOriginal, 'rechazado') !== false) {
                            $dotColor = 'bg-danger';
                            $estadoTexto = strtoupper($estadoOriginal);
                            $htmlEstado = '<span class="status-pill"><span class="status-dot ' . $dotColor . '"></span>' . $estadoTexto . '</span>';
                        }
                        elseif (strpos($estadoOriginal, 'pe') !== false || strpos($estadoOriginal, 'pendiente') !== false) {
                            $dotColor = 'bg-warning';
                            $estadoTexto = strtoupper($estadoOriginal);
                            $htmlEstado = '<span class="status-pill"><span class="status-dot ' . $dotColor . '"></span>' . $estadoTexto . '</span>';
                        } else {
                            $htmlEstado = '<span class="status-pill"><span class="status-dot bg-secondary"></span>' . strtoupper($estadoOriginal) . '</span>';
                        }

                        echo '<tr>';
                        echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['id']) . '</td>';
                        echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['identificador_completo']) . '</span></td>';
                        echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 100px;" title="Facultad: ' . htmlspecialchars($facultad) . '">' . htmlspecialchars($departamento) . '</div></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 130px;" title="' . htmlspecialchars($nombres) . '">' . htmlspecialchars(substr($nombres, 0, 30)) . (strlen($nombres) > 30 ? '...' : '') . '</div></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 150px;" title="' . htmlspecialchars($revista) . '">' . htmlspecialchars($revista) . '</div></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 150px;" title="' . htmlspecialchars($producto) . '">' . htmlspecialchars($producto) . '</div></td>';
                        
                        echo '<td>' . htmlspecialchars($row['evaluacion1'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['evaluacion2'] ?? '') . '</td>';
                        
                        echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntaje_final']) . '</td>';
                        echo '<td>' . $htmlEstado . '</td>';
                        
                        echo '<td class="text-center text-nowrap">';
                        echo '<a href="editar_publicacion_bon.php?id=' . $row['id'] . '" class="btn btn-light border btn-action text-primary shadow-sm" title="Editar"><i class="fas fa-pen"></i></a> ';
                        echo '<button class="btn btn-light border btn-action text-danger shadow-sm" onclick="confirmDeletePubBon(' . $row['id'] . ')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="quick-audit-section">
        <div class="quick-audit-title">
            <i class="fas fa-folder-tree text-secondary me-1"></i> Auditoría Rápida de Lotes (Bonificación - Publicaciones)
        </div>
        <div class="lotes-carousel">
            <?php foreach($ultimos_lotes as $lote): ?>
                <a href="auditoria_lote_bon.php?lote=<?php echo urlencode($lote); ?>" class="lote-card" title="Ver lote: <?php echo htmlspecialchars($lote); ?>">
                    <i class="fas fa-file-lines text-success"></i> <?php echo htmlspecialchars($lote); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<div id="modalpb" class="modal-pub-bon">
    <div class="modal-content-pub-bon">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS (Bonificación)</h4>
             <span class="close-modal" onclick="$(this).closest('.modal-pub-bon').fadeOut(200);">&times;</span>
        </div>
        <form action="report_publicaciones_bon.php" method="GET">
            <div class="mb-3">
                <label for="ano_xls_pb" class="form-label text-secondary fw-semibold">Año:</label>
                <select name="ano" id="ano_xls_pb" class="form-select">
                    <option value="">Todos los años...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="identificador_solicitud_pb" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                <select name="identificador_solicitud" id="identificador_solicitud_pb" class="form-select">
                    <option value="">Todos los paquetes...</option>
                    <?php
                    foreach ($identificadores as $id) {
                        echo '<option value="' . htmlspecialchars($id) . '" data-ano="' . substr($id, 0, 4) . '">' . htmlspecialchars($id) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg shadow-sm rounded-3"><i class="fas fa-download me-2"></i>Generar Reporte</button>
            </div>
        </form>
    </div>
</div>

<div id="modalCuadrospb" class="modal-pub-bon">
    <div class="modal-content-pub-bon">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros (Bonificación)</h4>
             <span class="close-modal" onclick="$(this).closest('.modal-pub-bon').fadeOut(200);">&times;</span>
        </div>
        <form action="cuadros_publicacion_bon.php" method="GET">
            <div class="mb-3">
                <label for="ano_cuadros_pb" class="form-label text-secondary fw-semibold">Año:</label>
                <select name="cuadro_ano" id="ano_cuadros_pb" class="form-select">
                    <option value="">Todos los años...</option>
                    <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="cuadro_identificador_solicitud_pb" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud_pb" class="form-select">
                    <option value="">Todos los paquetes...</option>
                    <?php
                    foreach ($identificadores as $id) {
                        echo '<option value="' . htmlspecialchars($id) . '" data-ano="' . substr($id, 0, 4) . '">' . htmlspecialchars($id) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg shadow-sm rounded-3"><i class="fas fa-file-alt me-2"></i>Generar Cuadro</button>
            </div>
        </form>
    </div>
</div>

<div id="modalResolucionespb" class="modal-pub-bon">
    <div class="modal-content-pub-bon" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-info m-0" style="color: #0284c7 !important;"><i class="fas fa-file-word me-2"></i>Resoluciones Bonificación</h4>
             <span class="close-modal" onclick="$(this).closest('.modal-pub-bon').fadeOut(200);">&times;</span>
        </div>
        <form action="resoluciones_publicacion_bon.php" method="GET">
            <div class="row bg-light p-3 mb-4 border rounded-3 mx-0">
                <div class="col-md-6 mb-2">
                    <label for="ano_res_pb" class="form-label text-secondary fw-semibold">Filtro por Año:</label>
                    <select id="ano_res_pb" class="form-select">
                        <option value="">Seleccione un año...</option>
                        <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="cuadro_identificador_pub_bon" class="form-label text-secondary fw-semibold">Identificador (Paquete):</label>
                    <select name="cuadro_identificador_pub_bon" id="cuadro_identificador_pub_bon" class="form-select" required>
                        <option value="">Selecciona un paquete</option>
                        <?php
                        foreach ($identificadores as $id) {
                            echo '<option value="' . htmlspecialchars($id) . '" data-ano="' . substr($id, 0, 4) . '">' . htmlspecialchars($id) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <h6 class="mb-3 text-secondary border-bottom pb-2 fw-bold">Datos de la Resolución (Opcionales)</h6>
            <div class="row px-2">
                <div class="col-md-6 mb-3">
                    <label for="num_resolucion" class="form-label text-muted">Número de resolución:</label>
                    <input type="text" name="num_resolucion" class="form-control" placeholder="Ej: 045">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_resolucion" class="form-label text-muted">Fecha de la resolución:</label>
                    <input type="date" name="fecha_resolucion" class="form-control">
                </div>
            </div>
            
            <div class="row px-2">
                <div class="col-md-8 mb-3">
                    <label for="nombre_vicerrector" class="form-label text-muted">Firma (Vicerrector/a):</label>
                    <input type="text" name="nombre_vicerrector" class="form-control" value="AIDA PATRICIA GONZÁLEZ NIEVA" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="genero_vicerrector" class="form-label text-muted">Género:</label>
                    <select name="genero_vicerrector" class="form-select" required>
                        <option value="F">Femenino</option>
                        <option value="M">Masculino</option>
                    </select>
                </div>
            </div>

            <div class="row px-2">
                <div class="col-md-6 mb-4">
                    <label for="nombre_reviso" class="form-label text-muted">Revisó:</label>
                    <input type="text" name="nombre_reviso" class="form-control" value="Marjhory Castro" required>
                </div>
                <div class="col-md-6 mb-4">
                    <label for="nombre_elaboro" class="form-label text-muted">Elaboró:</label>
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
    // IIFE protector para esperar a jQuery
    (function() {
        function initPublicacionBonModule() {
            
            // Función de filtro global
            window.applyYearFilterPubBon = function(yearSelectId, idSelectId) {
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

            // Anulación
            window.confirmDeletePubBon = function(id) {
                if (confirm("¿Estás seguro de que quieres eliminar esta publicación?")) {
                    const motivo = prompt("Por favor, indique el motivo de la anulación:");
                    if (motivo && motivo.trim() !== "") {
                        window.location.href = 'eliminar_publicacion_bon.php?id=' + id + '&motivo=' + encodeURIComponent(motivo);
                    } else {
                        alert("El motivo de la anulación es obligatorio para continuar.");
                    }
                }
            };

            $(document).ready(function() {
                // Control de Modales
                $('#openModalpb').on('click', function() { $('#modalpb').fadeIn(200); });
                $('#openModalCuadrospb').on('click', function() { $('#modalCuadrospb').fadeIn(200); });
                $('#openModalResolucionespb').on('click', function() { $('#modalResolucionespb').fadeIn(200); }); 
                
                // Cerrar haciendo clic fuera
                $(window).on('click', function(event) {
                    if ($(event.target).hasClass("modal-pub-bon")) {
                        $(event.target).fadeOut(200);
                    }
                });

                // Inicializar DataTables
                if (!$.fn.DataTable.isDataTable('#publicacionBonTable')) {
                    $('#publicacionBonTable').DataTable({
                        responsive: true,
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                        language: { url: '//cdn.datatables.net/plug-ins/2.0.0/i18n/es-ES.json' }
                    });
                }

                // Eventos de filtro
                $('#ano_xls_pb').on('change', function() { applyYearFilterPubBon('ano_xls_pb', 'identificador_solicitud_pb'); });
                $('#ano_cuadros_pb').on('change', function() { applyYearFilterPubBon('ano_cuadros_pb', 'cuadro_identificador_solicitud_pb'); });
                $('#ano_res_pb').on('change', function() { applyYearFilterPubBon('ano_res_pb', 'cuadro_identificador_pub_bon'); });
            });
        }

        // Wait for jQuery
        if (window.jQuery) {
            initPublicacionBonModule();
        } else {
            var checkInterval = setInterval(function() {
                if (window.jQuery) {
                    clearInterval(checkInterval);
                    initPublicacionBonModule();
                }
            }, 10);
        }
    })();
</script>