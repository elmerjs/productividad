<?php
// Requerir la conexión a la base de datos
include_once('conn.php');

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT f.nombre_fac_min as FACULTAD, d.depto_nom_propio as DEPARTAMENTO,
    t.id_titulo AS id,
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
    
    -- Obtener los nombres de los profesores relacionados con el título
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' - ', ter.documento_tercero)
        ORDER BY ter.nombre_completo
        SEPARATOR ', '
    ) AS profesores
FROM 
    titulos t
JOIN 
    titulo_profesor tp ON tp.id_titulo = t.id_titulo
JOIN 
    tercero ter ON tp.fk_tercero = ter.documento_tercero
    join deparmanentos d  on d.PK_DEPTO = ter.fk_depto
    join facultad f on f.PK_FAC = d.FK_FAC
WHERE 1 = 1 ";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

$sql .= " GROUP BY 
    t.id_titulo, t.identificador, t.numero_oficio, t.titulo_obtenido, t.tipo, 
    t.tipo_estudio, t.institucion, t.fecha_terminacion, t.resolucion_convalidacion, 
    t.puntaje, t.tipo_productividad
ORDER BY 
    t.fecha_terminacion DESC
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores de título
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM titulos");
$identificadores = [];

while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Títulos Obtenidos</title>

    <!-- Incluir Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Incluir jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Incluir los estilos de DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

    <!-- Incluir los scripts de DataTables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <!-- Incluir los scripts de exportación -->
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-width: 600px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

       

/* Estiliza la tabla como antes */


    </style>
</head>

<body>
        <h1>Listado de Títulos Obtenidos</h1>
    <!-- Botones de acciones -->
    <div class="mb-3">
        <button id="openModaltt" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadrostt" class="btn btn-secondary">Generar Cuadros</button>
        <button id="openModalResolucionesTitulos" class="btn btn-info text-white">Generar Resoluciones</button>
    </div>

       
<div class="table-responsive">

        <!-- Tabla -->
        <table id="titulosTable" class="display table table-striped table-bordered">
            <thead>
                <tr> <th>FACULTAD</th>
                <th>DEPARTAMENTO</th>
                    <th>Identificador</th>
                    <th>Número de Oficio</th>
                    <th>Profesores</th>

                    <th>Título Obtenido</th>
                    <th>Tipo</th>
                    <th>Tipo de Estudio</th>
                    <th>Institución</th>
                    <th>Fecha de Terminación</th>
                    <th>Resolución Convalidación</th>
                    <th>Puntaje</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Mostrar los resultados de la consulta
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                     // Columna FACULTAD con texto limitado y título completo
                echo '<td title="' . $row['FACULTAD'] . '">' . substr(str_replace("Facultad de ", "", $row['FACULTAD']), 0, 15) . (strlen($row['FACULTAD']) > 15 ? '...' : '') . '</td>'; 

echo '<td title="' . $row['DEPARTAMENTO'] . '">' . substr($row['DEPARTAMENTO'], 0, 15) . (strlen($row['DEPARTAMENTO']) > 15 ? '...' : '') . '</td>';
                    echo '<td>' . htmlspecialchars($row['identificador']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['numero_oficio']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['profesores']) . '</td>';

                    echo '<td>' . htmlspecialchars($row['titulo_obtenido']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['tipo']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['tipo_estudio']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['institucion']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['fecha_terminacion']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['resolucion_convalidacion']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['puntaje']) . '</td>';
                    echo '<td>';
echo '<a href="editar_titulos.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a>';
                    echo '<button class="delete-btn btn btn-danger btn-sm" data-id="' . $row['id'] . '">Eliminar</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
</div>

    <!-- Modal para exportar a Excel -->
<div id="modaltt" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_titulos.php" method="GET">
            <label for="identificador_solicitud">Identificador de Solicitud:</label>
            <select name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident) {
                    echo '<option value="' . htmlspecialchars($row_ident['identificador']) . '">' . htmlspecialchars($row_ident['identificador']) . '</option>';
                }
                ?>
            </select>
            <br><br>
            <label for="ano">Año:</label>
            <input type="number" name="ano" id="ano" class="form-control">
            <br><br>
            <input type="submit" value="Generar Reporte" class="btn btn-primary">
        </form>
    </div>
</div>
<!-- Modal para ver cuadros -->
<div id="modalCuadrostt" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_titulos.php" method="GET">
            <label for="cuadro_identificador_solicitud">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident_cuadro) {
                    echo '<option value="' . htmlspecialchars($row_ident_cuadro['identificador']) . '">' . htmlspecialchars($row_ident_cuadro['identificador']) . '</option>';
                }
                ?>
            </select>
            <br><br>
            <label for="cuadro_ano">Año:</label>
            <input type="number" name="cuadro_ano" id="cuadro_ano" class="form-control">
            <br><br>
            <input type="submit" value="Generar Cuadro" class="btn btn-secondary">
        </form>
    </div>
</div>
<div id="modalResolucionesTitulos" class="modal">
    <div class="modal-content">
        <span class="close close-resoluciones-titulos">&times;</span>
        <h2>Generar Resoluciones de Títulos</h2>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>1. Filtrar por Año:</label>
                <select id="filtro_ano_res" class="form-control">
                    <option value="todos">-- Todos los años --</option>
                    <?php
                    // Obtenemos los años únicos de los identificadores (asumiendo formato YYYY_MM_...)
                    $resAnos = $conn->query("SELECT DISTINCT SUBSTRING(identificador, 1, 4) as ano FROM titulos ORDER BY ano DESC");
                    while($a = $resAnos->fetch_assoc()) {
                        echo '<option value="'.$a['ano'].'">'.$a['ano'].'</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <form action="resoluciones_titulos.php" method="GET">
            <label for="cuadro_identificador_solicitud">2. Selecciona el Paquete:</label>
            <select name="cuadro_identificador_solicitud" id="res_identificador_titulos" class="form-control" required>
                <option value="">-- Selecciona un identificador --</option>
                <?php
                // Por defecto cargamos todos, el JS filtrará después
                $resIds = $conn->query("SELECT DISTINCT identificador FROM titulos ORDER BY identificador DESC");
                while ($row = $resIds->fetch_assoc()) {
                    $ano_paquete = substr($row['identificador'], 0, 4);
                    echo '<option value="' . htmlspecialchars($row['identificador']) . '" data-ano="'.$ano_paquete.'">' . htmlspecialchars($row['identificador']) . '</option>';
                }
                ?>
            </select>
            <br>
            <button type="submit" class="btn btn-info text-white w-100">
                <i class="fas fa-file-word"></i> Generar Resoluciones Word
            </button>
        </form>
    </div>
</div>
<script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#titulosTable').DataTable({
            responsive: true, // Hace la tabla responsive
  
   
    dom: 'Bfrtip', // Incluye los botones de exportación
    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
    }
        });

        // Abrir el modal para generar XLS
        $("#openModaltt").click(function() {
            $("#modaltt").css("display", "block");
        });

        // Abrir el modal para generar Cuadros
        $("#openModalCuadrostt").click(function() {
            $("#modalCuadrostt").css("display", "block");
        });

        // Cerrar los modales
        $(".close").click(function() {
            $("#modaltt").css("display", "none");
        });

        $(".close-cuadros").click(function() {
            $("#modalCuadrostt").css("display", "none");
        });

        // Cerrar los modales si se hace clic fuera de ellos
        $(window).click(function(event) {
            if ($(event.target).is("#modaltt")) {
                $("#modaltt").css("display", "none");
            }
            if ($(event.target).is("#modalCuadrostt")) {
                $("#modalCuadrostt").css("display", "none");
            }
        });

        // Acciones de editar y eliminar
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
        // Abrir modal Resoluciones Títulos
        $("#openModalResolucionesTitulos").click(function() {
            $("#modalResolucionesTitulos").css("display", "block");
        });

        // Cerrar modal
        $(".close-resoluciones-titulos").click(function() {
            $("#modalResolucionesTitulos").css("display", "none");
        });
    });
    $(document).ready(function() {
    // Evento cuando cambia el filtro de año
    $('#filtro_ano_res').on('change', function() {
        var anoSeleccionado = $(this).val();
        
        // Resetear el select de identificadores
        $('#res_identificador_titulos').val("");
        
        // Filtrar las opciones
        $('#res_identificador_titulos option').each(function() {
            var optionAno = $(this).data('ano');
            
            if (anoSeleccionado === "todos") {
                $(this).show(); // Mostrar todo si elige "todos"
            } else {
                if (optionAno == anoSeleccionado || $(this).val() === "") {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        });
    });

    // Control de apertura del modal (ajustado al nuevo ID)
    $("#openModalResolucionesTitulos").click(function() {
        $("#modalResolucionesTitulos").show();
    });

    $(".close-resoluciones-titulos").click(function() {
        $("#modalResolucionesTitulos").hide();
    });
});
</script>

<!-- Incluir Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
