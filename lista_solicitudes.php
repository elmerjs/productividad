<?php
// Requerir la conexión a la base de datos
include_once('conn.php');

// Obtener los filtros desde el formulario (si existen)
$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
     GROUP_CONCAT(
            DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero)
            ORDER BY t.documento_tercero
            SEPARATOR '\n'
        ) AS DETALLES_PROFESORES,s.id_solicitud_articulo,
    s.titulo_articulo AS `NOMBRE DEL PRODUCTO`,
    s.tipo_articulo AS `TIPO DE ARTICULO`,
    s.tipo_revista AS `TIPO REVISTA`,
    s.nombre_revista AS `NOMBRE REVISTA`,
    s.issn AS `ISSN`, s.estado_solicitud as estado_solicitud,
    s.identificador_solicitud, s.numero_oficio, s.id_solicitud_articulo as ID_S, s.puntaje
FROM 
    solicitud s
JOIN 
    solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
JOIN 
    tercero t ON sp.fk_id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
LEFT JOIN 
    articulo a ON s.fk_id_articulo = a.id_articulo
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND s.identificador_solicitud = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(s.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados y ORDENAR del más reciente al más antiguo
$sql .= " GROUP BY s.id_solicitud_articulo ORDER BY s.id_solicitud_articulo DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores de solicitud
$identificadores_result = $conn->query("SELECT DISTINCT identificador_solicitud FROM solicitud"); 
$identificadores = [];
while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
}

// Obtener solo los 6 LOTES MÁS RECIENTES para las tarjetas de acceso rápido
$ultimos_lotes_result = $conn->query("SELECT DISTINCT identificador_solicitud FROM solicitud WHERE identificador_solicitud IS NOT NULL AND identificador_solicitud != '' ORDER BY identificador_solicitud DESC LIMIT 6");
$ultimos_lotes = [];
if ($ultimos_lotes_result) {
    while ($row = $ultimos_lotes_result->fetch_assoc()) {
        $ultimos_lotes[] = $row['identificador_solicitud'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Solicitudes Revistas</title>

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
        #revistas {
            margin-bottom: 0 !important;
        }
        #revistas thead th {
            background-color: #f8fafc;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.7rem; /* Reducido */
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 6px 8px; /* Padding recortado a la mitad */
            text-transform: uppercase;
        }
        #revistas tbody td {
            vertical-align: middle;
            font-size: 0.78rem; /* Fuente más técnica y pequeña */
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            padding: 3px 8px !important; /* Altura de fila al mínimo viable */
            line-height: 1.15; 
        }
        .text-truncate-custom {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }
        
        /* Ajustes a los elementos internos de la tabla para que no la ensanchen */
        #revistas .badge {
            font-size: 0.68rem;
            padding: 2px 6px !important;
            font-weight: 600;
            border-radius: 4px;
        }
        #revistas small {
            font-size: 0.72rem;
        }
        .btn-action {
            border-radius: 4px;
            padding: 2px 6px !important; /* Botón enano */
            font-size: 0.75rem; 
            margin: 0 1px;
        }
        
        /* 6. CARRUSEL DE LOTES (Movido abajo) */
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
                <h1 class="page-title-inner">Artículos Especializados</h1>
                <p class="page-subtitle-inner">Listado maestro de revistas indexadas (Base Salarial)</p>
            </div>
            
            <div class="d-flex gap-2 flex-wrap">
                <button id="openModal" class="btn-modern btn-m-xls">
                    <i class="fas fa-file-excel"></i> Exportar XLS
                </button>
                <button id="openModalCuadros" class="btn-modern btn-m-cuadros">
                    <i class="fas fa-table"></i> Generar Cuadros
                </button>
                <button id="openModalResoluciones" class="btn-modern btn-m-res">
                    <i class="fas fa-file-signature"></i> Resoluciones
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="revistas" class="table table-hover align-middle" data-order='[[ 0, "desc" ]]' style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%">ID</th>
                        <th style="width: 10%">IDENTIFICADOR</th>
                        <th style="width: 15%">DEPARTAMENTO</th>                    
                        <th style="width: 8%">OFICIO</th>
                        <th style="width: 15%">PROFESOR(ES)</th>
                        <th style="width: 15%">PRODUCTO</th>
                        <th style="width: 8%">TIPO ART.</th>
                        <th style="width: 8%">REVISTA</th>
                        <th style="width: 5%">ISSN</th>
                        <th class="text-center" style="width: 5%">PTS</th>
                        <th style="width: 10%">ESTADO</th> 
                        <th class="text-center" style="width: 8%">ACCIONES</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        $facultad = str_replace("Facultad de ", "", $row['FACULTAD']);
                        $nombreProducto = htmlspecialchars($row['NOMBRE DEL PRODUCTO']);
                        $nombreRevista = htmlspecialchars($row['NOMBRE REVISTA']);
                        $nombres = htmlspecialchars($row['NOMBRES']);
                        $departamento = htmlspecialchars($row['DEPARTAMENTO']);
                        
                        // LÓGICA DE ESTADOS MINIMALISTAS
                        $estadoOriginal = strtolower(trim($row['estado_solicitud']));
                        $estadoTexto = strtoupper($estadoOriginal);
                        
                        if ($estadoTexto === '') $estadoTexto = 'ok';

                        // Por defecto: Gris y tachado si es anulado
                        if ($estadoOriginal === 'an' || strpos($estadoOriginal, 'anulado') !== false) {
                            $estadoTexto = 'ANULADO';
                            $htmlEstado = '<span class="status-pill status-anulado"><span class="status-dot bg-secondary"></span>' . $estadoTexto . '</span>';
                        } else {
                            $dotColor = 'bg-secondary'; // Gris por defecto
                            if (strpos($estadoOriginal, 'aprobado') !== false) $dotColor = 'bg-success'; // Verde
                            elseif (strpos($estadoOriginal, 'rechazado') !== false) $dotColor = 'bg-danger'; // Rojo
                            elseif (strpos($estadoOriginal, 'pendiente') !== false) $dotColor = 'bg-warning'; // Amarillo
                            elseif (strpos($estadoOriginal, 'revisión') !== false || strpos($estadoOriginal, 'revision') !== false) $dotColor = 'bg-primary'; // Azul
                            
                            $htmlEstado = '<span class="status-pill"><span class="status-dot ' . $dotColor . '"></span>' . $estadoTexto . '</span>';
                        }

                        echo '<tr>';
                        echo '<td class="text-center fw-bold"><a href="reporte_articulo.php?id_solicitud_articulo=' . htmlspecialchars($row['id_solicitud_articulo']) . '" class="text-decoration-none text-primary">' . htmlspecialchars($row['id_solicitud_articulo']) . '</a></td>';                    
                        echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['identificador_solicitud']) . '</span></td>';
                        echo '<td><div class="text-truncate-custom fw-medium text-dark" style="max-width: 140px;" title="Facultad: ' . htmlspecialchars($facultad) . '">' . $departamento . '</div></td>';
                        echo '<td><small class="text-secondary">' . htmlspecialchars($row['numero_oficio']) . '</small></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 160px;" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">' . $nombres . '</div></td>';                    
                        echo '<td><div class="text-truncate-custom" style="max-width: 180px;" title="' . $nombreProducto . '">' . $nombreProducto . '</div></td>';
                        echo '<td><span class="badge bg-light text-secondary border px-1">' . htmlspecialchars($row['TIPO DE ARTICULO']) . '</span></td>';
                        echo '<td><div class="text-truncate-custom" style="max-width: 140px;" title="' . $nombreRevista . ' (' . ucfirst(strtolower($row['TIPO REVISTA'])) . ')">' . $nombreRevista . '</div></td>';
                        echo '<td><small class="font-monospace text-muted">' . htmlspecialchars($row['ISSN']) . '</small></td>';
                        echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntaje']) . '</td>';
                        
                        // Impresión del nuevo Estado
                        echo '<td>' . $htmlEstado . '</td>';
                        
                        echo '<td class="text-center text-nowrap">';
                        echo '<a href="actualizar_articulo.php?id_solicitud=' . $row['ID_S'] . '" class="btn btn-light border btn-action text-primary shadow-sm" title="Editar"><i class="fas fa-pen"></i></a> ';
                        
                        
                        // Cambia el nombre de la función a confirmAnularArticulo
                        echo '<button class="btn btn-light border btn-action text-danger shadow-sm" onclick="confirmAnularArticulo(' . $row['ID_S'] . ')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="quick-audit-section">
            <div class="quick-audit-title">
                <i class="fas fa-folder-tree text-secondary me-1"></i> Auditoría Rápida de Lotes (Archivador)
            </div>
            <div class="lotes-carousel">
                <?php foreach($ultimos_lotes as $lote): ?>
                    <a href="auditoria_lote.php?lote=<?php echo urlencode($lote); ?>" class="lote-card" title="Auditar duplicados en este lote">
                        <i class="fas fa-folder-open text-primary"></i> <?php echo htmlspecialchars($lote); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="modal" class="modal">
            <div class="modal-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                     <h4 class="fw-bold text-success m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS</h4>
                     <span class="close close-xls">&times;</span>
                </div>
                <form action="report_articulo_xlsx.php" method="GET">
                    <div class="mb-3">
                        <label for="identificador_solicitud" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                        <select name="identificador_solicitud" id="identificador_solicitud" class="form-select">
                            <option value="">Selecciona un identificador</option>
                            <?php
                            foreach ($identificadores as $row_ident) {
                                echo '<option value="' . htmlspecialchars($row_ident['identificador_solicitud']) . '">' . htmlspecialchars($row_ident['identificador_solicitud']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="ano" class="form-label text-secondary fw-semibold">Año de filtrado:</label>
                        <input type="number" name="ano" id="ano" class="form-control" placeholder="Ej: 2024">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg shadow-sm rounded-3"><i class="fas fa-download me-2"></i>Descargar Archivo</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="modalCuadros" class="modal">
            <div class="modal-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                     <h4 class="fw-bold text-primary m-0"><i class="fas fa-table me-2"></i>Generar Cuadros</h4>
                     <span class="close close-cuadros">&times;</span>
                </div>
                <form action="cuadros_articulos.php" method="GET">
                    <div class="mb-3">
                        <label for="cuadro_ano" class="form-label text-secondary fw-semibold">Año:</label>
                        <select name="cuadro_ano" id="cuadro_ano" class="form-select">
                            <option value="">Selecciona un año</option>
                            <?php
                            $anos = array_unique(array_map(function($row) {
                                return substr($row['identificador_solicitud'], 0, 4);
                            }, $identificadores));
                            sort($anos);
                            foreach ($anos as $ano_val) {
                                echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="cuadro_identificador_solicitud" class="form-label text-secondary fw-semibold">Identificador:</label>
                        <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-select">
                            <option value="">Selecciona un identificador</option>
                            <?php
                            foreach ($identificadores as $row_ident_cuadro) {
                                echo '<option value="' . htmlspecialchars($row_ident_cuadro['identificador_solicitud']) . '" data-ano="' . substr($row_ident_cuadro['identificador_solicitud'], 0, 4) . '">'
                                    . htmlspecialchars($row_ident_cuadro['identificador_solicitud']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm rounded-3"><i class="fas fa-file-alt me-2"></i>Visualizar Cuadro</button>
                    </div>
                </form>
            </div>
        </div>
              
        <div id="modalResoluciones" class="modal">
            <div class="modal-content" style="max-width: 650px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                     <h4 class="fw-bold text-info m-0" style="color: #0284c7 !important;"><i class="fas fa-file-word me-2"></i>Resoluciones</h4>
                     <span class="close close-resoluciones">&times;</span>
                </div>
                
                <form action="resoluciones_articulos.php" method="GET">
                    <div class="row bg-light p-3 mb-4 border rounded-3 mx-0">
                        <div class="col-md-6 mb-2">
                            <label for="res_ano" class="form-label text-secondary fw-semibold">Año del Paquete:</label>
                            <select name="res_ano" id="res_ano" class="form-select">
                                <option value="">Selecciona un año</option>
                                <?php
                                foreach ($anos as $ano_val) {
                                    echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="res_identificador" class="form-label text-secondary fw-semibold">Identificador:</label>
                            <select name="cuadro_identificador_solicitud" id="res_identificador" class="form-select" required>
                                <option value="">Selecciona un identificador</option>
                                <?php
                                foreach ($identificadores as $row_ident_res) {
                                    echo '<option value="' . htmlspecialchars($row_ident_res['identificador_solicitud']) . '" data-ano="' . substr($row_ident_res['identificador_solicitud'], 0, 4) . '">'
                                        . htmlspecialchars($row_ident_res['identificador_solicitud']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="mb-3 text-secondary border-bottom pb-2 fw-bold">Datos del Oficio (Opcionales)</h6>
                    <div class="row px-2">
                        <div class="col-md-6 mb-3">
                            <label for="num_resolucion" class="form-label text-muted">N° de resolución inicial:</label>
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
                        <button type="submit" class="btn btn-info text-white btn-lg shadow-sm rounded-3" style="background-color: #0ea5e9; border-color: #0ea5e9;"><i class="fas fa-file-word me-2"></i>Generar Documento Word</button>
                    </div>
                </form>
            </div>
        </div>
      
    </div>

    <script>
        document.getElementById('cuadro_ano').addEventListener('change', function () {
            const selectedYear = this.value; 
            const identificadorSelect = document.getElementById('cuadro_identificador_solicitud');
            const opciones = identificadorSelect.querySelectorAll('option');

            if (!selectedYear) {
                opciones.forEach(option => option.style.display = 'block');
                identificadorSelect.value = '';
                return;
            }

            opciones.forEach(option => {
                if (option.dataset.ano === selectedYear || option.value === '') {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            identificadorSelect.value = '';
        });

        document.getElementById('res_ano').addEventListener('change', function () {
            const selectedYear = this.value;
            const selectIdent = document.getElementById('res_identificador');
            const opciones = selectIdent.querySelectorAll('option');
            opciones.forEach(opt => {
                opt.style.display = (!selectedYear || opt.dataset.ano === selectedYear || opt.value === '') ? 'block' : 'none';
            });
            selectIdent.value = '';
        });

        // Cambia el nombre aquí también
function confirmAnularArticulo(id) {
    if (confirm("¿Estás seguro de que quieres anular esta solicitud de artículo?")) {
        const motivo = prompt("Por favor, indique el motivo de la anulación:");
        if (motivo && motivo.trim() !== "") {
            // Verifica que la URL sea eliminar_solicitud.php
            window.location.href = 'eliminar_solicitud.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
        } else if (motivo !== null) {
            alert("El motivo de la anulación es obligatorio.");
        }
    }
}

        // ✅ CORRECTO
            function initSolicitudesModule() {
                $('#openModal').on('click', function() { $('#modal').fadeIn(200); });
                $('#openModalCuadros').on('click', function() { $('#modalCuadros').fadeIn(200); });
                $('#openModalResoluciones').on('click', function() { $('#modalResoluciones').fadeIn(200); });

                $('.close-xls, .close-cuadros, .close-resoluciones').on('click', function() {
                    $('.modal').fadeOut(200);
                });

                $(window).on('click', function(event) {
                    if ($(event.target).hasClass('modal')) {
                        $('.modal').fadeOut(200);
                    }
                });
            }

            if (window.jQuery) {
                initSolicitudesModule();
            } else {
                var checkInterval = setInterval(function() {
                    if (window.jQuery) {
                        clearInterval(checkInterval);
                        initSolicitudesModule();
                    }
                }, 10);
            }

    </script>
</body>
</html>