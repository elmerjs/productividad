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

// Agrupar resultados por id de solicitud
$sql .= " GROUP BY s.id_solicitud_articulo";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores de solicitud
$identificadores_result = $conn->query("SELECT DISTINCT identificador_solicitud FROM solicitud"); 
$identificadores = [];
while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
}

// Realizar la consulta para obtener los identificadores de solicitud (El que ya tenías)
$identificadores_result = $conn->query("SELECT DISTINCT identificador_solicitud FROM solicitud"); 
$identificadores = [];
while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
}

// NUEVO: Obtener solo los 6 LOTES MÁS RECIENTES para las tarjetas de acceso rápido
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

    <style>
        /* 1. ESTILOS BASE Y TIPOGRAFÍA */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9; /* Fondo gris muy suave y moderno */
            color: #334155;
            padding-top: 20px;
        }

        /* 2. ENCABEZADO Y TARJETAS */
        .page-header {
            margin-bottom: 2rem;
        }
        .page-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.75rem;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .page-subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-top: 4px;
        }
        .custom-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            padding: 20px;
        }

        /* 3. BOTONES MODERNIZADOS */
        .btn-modern {
            font-weight: 500;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* 4. MODALES MEJORADOS */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(15, 23, 42, 0.6); /* Fondo más oscuro con blur virtual */
            backdrop-filter: blur(4px);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #ffffff;
            margin: 2% auto;
            padding: 30px;
            border: none;
            width: 90%; 
            max-width: 600px;
            border-radius: 16px; /* Bordes más redondeados */
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: modalFadeIn 0.3s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .close {
            color: #94a3b8;
            float: right;
            font-size: 24px;
            font-weight: bold;
            line-height: 1;
            transition: color 0.2s;
        }
        .close:hover, .close:focus {
            color: #0f172a;
            text-decoration: none;
            cursor: pointer;
        }

        /* 5. TABLA Y DATATABLES */
        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0;
            width: 100% !important;
        }
        #revistas thead th {
            background-color: #f8fafc;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            border-top: none;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            text-align: center;
            vertical-align: middle;
        }
        #revistas tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Componentes de la tabla */
        .badge-estado {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4em 0.8em;
            border-radius: 6px;
            letter-spacing: 0.3px;
        }
        .text-truncate-custom {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }
        .btn-action {
            border-radius: 6px;
            padding: 0.35rem 0.6rem;
            font-size: 0.8rem;
            margin: 0 2px;
            border: none;
        }
        /* 1. COMPRIMIR LAS CELDAS AL MÁXIMO */
#revistas tbody td {
    vertical-align: middle;
    font-size: 0.85rem; /* Letra un pelín más pequeña para que encaje mejor */
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    padding: 4px 8px !important; /* <-- El !important fuerza a Bootstrap a obedecer */
    line-height: 1.2; /* Reduce el espacio en blanco entre líneas de texto */
}

/* 2. ENCOGER LOS BOTONES DE ACCIÓN (LOS ICONOS) */
.btn-action {
    border-radius: 4px;
    padding: 3px 6px !important; /* <-- Botones mucho más delgados verticalmente */
    font-size: 0.8rem; /* Tamaño del icono */
    margin: 0 1px;
    border: none;
    line-height: 1; /* Evita que el botón tenga altura extra fantasma */
}

/* 3. ENCOGER LAS ETIQUETAS (BADGES DE ESTADO Y TIPO) */
.badge-estado, .badge {
    padding: 3px 6px !important; /* <-- Aplasta el alto de las pastillas de colores */
    font-size: 0.7rem;
    line-height: 1;
    display: inline-block;
}
        
        /* 6. CARRUSEL DE LOTES RECIENTES (UX) */
        .quick-audit-section {
            margin-bottom: 20px;
        }
        .quick-audit-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .lotes-carousel {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 8px; /* Espacio para el scrollbar */
        }
        /* Estilizar la barra de scroll horizontal para que sea elegante */
        .lotes-carousel::-webkit-scrollbar { height: 6px; }
        .lotes-carousel::-webkit-scrollbar-track { background: transparent; }
        .lotes-carousel::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .lotes-carousel::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .lote-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .lote-card:hover {
            border-color: #3b82f6;
            background-color: #f0fdf4; /* Fondo verde clarito al pasar el mouse */
            color: #166534;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(22, 101, 52, 0.1);
        }
        .lote-card.view-all {
            background-color: #f8fafc;
            color: #64748b;
            border: 1px dashed #cbd5e1;
        }
        .lote-card.view-all:hover {
            background-color: #e2e8f0;
            color: #0f172a;
            border-style: solid;
        }
    </style>
</head>
<body>

 <div class="container-fluid px-4 mb-5">
        
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="page-title"><i class="fas fa-layer-group text-primary me-2"></i>Gestión de Solicitudes</h1>
                <p class="page-subtitle">Listado maestro de artículos y revistas indexadas</p>
            </div>
            
            <div class="d-flex gap-2 flex-wrap bg-white p-2 rounded-3 shadow-sm border">
                <button id="openModal" class="btn btn-primary btn-modern d-flex align-items-center gap-2">
                    <i class="fas fa-file-excel"></i> XLS
                </button>
                <button id="openModalCuadros" class="btn btn-secondary btn-modern d-flex align-items-center gap-2">
                    <i class="fas fa-table"></i> Cuadros
                </button>
                <button id="openModalResoluciones" class="btn btn-info text-white btn-modern d-flex align-items-center gap-2">
                    <i class="fas fa-file-signature"></i> Resoluciones
                </button>
                
                <div class="vr mx-1 d-none d-md-block"></div> <a href="dashboard_analitica.php" class="btn btn-outline-primary btn-modern d-flex align-items-center gap-2" title="Ver solo artículos">
                    <i class="fas fa-chart-bar"></i> Auditoría Artículos
                </a>
                <a href="dashboard_analitica_full.php" class="btn btn-success btn-modern d-flex align-items-center gap-2" title="Ver auditoría completa (Libros, Patentes, etc)">
                    <i class="fas fa-shield-alt"></i> Auditoría 360°
                </a>
            </div>
        </div>
        
        <div class="custom-card">
            <div class="table-responsive">
                <table id="revistas" class="table table-hover align-middle" style="width:100%">
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
                            <th class="text-center" style="width: 8%">ESTADO</th> 
                            <th class="text-center" style="width: 8%">ACCIONES</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Mostrar los resultados de la consulta
                        while ($row = $result->fetch_assoc()) {
                            // Eliminar la primera parte "Facultad de "
                            $facultad = str_replace("Facultad de ", "", $row['FACULTAD']);

                            // Truncar los nombres y productos para tooltip
                            $nombreProducto = htmlspecialchars($row['NOMBRE DEL PRODUCTO']);
                            $nombreRevista = htmlspecialchars($row['NOMBRE REVISTA']);
                            $nombres = htmlspecialchars($row['NOMBRES']);
                            $departamento = htmlspecialchars($row['DEPARTAMENTO']);
                            
                            // Estado badge logic
                            $estado = htmlspecialchars($row['estado_solicitud']);
                            $badgeClass = 'bg-secondary'; // Default
                            if (stripos($estado, 'aprobado') !== false) $badgeClass = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                            elseif (stripos($estado, 'rechazado') !== false) $badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                            elseif (stripos($estado, 'pendiente') !== false) $badgeClass = 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-50';
                            elseif (stripos($estado, 'revisión') !== false) $badgeClass = 'bg-info bg-opacity-10 text-info-emphasis border border-info border-opacity-25';

                            // Mostrar los resultados de la tabla
                            echo '<tr>';
                            echo '<td class="text-center fw-bold">
                                    <a href="reporte_articulo.php?id_solicitud_articulo=' . htmlspecialchars($row['id_solicitud_articulo']) . '" 
                                       class="text-decoration-none text-primary">
                                       ' . htmlspecialchars($row['id_solicitud_articulo']) . '
                                    </a>
                                  </td>';                    
                            echo '<td><span class="badge bg-light text-secondary border px-2 py-1">' . htmlspecialchars($row['identificador_solicitud']) . '</span></td>';
                           echo '<td>
        <div class="text-truncate-custom fw-medium text-dark" style="max-width: 160px;" title="Facultad: ' . htmlspecialchars($facultad) . '">
            ' . $departamento . '
        </div>
      </td>';
                            echo '<td><small class="text-secondary">' . htmlspecialchars($row['numero_oficio']) . '</small></td>';

                            echo '<td>
                                    <div class="text-truncate-custom" style="max-width: 180px;" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">
                                        ' . $nombres . '
                                    </div>
                                  </td>';                    
                            echo '<td>
                                    <div class="text-truncate-custom" style="max-width: 200px;" title="' . $nombreProducto . '">
                                        ' . $nombreProducto . '
                                    </div>
                                  </td>';
                            echo '<td><span class="badge bg-light text-secondary border px-2 py-1">' . htmlspecialchars($row['TIPO DE ARTICULO']) . '</span></td>';
                            
                            echo '<td>
                                    <div class="text-truncate-custom" title="' . $nombreRevista . ' (' . ucfirst(strtolower($row['TIPO REVISTA'])) . ')">
                                        ' . $nombreRevista . '
                                    </div>
                                  </td>';
                            echo '<td><small class="font-monospace text-muted">' . htmlspecialchars($row['ISSN']) . '</small></td>';
                            echo '<td class="text-center fw-bold text-success">' . htmlspecialchars($row['puntaje']) . '</td>';

                            echo '<td class="text-center"><span class="badge-estado ' . $badgeClass . '">' . strtoupper($estado) . '</span></td>';

                            // Columna de acciones
                            echo '<td class="text-center text-nowrap">';
                            echo '<a href="actualizar_articulo.php?id_solicitud=' . $row['ID_S'] . '" class="btn btn-warning btn-sm btn-action text-dark shadow-sm" title="Editar"><i class="fas fa-pen"></i></a> ';
                            echo '<button class="btn btn-danger btn-sm btn-action shadow-sm" onclick="confirmDeleteWithReason(' . $row['ID_S'] . ')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
      <div class="quick-audit-section">
            <div class="quick-audit-title">
                <i class="fas fa-bolt text-warning me-1"></i> Auditoría Rápida por Lotes Recientes
            </div>
            <div class="lotes-carousel">
                <?php foreach($ultimos_lotes as $lote): ?>
                    <a href="auditoria_lote.php?lote=<?php echo urlencode($lote); ?>" class="lote-card" title="Auditar duplicados en este lote">
                        <i class="fas fa-folder-open text-primary"></i> Lote: <?php echo htmlspecialchars($lote); ?>
                    </a>
                <?php endforeach; ?>
                
                <a href="#" class="lote-card view-all" title="Abrir selector de todos los lotes">
                    Ver todos <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header border-bottom-0 pb-0 mb-3 d-flex justify-content-between align-items-center">
             <h4 class="modal-title fw-bold text-primary m-0"><i class="fas fa-file-excel me-2"></i>Reporte XLS</h4>
             <span class="close close-xls">&times;</span>
        </div>
        <div class="modal-body">
            <form action="report_articulo_xlsx.php" method="GET">
                <div class="mb-3">
                    <label for="identificador_solicitud" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                    <select name="identificador_solicitud" id="identificador_solicitud" class="form-select shadow-sm">
                        <option value="">Selecciona un identificador</option>
                        <?php
                        foreach ($identificadores as $row_ident) {
                            echo '<option value="' . htmlspecialchars($row_ident['identificador_solicitud']) . '">' . htmlspecialchars($row_ident['identificador_solicitud']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="ano" class="form-label text-secondary fw-semibold">Año:</label>
                    <input type="number" name="ano" id="ano" class="form-control shadow-sm" placeholder="Ej: 2024">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm"><i class="fas fa-download me-2"></i>Descargar Archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalCuadros" class="modal">
    <div class="modal-content">
        <div class="modal-header border-bottom-0 pb-0 mb-3 d-flex justify-content-between align-items-center">
             <h4 class="modal-title fw-bold text-secondary m-0"><i class="fas fa-table me-2"></i>Cuadros de Resumen</h4>
             <span class="close close-cuadros">&times;</span>
        </div>
        <div class="modal-body">
            <form action="cuadros_articulos.php" method="GET">
                
                <div class="mb-3">
                    <label for="cuadro_ano" class="form-label text-secondary fw-semibold">Año:</label>
                    <select name="cuadro_ano" id="cuadro_ano" class="form-select shadow-sm">
                        <option value="">Selecciona un año</option>
                        <?php
                        // Generar los años únicos
                        $anos = array_unique(array_map(function($row) {
                            return substr($row['identificador_solicitud'], 0, 4);
                        }, $identificadores));

                        // Ordenar los años
                        sort($anos);

                        // Generar las opciones del select
                        foreach ($anos as $ano) {
                            echo '<option value="' . htmlspecialchars($ano) . '">' . htmlspecialchars($ano) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="cuadro_identificador_solicitud" class="form-label text-secondary fw-semibold">Identificador de Solicitud:</label>
                    <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-select shadow-sm">
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
                    <button type="submit" class="btn btn-secondary btn-lg shadow-sm"><i class="fas fa-file-alt me-2"></i>Generar Vista</button>
                </div>
            </form>
        </div>
    </div>
</div>
      
<div id="modalResoluciones" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header border-bottom-0 pb-0 mb-3 d-flex justify-content-between align-items-center">
             <h3 class="modal-title fw-bold text-info m-0"><i class="fas fa-file-word me-2"></i>Resoluciones</h3>
             <span class="close close-resoluciones">&times;</span>
        </div>
        
        <div class="modal-body">
            <form action="resoluciones_articulos.php" method="GET">
                
                <div class="row bg-light p-3 mb-4 border rounded-3 shadow-sm mx-0">
                    <div class="col-md-6 mb-2">
                        <label for="res_ano" class="form-label text-secondary fw-semibold">Año del Paquete:</label>
                        <select name="res_ano" id="res_ano" class="form-select shadow-sm">
                            <option value="">Selecciona un año</option>
                            <?php
                            foreach ($anos as $ano_val) {
                                echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="cuadro_identificador_solicitud" class="form-label text-secondary fw-semibold">Identificador:</label>
                        <select name="cuadro_identificador_solicitud" id="res_identificador" class="form-select shadow-sm" required>
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
                        <label for="num_resolucion" class="form-label text-muted">N° de resolución:</label>
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
                    <button type="submit" class="btn btn-info text-white btn-lg shadow-sm"><i class="fas fa-file-word me-2"></i>Generar Documento Word</button>
                </div>
            </form>
        </div>
    </div>
</div>
      
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // JavaScript para filtrar los identificadores por año en el modal de cuadros
    document.getElementById('cuadro_ano').addEventListener('change', function () {
        const selectedYear = this.value; // Año seleccionado
        const identificadorSelect = document.getElementById('cuadro_identificador_solicitud');
        const opciones = identificadorSelect.querySelectorAll('option');

        // Mostrar todas las opciones si no se selecciona un año
        if (!selectedYear) {
            opciones.forEach(option => option.style.display = 'block');
            identificadorSelect.value = '';
            return;
        }

        // Filtrar opciones según el año seleccionado
        opciones.forEach(option => {
            if (option.dataset.ano === selectedYear || option.value === '') {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });

        // Reiniciar la selección
        identificadorSelect.value = '';
    });
</script>
<script>
    function confirmDeleteWithReason(id) {
        const confirmation = confirm("¿Estás seguro de que quieres eliminar esta solicitud?");
        if (confirmation) {
            const motivo = prompt("Por favor, indique el motivo de la anulación:");
            if (motivo && motivo.trim() !== "") {
                // Redirigir con el ID y el motivo como parámetros
                window.location.href = 'eliminar_solicitud.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
            }
        }
    }
</script>
<script>
    $(document).ready(function() {
        // Inicializar DataTable con configuración en español y botones
        $('#revistas').DataTable({
            responsive: true,
            dom: 'Blfrtip',
            // NUEVO: Ordenar por la primera columna (índice 0, que es el ID) de forma descendente ('desc')
            "order": [[ 0, "desc" ]], 
            buttons: [
                { extend: 'copy', className: 'btn btn-light border btn-sm', text: '<i class="fas fa-copy text-secondary"></i> Copiar' },
                { extend: 'csv', className: 'btn btn-light border btn-sm', text: '<i class="fas fa-file-csv text-success"></i> CSV' },
                { extend: 'excel', className: 'btn btn-light border btn-sm', text: '<i class="fas fa-file-excel text-success"></i> Excel' },
                { extend: 'pdf', className: 'btn btn-light border btn-sm', text: '<i class="fas fa-file-pdf text-danger"></i> PDF' },
                { extend: 'print', className: 'btn btn-light border btn-sm', text: '<i class="fas fa-print text-primary"></i> Imprimir' }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            },
            columnDefs: [
                { orderable: false, targets: -1 } // Deshabilitar ordenamiento en columna de acciones
            ]
        });

        // Abrir modales
        $("#openModal").click(function() {
            $("#modal").css("display", "block");
        });

        $("#openModalCuadros").click(function() {
            $("#modalCuadros").css("display", "block");
        });
        
        // Cerrar los modales
        $(".close-xls").click(function() {
            $("#modal").css("display", "none");
        });

        $(".close-cuadros").click(function() {
            $("#modalCuadros").css("display", "none");
        });
        
        // Cerrar los modales si se hace clic fuera de ellos
        $(window).click(function(event) {
            if ($(event.target).is("#modal")) {
                $("#modal").css("display", "none");
            }
            if ($(event.target).is("#modalCuadros")) {
                $("#modalCuadros").css("display", "none");
            }
            if ($(event.target).is("#modalResoluciones")) {
                $("#modalResoluciones").css("display", "none");
            }
        });

        // Acciones de editar y eliminar (legacy support para botones fuera de datatable si los hubiera)
        $(".edit-btn").click(function() {
            var id = $(this).data("id");
            alert("Editar solicitud con ID: " + id);
        });

        $(".delete-btn").click(function() {
            var id = $(this).data("id");
            var confirmDelete = confirm("¿Seguro que quieres eliminar esta solicitud?");
            if (confirmDelete) {
                alert("Eliminar solicitud con ID: " + id);
            }
        });
        
        // Abrir modal Resoluciones
        $("#openModalResoluciones").click(function() {
            $("#modalResoluciones").css("display", "block");
        });

        // Cerrar modal Resoluciones
        $(".close-resoluciones").click(function() {
            $("#modalResoluciones").css("display", "none");
        });

        // Filtro de años para el modal de resoluciones
        document.getElementById('res_ano').addEventListener('change', function () {
            const selectedYear = this.value;
            const selectIdent = document.getElementById('res_identificador');
            const opciones = selectIdent.querySelectorAll('option');
            opciones.forEach(opt => {
                opt.style.display = (!selectedYear || opt.dataset.ano === selectedYear || opt.value === '') ? 'block' : 'none';
            });
            selectIdent.value = '';
        });
        
    });
</script>
</body>
</html>