<?php
// Incluir la conexión a la base de datos
include 'conn.php';

// Consulta para obtener los identificadores de todas las tablas
$sql = "
    SELECT identificador FROM premios
UNION
SELECT identificador FROM libros
UNION
SELECT identificador_solicitud AS identificador FROM solicitud
UNION
SELECT identificador_completo AS identificador FROM creacion
UNION
SELECT identificador FROM innovacion
UNION
SELECT identificador FROM patentes
UNION
SELECT identificador FROM titulos
UNION
SELECT identificador FROM trabajos_cientificos
UNION
SELECT identificador FROM traduccion_libros
UNION
SELECT identificador FROM produccion_t_s
UNION
SELECT identificador_completo AS identificador FROM creacion_bon
UNION
SELECT identificador_completo AS identificador FROM ponencias_bon
UNION
SELECT identificador_completo AS identificador FROM publicacion_bon
UNION
SELECT identificador_completo AS identificador FROM resena_bon
UNION
SELECT identificador FROM direccion_tesis
UNION
SELECT identificador FROM posdoctoral
UNION
SELECT identificador FROM trabajos_cientificos_bon
UNION
SELECT identificador FROM traduccion_bon;
";

$result = $conn->query($sql);
$datos = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $identificador = $row['identificador'];
        $anio = substr($identificador, 0, 4);

        if (is_numeric($anio)) {
            $datos[$anio][] = $identificador;
        }
    }
}

// Ordenar años de mayor a menor
krsort($datos);

// Ordenar cada lista de identificadores dentro del año
foreach ($datos as &$lista) {
    natsort($lista);   // orden natural 
    $lista = array_values($lista); // reindexar
}
unset($lista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consola de Búsqueda | CIARP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --accent-color: #3b82f6;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            display: flex;
            height: 100vh;
            margin: 0;
            overflow: hidden; /* Evita scroll en todo el body */
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 320px;
            background-color: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid #1e293b;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #1e293b;
            background: linear-gradient(to bottom, #1e293b, #0f172a);
        }

        .sidebar-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .sidebar-content::-webkit-scrollbar { width: 6px; }
        .sidebar-content::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        /* Años (Acordeón) */
        .year-btn {
            width: 100%;
            background: transparent;
            border: none;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            margin-bottom: 5px;
        }

        .year-btn:hover { background-color: var(--sidebar-hover); color: white; }
        .year-btn.active { color: white; background-color: rgba(59, 130, 246, 0.15); border-left: 3px solid var(--accent-color); }
        .year-btn i.fa-chevron-down { transition: transform 0.3s; }
        .year-btn.active i.fa-chevron-down { transform: rotate(180deg); }

        /* Lista de Identificadores */
        .identifier-list {
            display: none;
            list-style: none;
            padding: 0 0 10px 10px;
            margin: 0;
            border-left: 1px dashed #334155;
            margin-left: 20px;
        }

        .identifier-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            margin: 3px 0;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .identifier-item:hover { background-color: var(--sidebar-hover); }

        .identifier-item a.id-link {
            text-decoration: none;
            color: #cbd5e1;
            font-size: 0.85rem;
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color 0.2s;
        }

        .identifier-item a.id-link:hover { color: #3b82f6; }
        
        .identifier-item a.id-link.active-id { color: #10b981; font-weight: bold; }

        .download-btn {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .download-btn:hover { background-color: #10b981; color: white; }

        /* --- MAIN CONTENT --- */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Clave para evitar desbordes */
            background-color: #f1f5f9;
        }

        .top-nav {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            z-index: 10;
        }

        .content-area {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto; /* Scroll vertical general */
        }

        /* Tarjeta de resultados controlada */
        .result-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            padding: 25px;
            min-height: 400px;
            /* AQUÍ ESTÁ LA MAGIA PARA QUE NO SEA INVASIVO HACIA LOS LADOS */
            width: 100%;
            overflow-x: auto; 
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }

        /* Estilos que vienen de AJAX (Tablas) */
        .tabla-registros {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow-x: auto; /* Asegura scroll en tablas internas */
        }
        
        .tabla-link {
            display: inline-block;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600;
            padding: 10px 15px;
            background: #f1f5f9;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            transition: all 0.2s;
            cursor: pointer;
        }
        .tabla-link:hover { background: #e2e8f0; color: #3b82f6; }
        .tabla-link i { margin-right: 8px; color: #64748b; }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            background: white;
        }
        .tabla-datos th {
            background: #e2e8f0;
            color: #334155;
            padding: 10px;
            border: 1px solid #cbd5e1;
            white-space: nowrap;
        }
        .tabla-datos td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h5 class="m-0 fw-bold text-white"><i class="fas fa-folder-tree text-primary me-2"></i>Archivador CIARP</h5>
        <small class="text-muted">Explorador de Identificadores</small>
    </div>
    
    <div class="sidebar-content">
        <?php foreach ($datos as $anio => $identificadores) : ?>
            <div class="year-group">
                <button class="year-btn" onclick="toggleIdentifiers(this, 'list-<?= $anio ?>')">
                    <span><i class="far fa-calendar-alt me-2 text-primary"></i> Lotes <?= $anio ?></span>
                    <i class="fas fa-chevron-down fa-sm"></i>
                </button>
                <ul id="list-<?= $anio ?>" class="identifier-list">
                    <?php foreach ($identificadores as $id) : ?>
                        <li class="identifier-item">
                            <a href="#" class="id-link" onclick="buscarTablas('<?= htmlspecialchars($id) ?>', this); return false;" title="<?= htmlspecialchars($id) ?>">
                                <i class="fas fa-hashtag me-1 opacity-50"></i> <?= htmlspecialchars($id) ?>
                            </a>
                            <a href="report_consolidado.php?identificador_solicitud=<?= urlencode($id) ?>" class="download-btn" target="_blank" title="Descargar PDF Consolidado">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</aside>

<main class="main-content">
    <header class="top-nav">
        <div>
            <h5 class="m-0 fw-bold text-dark" id="display-title">Vista de Detalles</h5>
            <small class="text-muted" id="display-subtitle">Seleccione un identificador en el panel izquierdo</small>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-left me-1"></i> Volver a Formularios
            </a>
        </div>
    </header>

    <div class="content-area">
        <div class="result-card shadow-sm" id="resultado-container">
            <div id="resultado" class="w-100">
                <div class="empty-state">
                    <i class="fas fa-hand-pointer"></i>
                    <h4>Explorador Inactivo</h4>
                    <p>Haz clic en un identificador para ver en qué tablas y módulos aparece registrado.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Toggle de los Años con animación
function toggleIdentifiers(btn, listId) {
    const list = document.getElementById(listId);
    const isVisible = list.style.display === "block";
    
    // Cerrar todos los demás (Opcional, si quieres comportamiento acordeón estricto)
    // $('.identifier-list').slideUp(200);
    // $('.year-btn').removeClass('active');

    if (isVisible) {
        $(list).slideUp(200);
        $(btn).removeClass('active');
    } else {
        $(list).slideDown(200);
        $(btn).addClass('active');
    }
}

// Búsqueda de Tablas (AJAX)
function buscarTablas(identificador, element) {
    // Estilos activos en el menú
    $('.id-link').removeClass('active-id');
    if(element) $(element).addClass('active-id');

    // Cambiar Títulos Superiores
    $('#display-title').html('<i class="fas fa-search me-2 text-primary"></i> Buscando: ' + identificador);
    $('#display-subtitle').text('Cargando registros asociados...');

    // Mostrar Spinner de Carga
    $('#resultado').html(`
        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <h5>Analizando base de datos...</h5>
        </div>
    `);

    $.ajax({
        url: 'buscar_tablas.php',
        type: 'POST',
        data: { identificador: identificador },
        success: function(response) {
            $('#display-title').html('<i class="fas fa-database me-2 text-success"></i> Resultados para: ' + identificador);
            $('#display-subtitle').text('Módulos donde se encontró información');
            
            // Inyectar HTML
            $('#resultado').html(response).hide().fadeIn(300);
            
            // Decorar los links generados por el AJAX para que se vean como botones
            $('#resultado .tabla-link').each(function() {
                $(this).prepend('<i class="fas fa-table"></i> ');
            });
        },
        error: function() {
            $('#resultado').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error al conectar con el servidor.</div>');
        }
    });
}

// Cargar Registros dentro de la Tabla seleccionada
function cargarRegistros(tabla, campo, identificador) {
    var divId = "#registros-" + tabla;
    var div = $(divId);

    if (div.is(":visible")) {
        div.slideUp(200);
    } else {
        div.html('<div class="spinner-border spinner-border-sm text-secondary m-3"></div>').show();
        $.ajax({
            url: 'cargar_registros.php',
            type: 'POST',
            data: { tabla: tabla, campo: campo, identificador: identificador },
            success: function(response) {
                div.html(response).hide().slideDown(200);
                // Llamar a la función de ajuste de columnas si es necesario
                fixTableColsIn(divId);
            }
        });
    }
}

// Fix para columnas (tu script original)
function fixTableColsIn(containerSelector) {
    document.querySelectorAll(containerSelector + ' table').forEach(table => {
        table.classList.add('tabla-datos'); // Fuerza el estilo de tabla
        for (let r = 0; r < table.rows.length; r++) {
            const cells = table.rows[r].cells;
            if (cells.length > 0) {
                cells[cells.length - 1].classList.add('last-col');
                cells[0].classList.add('first-col');
            }
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>