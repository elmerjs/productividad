<?php
// Requerir la conexión a la base de datos
include_once('conn.php');

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL a prueba de fallos usando LEFT JOIN y MAX()
$sql = "
SELECT 
    t.id_traduccion,
    MAX(f.nombre_fac_min) AS `FACULTAD`,
    MAX(d.depto_nom_propio) AS `DEPARTAMENTO`,
    t.numero_oficio,
    t.identificador,
    t.fecha_solicitud,
    t.producto,
    t.puntaje,
    t.estado,
    t.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero) ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    traduccion_libros t
LEFT JOIN 
    traduccion_profesor tp ON tp.id_traduccion = t.id_traduccion
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

// Agrupar los resultados por el ID y ordenar del más reciente al más antiguo
$sql .= " GROUP BY 
    t.id_traduccion, t.numero_oficio, t.identificador, t.fecha_solicitud, t.producto, t.puntaje, t.estado, t.tipo_productividad
ORDER BY t.id_traduccion DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

// Realizar la consulta para obtener los identificadores de solicitud y extraer los años
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM traduccion_libros WHERE identificador IS NOT NULL ORDER BY identificador DESC"); 

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
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador FROM traduccion_libros WHERE identificador IS NOT NULL AND identificador != '' ORDER BY identificador DESC LIMIT 6");
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
    <title>Listado de Traducción de Libros</title>

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
        #traduccion {
            margin-bottom: 0 !important;
        }
        #traduccion thead th {
            background-color: #f8fafc;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.7rem; 
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 6px 8px; 
            text-transform: uppercase;
        }
        #traduccion tbody td {
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
        
        #traduccion .badge {
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
            <h1 class="page-title-inner">Traducción de Libros</h1>
            <p class="page-subtitle-inner">Listado maestro de obras y textos académicos traducidos</p>
        </div>
        
        <div class="d-flex gap-2 flex-wrap">
            <button id="openModaltl" class="btn-modern btn-m-xls">
                <i class="fas fa-file-excel"></i> Exportar XLS
            </button>
            <button id="openModalCuadrostl" class="btn-modern btn-m-cuadros">
                <i class="fas fa-table"></i> Generar Cuadros
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="traduccion" class="table table-hover align-middle" data-order='[[ 0, "desc" ]]' style="width:100%">
            <thead>
                <tr> 
                    <th class="text-center" style="width: 5%">ID</th>         
                    <th style="width: 10%">IDENTIFICADOR</th>
                    <th style="width: 15%">DEPARTAMENTO</th>
                    <th style="width: 10%">OFICIO</th>
                    <th style="width: 15%">PROFESORES</th>
                    <th style="width: 20%">PRODUCTO</th>
                    <th class="text-center" style="width: 5%">PTS</th>
                    <th style="width: 10%">ESTADO</th>
                    <th class="text-center" style="width: 10%">ACCIONES</th>
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

                    // Por defecto: Gris y tachado si es anulado
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
                    echo '<td class="text-center fw-bold text-primary">' . htmlspecialchars($row['id_traduccion']) . '</td>';
                    echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['identificador']) . '</span></td>';

                    echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 140px;" title="Facultad: ' . htmlspecialchars($facultad) . '">' . htmlspecialchars($departamento) . '</div></td>';
                    
                    echo '<td><small class="text-secondary">' . htmlspecialchars($row['numero_oficio']) . '</small></td>';

                    echo '<td><div class="text-truncate-custom" style="max-width: 160px;" title="' . htmlspecialchars($nombres) . '">' . htmlspecialchars(substr($nombres, 0, 30)) . (strlen($nombres) > 30 ? '...' : '') . '</div></td>';

                    echo '<td><div class="text-truncate-custom" style="max-width: 200px;" title="' . htmlspecialchars($producto) . '">' . htmlspecialchars($producto) . '</div></td>';
                    
                    echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntaje']) . '</td>';
                    
                    // Impresión del nuevo Estado
                    echo '<td>' . $htmlEstado . '</td>';

                    echo '<td class="text-center text-nowrap">';
                    echo '<a href="editar_traduccion.php?id=' . $row['id_traduccion'] . '" class="btn btn-light border btn-action text-primary shadow-sm" title="Editar"><i class="fas fa-pen"></i></a> ';
                    echo '<button class="delete-btn btn btn-light border btn-action text-danger shadow-sm" onclick="confirmDeleteWithReason(' . $row['id_traduccion'] . ')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="quick-audit-section">
        <div class="quick-audit-title">
            <i class="fas fa-folder-tree text-secondary me-1"></i> Auditoría Rápida de Lotes (Traducciones)
        </div>
        <div class="lotes-carousel">
            <?php foreach($ultimos_lotes as $lote): ?>
                <a href="auditoria_lote.php?lote=<?php echo urlencode($lote); ?>" class="lote-card" title="Ver lote: <?php echo htmlspecialchars($lote); ?>">
                    <i class="fas fa-language text-info"></i> <?php echo htmlspecialchars($lote); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<div id="modaltl" class="modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS Traducciones</h4>
             <span class="close close-xls">&times;</span>
        </div>
        <div class="modal-body p-0">
            <form action="report_traduccion.php" method="GET">
                <div class="mb-3">
                    <label for="ano_xls" class="form-label text-secondary fw-semibold">Año:</label>
                    <select name="ano" id="ano_xls" class="form-select">
                        <option value="">Todos los años...</option>
                        <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="identificador_solicitud_tl" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                    <select name="identificador_solicitud" id="identificador_solicitud_tl" class="form-select">
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

<div id="modalCuadrostl" class="modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros</h4>
             <span class="close close-cuadros">&times;</span>
        </div>
        <div class="modal-body p-0">
            <form action="cuadros_traduccion_lib.php" method="GET">
                <div class="mb-3">
                    <label for="ano_cuadros" class="form-label text-secondary fw-semibold">Año:</label>
                    <select name="cuadro_ano" id="ano_cuadros" class="form-select">
                        <option value="">Todos los años...</option>
                        <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="cuadro_identificador_solicitud_tl" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                    <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud_tl" class="form-select">
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

<script>
    function confirmDeleteWithReason(id) {
        const confirmation = confirm("¿Estás seguro de que quieres eliminar esta traducción?");
        if (confirmation) {
            const motivo = prompt("Por favor, indique el motivo de la anulación:");
            if (motivo && motivo.trim() !== "") {
                // NOTA: Se corrigió el destino de redirección para que apunte a traducciones y no a patentes
                window.location.href = 'eliminar_traduccion.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
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
        $('#traduccion').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });

        // Abrir los modales
        $("#openModaltl").click(function() { $("#modaltl").fadeIn(200); });
        $("#openModalCuadrostl").click(function() { $("#modalCuadrostl").fadeIn(200); });

        // Escuchar los cambios en los selectores de año para actualizar la lista de identificadores
        $('#ano_xls').on('change', function() { applyYearFilter('ano_xls', 'identificador_solicitud_tl'); });
        $('#ano_cuadros').on('change', function() { applyYearFilter('ano_cuadros', 'cuadro_identificador_solicitud_tl'); });

        // Cerrar los modales
        $(".close").click(function() { $(this).closest('.modal').fadeOut(200); });

        // Cerrar los modales si se hace clic fuera del contenido
        $(window).click(function(event) {
            if ($(event.target).is(".modal")) {
                $(".modal").fadeOut(200);
            }
        });
    });
</script>

</body>
</html>