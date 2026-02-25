<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

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

if ($result->num_rows > 0) {
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
    natsort($lista);   // orden natural (2025_06_1xxx quedará antes de 2025_08_1)
    $lista = array_values($lista); // reindexar
}
unset($lista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filtrar Reporte</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            display: flex;
            font-family: Arial, sans-serif;
        }
        .sidebar {
            width: 250px;
            padding: 20px;
            border-right: 1px solid #ccc;
            overflow-y: auto;
            height: 100vh;
        }
        .year-item {
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 5px;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .year-item:hover {
            background-color: #e2e6ea;
        }
        .identifier-list {
            display: none;
            margin-left: 20px;
            padding: 5px;
        }
        .identifier-list li {
            list-style-type: none;
            padding: 3px;
        }
        .identifier-list a {
            text-decoration: none;
            color: #007bff;
            cursor: pointer;
        }
        .identifier-list a:hover {
            text-decoration: underline;
        }
        .main-content {
            flex-grow: 1;
            padding: 20px;
        }
        .result-box {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .result-box ul {
            padding-left: 20px;
        }
        /* Estilos para la lista de tablas */
.tabla-lista {
    list-style-type: none;
    padding-left: 10px;
}

.tabla-lista li {
    margin-bottom: 5px;
}

/* Estilos para el "botón" de cada tabla (sin bordes gruesos) */
.tabla-link {
    display: block;
    text-decoration: none;
    color: #333;
    font-weight: bold;
    padding: 3px 8px;
    cursor: pointer;
}

.tabla-link:hover {
    color: #007bff; /* Azul como los links */
}

/* Ocultar contenido desplegable por defecto */
.tabla-registros {
    display: none;
    padding-left: 15px;
    border-left: 2px solid #ddd;
    margin-top: 5px;
}

/* Mejoramos la tabla de datos */
.tabla-datos {
    width: 100%;
    border-collapse: collapse;
}

.tabla-datos th, .tabla-datos td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
}

.columna-pequena {
    width: 10%;
    min-width: 80px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.columna-mediana {
    width: 20%;
    min-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.columna-normal {
    width: auto; /* El resto de la tabla se adapta automáticamente */
}
.identifier-item {
    display: flex;
    align-items: center;
    gap: 10px; /* Espacio entre el identificador y el botón */
}

.identifier-item a {
    text-decoration: none;
    color: #007bff;
}

.identifier-item .download-btn {
    background-color: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 14px;
    transition: background 0.3s;
}

.identifier-item .download-btn:hover {
    background-color: #218838;
}
    </style>
</head>
<body>

<!-- Barra lateral con los años y los identificadores -->
<div class="sidebar">
    <h3>Lista de Identificadores</h3>
    <?php foreach ($datos as $anio => $identificadores) : ?>
        <div class="year-item" onclick="toggleIdentifiers('list-<?= $anio ?>')">
            <?= $anio ?> ▼
        </div>
        <ul id="list-<?= $anio ?>" class="identifier-list">
            <?php foreach ($identificadores as $id) : ?>
                <li class="identifier-item">
                    <a href="#" onclick="buscarTablas('<?= htmlspecialchars($id) ?>'); return false;">
                        <?= htmlspecialchars($id) ?>
                    </a>
                    <a href="report_consolidado.php?identificador_solicitud=<?= urlencode($id) ?>" class="download-btn" target="_blank">
                        ⬇️
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div>  

<!-- Contenido principal -->
<div class="main-content">
    <h2>Información del Identificador</h2>
    <div id="resultado" class="result-box">
        <p>Selecciona un identificador para ver en qué tablas aparece.</p>
    </div>
</div>

<script>
// Mostrar/ocultar identificadores por año
function toggleIdentifiers(id) {
    var list = document.getElementById(id);
    list.style.display = (list.style.display === "none" || list.style.display === "") ? "block" : "none";
}

// Buscar en qué tablas está el identificador
function buscarTablas(identificador) {
    $.ajax({
        url: 'buscar_tablas.php',
        type: 'POST',
        data: { identificador: identificador },
        success: function(response) {
            $('#resultado').html(response);
        }
    });
}

// Cargar registros de una tabla específica
function cargarRegistros(tabla, campo, identificador) {
    var divId = "#registros-" + tabla;
    var div = $(divId);

    if (div.is(":visible")) {
        div.hide();
    } else {
        $.ajax({
            url: 'cargar_registros.php',
            type: 'POST',
            data: { tabla: tabla, campo: campo, identificador: identificador },
            success: function(response) {
                div.html(response).show();
            }
        });
    }
}
  function fixTableColsIn(containerSelector) {
  document.querySelectorAll(containerSelector + ' table').forEach(table => {
    // marcar la primera y la última columna en cada fila
    for (let r = 0; r < table.rows.length; r++) {
      const cells = table.rows[r].cells;
      if (cells.length > 0) {
        // marca la última celda
        cells[cells.length - 1].classList.add('last-col');
        // marca la primera por si quieres estilo específico
        cells[0].classList.add('first-col');
      }
    }
  });
}

// ejemplo: si después de cargar HTML pones la tabla dentro de '#registros-tablaX'
// llama a:
fixTableColsIn('.tabla-registros');
  
</script>

</body>
</html>
