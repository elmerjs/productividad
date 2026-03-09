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
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID del título
$sql .= " GROUP BY t.id_titulo ORDER BY t.id_titulo DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

// --- EXTRACCIÓN DE IDENTIFICADORES Y AÑOS PARA EL MODAL ---
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM titulos ORDER BY identificador DESC");
$identificadores = [];
$unique_years = [];

while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
    
    // Extraer los primeros 4 dígitos para tener la lista de años
    $year = substr($row['identificador'], 0, 4);
    if (!empty($year) && is_numeric($year) && !in_array($year, $unique_years)) {
        $unique_years[] = $year;
    }
}
rsort($unique_years); // Ordenar años de mayor a menor
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Títulos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    
    <style>
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1050; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%;
            overflow: auto; 
            background-color: rgba(0, 0, 0, 0.5); 
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe; 
            margin: 2% auto; 
            padding: 20px; 
            border: 1px solid #888;
            width: 80%; 
            max-width: 600px;
            border-radius: 8px;
        }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h1>Listado de Títulos Obtenidos</h1>

    <div class="mb-3">
        <button id="openModal" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadros" class="btn btn-secondary">Generar Cuadros</button>
        <button id="openModalResolucionesTitulos" class="btn btn-info text-white">Generar Resoluciones</button>
    </div>

    <table id="titulos" class="table-striped table-bordered">
        <thead>
            <tr> 
                <th>ID</th>                
                <th>IDENTIFICADOR</th>
                <th>DEPARTAMENTO</th>
                <th>OFICIO</th>
                <th>PROFESOR(ES)</th>
                <th>TÍTULO OBTENIDO</th>
                <th>TIPO DE ESTUDIO</th>
                <th>INSTITUCIÓN</th>
                <th>PUNTAJE</th>
                <th>CONVALIDACIÓN</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = $result->fetch_assoc()) {
                $facultad = $row['FACULTAD'];
                $facultadTruncada = strlen($facultad) > 20 ? substr($facultad, 0, 20) . '...' : $facultad;

                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['identificador']) . '</td>';
                
                echo '<td class="facultad-truncate" title="' 
                    . htmlspecialchars($row['DEPARTAMENTO']) . ' - ' . htmlspecialchars($facultad) . '">'
                    . htmlspecialchars(substr($row['DEPARTAMENTO'], 0, 20)) . ' - ' . $facultadTruncada 
                    . '</td>';

                echo '<td>' . htmlspecialchars($row['numero_oficio']) . '</td>';
                echo '<td>' . htmlspecialchars($row['profesores']) . '</td>';
                echo '<td>' . htmlspecialchars($row['titulo_obtenido']) . '</td>';
                echo '<td>' . htmlspecialchars($row['tipo_estudio']) . '</td>';
                echo '<td>' . htmlspecialchars($row['institucion']) . '</td>';
                echo '<td>' . htmlspecialchars($row['puntaje']) . '</td>';
                echo '<td>' . htmlspecialchars($row['resolucion_convalidacion']) . '</td>';
                
                echo '<td>';
                echo '<a href="editar_titulos.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a> ';
                echo '<button class="delete-btn btn btn-danger btn-sm" data-id="' . $row['id'] . '">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close close-xls">&times;</span>
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

<div id="modalCuadros" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_titulos.php" method="GET">
            <label for="cuadro_ano">Año:</label>
            <select name="cuadro_ano" id="cuadro_ano" class="form-control mb-3">
                <option value="">Selecciona un año</option>
                <?php
                foreach ($unique_years as $ano) {
                    echo '<option value="' . htmlspecialchars($ano) . '">' . htmlspecialchars($ano) . '</option>';
                }
                ?>
            </select>
            <label for="cuadro_identificador_solicitud">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident_cuadro) {
                    echo '<option value="' . htmlspecialchars($row_ident_cuadro['identificador']) . '" data-ano="' . substr($row_ident_cuadro['identificador'], 0, 4) . '">'
                        . htmlspecialchars($row_ident_cuadro['identificador']) . '</option>';
                }
                ?>
            </select>
            <br><br>
            <input type="submit" value="Generar Cuadro" class="btn btn-secondary">
        </form>
    </div>
</div>

<div id="modalResolucionesTitulos" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close close-resoluciones-titulos">&times;</span>
        <h2 class="mb-4">Generar Resoluciones de Títulos (Word)</h2>
        <form action="resoluciones_titulos.php" method="GET">
            
            <div class="row bg-light p-3 mb-3 border rounded">
                <div class="col-md-6 mb-2">
                    <label for="filtro_ano_res" class="fw-bold">Año del Paquete:</label>
                    <select name="res_ano" id="filtro_ano_res" class="form-control">
                        <option value="todos">Todos los años</option>
                        <?php
                        foreach ($unique_years as $ano_val) {
                            echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="res_identificador_titulos" class="fw-bold">Identificador de Solicitud:</label>
                    <select name="cuadro_identificador_titulo" id="res_identificador_titulos" class="form-control" required>
                        <option value="">Selecciona un identificador</option>
                        <?php
                        foreach ($identificadores as $row_ident_res) {
                            echo '<option value="' . htmlspecialchars($row_ident_res['identificador']) . '" data-ano="' . substr($row_ident_res['identificador'], 0, 4) . '">'
                                . htmlspecialchars($row_ident_res['identificador']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 text-primary">Datos de la Resolución (Opcionales)</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="num_resolucion">Número de resolución:</label>
                    <input type="text" name="num_resolucion" id="num_resolucion" class="form-control" placeholder="Ej: 045">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_resolucion">Fecha de la resolución:</label>
                    <input type="date" name="fecha_resolucion" id="fecha_resolucion" class="form-control">
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="nombre_vicerrector">Firma (Vicerrector/a):</label>
                    <input type="text" name="nombre_vicerrector" id="nombre_vicerrector" class="form-control" value="AIDA PATRICIA GONZÁLEZ NIEVA" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="genero_vicerrector">Género:</label>
                    <select name="genero_vicerrector" id="genero_vicerrector" class="form-control" required>
                        <option value="F">Femenino</option>
                        <option value="M">Masculino</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <label for="nombre_reviso">Revisó:</label>
                    <input type="text" name="nombre_reviso" id="nombre_reviso" class="form-control" value="Marjhory Castro" required>
                </div>
                <div class="col-md-6 mb-4">
                    <label for="nombre_elaboro">Elaboró:</label>
                    <input type="text" name="nombre_elaboro" id="nombre_elaboro" class="form-control" value="Elizete Rivera" required>
                </div>
            </div>

            <input type="submit" value="Generar Resoluciones Word" class="btn btn-info text-white w-100 fs-5">
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#titulos').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json' }
        });

        $("#openModal").click(function() { $("#modal").css("display", "block"); });
        $("#openModalCuadros").click(function() { $("#modalCuadros").css("display", "block"); });
        $('#openModalResolucionesTitulos').click(function() { $('#modalResolucionesTitulos').show(); });

        $(".close-xls").click(function() { $("#modal").css("display", "none"); });
        $(".close-cuadros").click(function() { $("#modalCuadros").css("display", "none"); });
        $('.close-resoluciones-titulos').click(function() { $('#modalResolucionesTitulos').hide(); });

        $(window).click(function(event) {
            if ($(event.target).is(".modal")) {
                $(".modal").css("display", "none");
            }
        });

        // Filtrado por año (Resoluciones)
        $('#filtro_ano_res').on('change', function() {
            var anoSeleccionado = $(this).val();
            $('#res_identificador_titulos').val(""); 
            
            $('#res_identificador_titulos option').each(function() {
                var optionAno = $(this).data('ano');
                if (anoSeleccionado === "todos" || $(this).val() === "") {
                    $(this).show();
                } else {
                    if (optionAno == anoSeleccionado) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                }
            });
        });
        
        // Filtrado por año (Cuadros)
        $('#cuadro_ano').on('change', function() {
            var anoSeleccionado = $(this).val();
            $('#cuadro_identificador_solicitud').val(""); 
            
            $('#cuadro_identificador_solicitud option').each(function() {
                var optionAno = $(this).data('ano');
                if (anoSeleccionado === "" || $(this).val() === "") {
                    $(this).show();
                } else {
                    if (optionAno == anoSeleccionado) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                }
            });
        });

        $(".delete-btn").click(function() {
            var id = $(this).data("id");
            if (confirm('¿Estás seguro de eliminar la solicitud con ID ' + id + '?')) {
                alert('Aquí va tu código para eliminar la solicitud ' + id);
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>