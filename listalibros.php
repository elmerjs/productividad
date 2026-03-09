<?php
// Requerir la conexión a la base de datos
include_once  'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    f.nombre_fac_min AS `FACULTAD`, l.id_libro,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    l.numero_oficio AS `NUMERO_OFICIO`,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    GROUP_CONCAT(
        DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero)
        ORDER BY t.documento_tercero
        SEPARATOR '\n'
    ) AS `DETALLES_PROFESORES`,
    l.identificador AS `IDENTIFICADOR`,
    l.tipo_libro AS `TIPO DE LIBRO`,
    l.producto AS `NOMBRE DEL PRODUCTO`,
    l.isbn AS `ISBN`,
    l.mes_ano_edicion AS `MES Y AÑO`,
    l.nombre_editorial AS `EDITORIAL`,
    l.tiraje AS `TIRAJE`,
    l.numero_profesores AS `NUMERO_PROFESORES`,
    l.autores AS `NUMERO_AUTORES`,
    l.evaluacion_1 AS `EVALUACION 1`,
    l.evaluacion_2 AS `EVALUACION 2`,
    l.calculo AS `CALCULO`,
    l.puntaje_final AS `PUNTAJE_FINAL`,
    l.estado AS `ESTADO`,
    l.tipo_productividad AS `TIPO_PRODUCTIVIDAD`,
    l.obs_libro AS `OBSERVACIONES`
FROM 
    libros l
JOIN 
    libro_profesor lp ON lp.id_libro = l.id_libro
JOIN 
    tercero t ON lp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 
    1 = 1
";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND l.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(l.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar y ordenar
$sql .= " GROUP BY l.id_libro ORDER BY l.id_libro DESC";
$result = $conn->query($sql);

// --- EXTRACCIÓN DE IDENTIFICADORES Y AÑOS PARA EL MODAL ---
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM libros ORDER BY identificador DESC");
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
    <title>Listado de Libros</title>

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
    .close {
        color: #aaa; 
        float: right; 
        font-size: 28px; 
        font-weight: bold;
    }
    .close:hover, .close:focus {
        color: black; 
        text-decoration: none; 
        cursor: pointer;
    }
    .limited-text {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
</head>
<body>
<div class="container-fluid mt-4">
    <h1>Listado de Libros</h1>

    <div class="mb-3">
        <button id="openModal" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadros" class="btn btn-secondary">Generar Cuadros</button>
        <button id="openModalResolucionesLibros" class="btn btn-info text-white">Generar Resoluciones</button>
    </div>

    <table id="libros" class="display table table-striped table-bordered">
        <thead>
            <tr> 
                <th>ID</th> 
                <th>IDENTIFICADOR</th>
                <th>DEPARTAMENTO</th>
                <th>NUMERO OFICIO</th>
                <th>NOMBRES</th>
                <th>PRODUCTO</th>
                <th>PUNTAJE FINAL</th>
                <th>ESTADO</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Mostrar los resultados de la consulta
            while ($row = $result->fetch_assoc()) {
                
                $facultad = $row['FACULTAD'];
                $facultadTruncada = strlen($facultad) > 20 ? substr($facultad, 0, 20) . '...' : $facultad;

                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id_libro']) . '</td>';
                echo '<td>' . htmlspecialchars($row['IDENTIFICADOR']) . '</td>';
                
                echo '<td class="facultad-truncate" title="' 
                    . htmlspecialchars($row['DEPARTAMENTO']) . ' - ' . htmlspecialchars($facultad) . '">'
                    . htmlspecialchars(substr($row['DEPARTAMENTO'], 0, 20)) . ' - ' . $facultadTruncada 
                    . '</td>';

                echo '<td>' . htmlspecialchars($row['NUMERO_OFICIO']) . '</td>';
                
                echo '<td class="limited-text" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">'
                     . substr(htmlspecialchars($row['DETALLES_PROFESORES']), 0, 30) . (strlen($row['DETALLES_PROFESORES']) > 30 ? '...' : '') . '</td>';

                echo '<td>' . htmlspecialchars($row['NOMBRE DEL PRODUCTO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['PUNTAJE_FINAL']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ESTADO']) . '</td>';

                echo '<td>';
                echo '<a href="editar_libros.php?id=' . $row['id_libro'] . '" class="btn btn-warning btn-sm">Editar</a> ';
                echo '<button class="delete-btn btn btn-danger btn-sm" data-id="' . $row['id_libro'] . '">Eliminar</button>';
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
        <form action="report_libros.php" method="GET">
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
        <form action="cuadros_libros.php" method="GET">
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

<div id="modalResolucionesLibros" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close close-resoluciones-libros">&times;</span>
        <h2 class="mb-4">Generar Resoluciones de Libros (Word)</h2>
        <form action="resoluciones_libros.php" method="GET">
            
            <div class="row bg-light p-3 mb-3 border rounded">
                <div class="col-md-6 mb-2">
                    <label for="filtro_ano_libros" class="fw-bold">Año del Paquete:</label>
                    <select name="res_ano" id="filtro_ano_libros" class="form-control">
                        <option value="todos">Todos los años</option>
                        <?php
                        foreach ($unique_years as $ano_val) {
                            echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="res_identificador_libros" class="fw-bold">Identificador de Solicitud:</label>
                    <select name="cuadro_identificador_libro" id="res_identificador_libros" class="form-control" required>
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
        // Inicializar DataTable
        $('#libros').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });

        // Apertura de modales
        $("#openModal").click(function() { $("#modal").css("display", "block"); });
        $("#openModalCuadros").click(function() { $("#modalCuadros").css("display", "block"); });
        $('#openModalResolucionesLibros').click(function() { $('#modalResolucionesLibros').css("display", "block"); });

        // Cierre de modales
        $(".close-xls").click(function() { $("#modal").css("display", "none"); });
        $(".close-cuadros").click(function() { $("#modalCuadros").css("display", "none"); });
        $('.close-resoluciones-libros').click(function() { $('#modalResolucionesLibros').css("display", "none"); });

        // Cerrar si se hace clic fuera de la caja
        $(window).click(function(event) {
            if ($(event.target).is(".modal")) {
                $(".modal").css("display", "none");
            }
        });

        // Evento de filtrado por año para Resoluciones Libros
        $('#filtro_ano_libros').on('change', function() {
            var anoSeleccionado = $(this).val();
            
            $('#res_identificador_libros').val(""); // Resetear la selección
            
            $('#res_identificador_libros option').each(function() {
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

        // Evento eliminar 
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