<?php
// Requerir la conexión a la base de datos
include_once  'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL a prueba de fallos usando LEFT JOIN y MAX()
$sql = "
SELECT 
    t.id_innovacion AS id,
    MAX(f.nombre_fac_min) AS `FACULTAD`,
    MAX(d.depto_nom_propio) AS `DEPARTAMENTO`,
    t.numero_oficio, 
    t.identificador,
    t.fecha_solicitud,
    t.producto,
    t.impacto,
    t.puntaje AS puntaje_final,
    t.estado,
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
        ORDER BY ter.documento_tercero
        SEPARATOR '\n'
    ) AS `DETALLES_PROFESORES`
FROM 
    innovacion t
LEFT JOIN 
    innovacion_profesor tp ON tp.id_innovacion = t.id_innovacion
LEFT JOIN 
    tercero ter ON tp.id_profesor = ter.documento_tercero
LEFT JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
LEFT JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID de la innovación y ordenar descendente
$sql .= " GROUP BY 
    t.id_innovacion, t.identificador, t.numero_oficio, t.fecha_solicitud, t.producto, t.impacto, t.puntaje, t.estado
ORDER BY t.id_innovacion DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

// Realizar la consulta para obtener los identificadores de solicitud y extraer años
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM innovacion WHERE identificador IS NOT NULL ORDER BY identificador DESC"); 

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
    rsort($unique_years); // Ordenar años de mayor a menor
}

// --- OBTENER LOS ÚLTIMOS 6 LOTES PARA EL CARRUSEL ---
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador FROM innovacion WHERE identificador IS NOT NULL AND identificador != '' ORDER BY identificador DESC LIMIT 6");
$ultimos_lotes = [];
if ($ultimos_lotes_result) {
    while ($row = $ultimos_lotes_result->fetch_assoc()) {
        $ultimos_lotes[] = $row['identificador'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Innovaciones</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    
    <style>
        /* 1. AJUSTES DE INTEGRACIÓN */
        .module-wrapper {
            background-color: transparent;
            padding: 0;
            color: #334155;
        }

        /* 2. ENCABEZADO INTERNO */
        .page-header-inner {
            margin-bottom: 1.2rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 0.8rem;
        }
        .page-title-inner {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .page-subtitle-inner {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 2px;
        }

        /* 3. BOTONES MODERNIZADOS */
        .btn-modern {
            font-weight: 600;
            border-radius: 8px;
            padding: 0.4rem 0.9rem;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .btn-m-xls { background: #f0fdf4; color: #059669; border-color: #bbf7d0; }
        .btn-m-xls:hover { background: #059669; color: white; }
        
        .btn-m-cuadros { background: #eef2ff; color: #4f46e5; border-color: #c7d2fe; }
        .btn-m-cuadros:hover { background: #4f46e5; color: white; }
        
        .btn-m-res { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .btn-m-res:hover { background: #2563eb; color: white; }

        /* 4. ESTADOS MINIMALISTAS Y COMPRIMIDOS */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            white-space: nowrap;
            letter-spacing: 0.2px;
        }
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        .status-anulado {
            background-color: transparent;
            border-color: transparent;
            color: #94a3b8;
            text-decoration: line-through;
            padding-left: 0;
        }

        /* 5. TABLA ULTRA-COMPRIMIDA (High Density View) */
        #innovacion { margin-bottom: 0 !important; }
        #innovacion thead th {
            background-color: #f8fafc;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.7rem; 
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 6px 8px; 
            text-transform: uppercase;
        }
        #innovacion tbody td {
            vertical-align: middle;
            font-size: 0.78rem; 
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            padding: 3px 8px !important; 
            line-height: 1.15; 
        }
        .text-truncate-custom {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }
        
        #innovacion .badge {
            font-size: 0.68rem;
            padding: 2px 6px !important;
            font-weight: 600;
            border-radius: 4px;
        }
        .btn-action {
            border-radius: 4px;
            padding: 2px 6px !important; 
            font-size: 0.75rem; 
            margin: 0 1px;
        }
        
        /* 6. CARRUSEL DE LOTES (Abajo) */
        .quick-audit-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-top: 2rem;
            border: 1px dashed #cbd5e1;
        }
        .quick-audit-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .lotes-carousel {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 6px; 
            scrollbar-width: thin;
        }
        .lotes-carousel::-webkit-scrollbar { height: 4px; }
        .lotes-carousel::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .lote-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .lote-card:hover {
            border-color: #3b82f6;
            background-color: #eff6ff; 
            color: #1d4ed8;
        }

        /* 7. MODALES MEJORADOS (Efecto Glass) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(15, 23, 42, 0.4); 
            backdrop-filter: blur(6px);
            padding-top: 6vh;
        }
        .modal-content {
            background-color: #ffffff;
            margin: auto;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
            width: 90%; 
            max-width: 600px;
            border-radius: 20px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .close {
            color: #94a3b8;
            float: right;
            font-size: 24px;
            font-weight: bold;
            line-height: 1;
            transition: color 0.2s;
            cursor: pointer;
            background: #f1f5f9;
            width: 32px; height: 32px;
            display: flex; justify-content: center; align-items: center;
            border-radius: 50%;
        }
        .close:hover { color: #0f172a; background: #e2e8f0; }
        
        .modal-body .form-control, .modal-body .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }
        .modal-body .form-control:focus, .modal-body .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body>

<div class="module-wrapper">
    
    <div class="page-header-inner d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="page-title-inner">Registro de Innovación</h1>
            <p class="page-subtitle-inner">Listado maestro de productos de innovación tecnológica y académica</p>
        </div>
        
        <div class="d-flex gap-2 flex-wrap">
            <button id="openModalin" class="btn-modern btn-m-xls">
                <i class="fas fa-file-excel"></i> Exportar XLS
            </button>
            <button id="openModalCuadrosin" class="btn-modern btn-m-cuadros">
                <i class="fas fa-table"></i> Generar Cuadros
            </button>
            <button id="openModalResolucionesin" class="btn-modern btn-m-res">
                <i class="fas fa-file-signature"></i> Resoluciones
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="innovacion" class="table table-hover align-middle" data-order='[[ 0, "desc" ]]' style="width:100%">
            <thead>
                <tr> 
                    <th class="text-center" style="width: 5%">ID</th>
                    <th style="width: 10%">IDENTIF.</th>
                    <th style="width: 15%">DEPARTAMENTO</th>
                    <th style="width: 8%">OFICIO</th>
                    <th style="width: 15%">PROFESORES</th>
                    <th style="width: 20%">PRODUCTO</th>
                    <th class="text-center" style="width: 5%">PTS</th>                
                    <th style="width: 8%">ESTADO</th>
                    <th class="text-center" style="width: 6%">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    
                    // Manejo seguro de nulos por el LEFT JOIN
                    $facultad_raw = $row['FACULTAD'] ?? '';
                    $facultad = str_replace("Facultad de ", "", $facultad_raw);
                    $departamento = $row['DEPARTAMENTO'] ?? 'SIN ASIGNAR';
                    $nombres = !empty($row['DETALLES_PROFESORES']) ? $row['DETALLES_PROFESORES'] : 'Sin Profesores';
                    $producto = $row['producto'] ?? 'N/A';
                    
                    // LÓGICA DE ESTADOS MINIMALISTAS
                    $estadoOriginal = strtolower(trim($row['estado'] ?? ''));
                    $estadoTexto = strtoupper($estadoOriginal);
                    
                    if ($estadoTexto === '') $estadoTexto = 'SIN ESTADO';

                    if ($estadoOriginal === 'an' || strpos($estadoOriginal, 'anulado') !== false) {
                        $estadoTexto = 'ANULADO';
                        $htmlEstado = '<span class="status-pill status-anulado"><span class="status-dot bg-secondary"></span>' . $estadoTexto . '</span>';
                    } else {
                        $dotColor = 'bg-secondary'; 
                        if ($estadoOriginal === 'ac' || strpos($estadoOriginal, 'aprobado') !== false) {
                            $dotColor = 'bg-success'; 
                            $estadoTexto = 'ACTIVO';
                        }
                        elseif (strpos($estadoOriginal, 're') !== false || strpos($estadoOriginal, 'rechazado') !== false) $dotColor = 'bg-danger';
                        elseif (strpos($estadoOriginal, 'pe') !== false || strpos($estadoOriginal, 'pendiente') !== false) $dotColor = 'bg-warning';
                        
                        $htmlEstado = '<span class="status-pill"><span class="status-dot ' . $dotColor . '"></span>' . $estadoTexto . '</span>';
                    }

                    echo '<tr>';
                    echo '<td class="text-center fw-bold text-primary">' . htmlspecialchars($row['id']) . '</td>';
                    echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['identificador']) . '</span></td>';
                    
                    echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 140px;" title="Facultad: ' . htmlspecialchars($facultad) . '">' . htmlspecialchars($departamento) . '</div></td>';
                    
                    echo '<td><small class="text-secondary">' . htmlspecialchars($row['numero_oficio']) . '</small></td>';
                    
                    echo '<td><div class="text-truncate-custom" style="max-width: 160px;" title="' . htmlspecialchars($nombres) . '">' . htmlspecialchars(substr($nombres, 0, 30)) . (strlen($nombres) > 30 ? '...' : '') . '</div></td>';
                    
                    echo '<td><div class="text-truncate-custom" style="max-width: 200px;" title="' . htmlspecialchars($producto) . '">' . htmlspecialchars($producto) . '</div></td>';
                    
                    echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntaje_final']) . '</td>';
                    
                    echo '<td>' . $htmlEstado . '</td>';
                    
                    echo '<td class="text-center text-nowrap">';
                    echo '<a href="editar_innovacion.php?id=' . $row['id'] . '" class="btn btn-light border btn-action text-primary shadow-sm" title="Editar"><i class="fas fa-pen"></i></a> ';
                    echo '<button class="btn btn-light border btn-action text-danger shadow-sm" onclick="confirmDeleteWithReason(' . $row['id'] . ')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="quick-audit-section">
        <div class="quick-audit-title">
            <i class="fas fa-folder-tree text-secondary me-1"></i> Auditoría Rápida de Lotes (Innovación)
        </div>
        <div class="lotes-carousel">
            <?php foreach($ultimos_lotes as $lote): ?>
                <a href="auditoria_lote.php?lote=<?php echo urlencode($lote); ?>" class="lote-card" title="Ver lote: <?php echo htmlspecialchars($lote); ?>">
                    <i class="fas fa-microchip text-primary"></i> <?php echo htmlspecialchars($lote); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<div id="modalin" class="modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS Innovación</h4>
             <span class="close close-xls">&times;</span>
        </div>
        <div class="modal-body p-0">
            <form action="report_innovacion.php" method="GET">
                <div class="mb-3">
                    <label for="ano_xls_in" class="form-label text-secondary fw-semibold">Año:</label>
                    <select name="ano" id="ano_xls_in" class="form-select">
                        <option value="">Todos los años...</option>
                        <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="identificador_solicitud_in" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                    <select name="identificador_solicitud" id="identificador_solicitud_in" class="form-select">
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
</div>

<div id="modalCuadrosin" class="modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros</h4>
             <span class="close close-cuadros">&times;</span>
        </div>
        <div class="modal-body p-0">
            <form action="cuadros_innovacion.php" method="GET">
                <div class="mb-3">
                    <label for="ano_cuadros_in" class="form-label text-secondary fw-semibold">Año:</label>
                    <select name="cuadro_ano" id="ano_cuadros_in" class="form-select">
                        <option value="">Todos los años...</option>
                        <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="cuadro_identificador_solicitud_in" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                    <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud_in" class="form-select">
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
</div>

<div id="modalResolucionesin" class="modal">
    <div class="modal-content" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-info m-0" style="color: #0284c7 !important;"><i class="fas fa-file-word me-2"></i>Resoluciones Innovación</h4>
             <span class="close close-resoluciones-in">&times;</span>
        </div>
        <div class="modal-body p-0">
            <form action="resoluciones_innovacion.php" method="GET">
                
                <div class="row bg-light p-3 mb-4 border rounded-3 mx-0">
                    <div class="col-md-6 mb-2">
                        <label for="ano_res_in" class="form-label text-secondary fw-semibold">Filtro por Año:</label>
                        <select id="ano_res_in" class="form-select">
                            <option value="">Seleccione un año...</option>
                            <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="cuadro_identificador_innovacion" class="form-label text-secondary fw-semibold">Identificador (Paquete):</label>
                        <select name="cuadro_identificador_innovacion" id="cuadro_identificador_innovacion" class="form-select" required>
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
                        <label for="num_resolucion" class="form-label text-muted">Número de resolución (Inicial):</label>
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
    // Función de anulación con prompt
    function confirmDeleteWithReason(id) {
        if (confirm("¿Estás seguro de que quieres eliminar esta innovación?")) {
            const motivo = prompt("Por favor, indique el motivo de la anulación:");
            if (motivo && motivo.trim() !== "") {
                window.location.href = 'eliminar_innovacion.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio para continuar.");
            }
        }
    }

    // --- Función para filtrar los paquetes según el año seleccionado ---
    function applyYearFilter(yearSelectId, idSelectId) {
        const year = $('#' + yearSelectId).val();
        $('#' + idSelectId + ' option').each(function() {
            if ($(this).val() === "") return; 
            
            if (year === "" || $(this).data('ano').toString() === year) {
                $(this).show().prop('disabled', false);
            } else {
                $(this).hide().prop('disabled', true);
            }
        });
        $('#' + idSelectId).val(''); 
    }

    $(document).ready(function() {
        
        // Inicializar DataTable
        $('#innovacion').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });

        // Control de los modales
        $('#openModalin').on('click', function() { $('#modalin').fadeIn(200); });
        $('#openModalCuadrosin').on('click', function() { $('#modalCuadrosin').fadeIn(200); });
        $('#openModalResolucionesin').on('click', function() { $('#modalResolucionesin').fadeIn(200); }); 
        
        $('.close-xls').on('click', function() { $('#modalin').fadeOut(200); });
        $('.close-cuadros').on('click', function() { $('#modalCuadrosin').fadeOut(200); });
        $('.close-resoluciones-in').on('click', function() { $('#modalResolucionesin').fadeOut(200); });
        
        // Cerrar si se hace clic fuera de la caja del modal
        $(window).click(function(event) {
            if ($(event.target).is(".modal")) {
                $(".modal").fadeOut(200);
            }
        });

        // Escuchar los cambios en los selectores de año
        $('#ano_xls_in').on('change', function() { applyYearFilter('ano_xls_in', 'identificador_solicitud_in'); });
        $('#ano_cuadros_in').on('change', function() { applyYearFilter('ano_cuadros_in', 'cuadro_identificador_solicitud_in'); });
        $('#ano_res_in').on('change', function() { applyYearFilter('ano_res_in', 'cuadro_identificador_innovacion'); });
    });
</script>

</body>
</html>