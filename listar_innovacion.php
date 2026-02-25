<?php
// Requerir la conexión a la base de datos
include_once  'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    t.id_innovacion AS id,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numero_oficio, t.identificador,
    t.fecha_solicitud,
    t.producto,
    t.impacto,
    t.puntaje AS puntaje_final,
    
    -- Concatenar detalles de profesores solo para el mismo id_innovacion
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
        ORDER BY ter.documento_tercero
        SEPARATOR '\n'
    ) AS `DETALLES_PROFESORES`
FROM 
    innovacion t
JOIN 
    innovacion_profesor tp ON tp.id_innovacion = t.id_innovacion
JOIN 
    tercero ter ON tp.id_profesor = ter.documento_tercero
JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID de la innovación
$sql .= " GROUP BY 
    t.id_innovacion, f.nombre_fac_min, d.depto_nom_propio, t.identificador, 
    t.numero_oficio, t.fecha_solicitud, t.producto, t.impacto, t.puntaje
ORDER BY 
    f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores de solicitud
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM innovacion"); // Reemplaza con tu consulta original
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
    <title>Listado de Innovaciones</title>

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
        /* Estilos personalizados para el modal */
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

        /* Estilos para limitar la anchura de las columnas y el texto */
        .limited-text {
            max-width: 200px; /* Ajusta el ancho según tu preferencia */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <h1>Listado de Innovaciones</h1>

        <!-- Botones de acciones -->
        <div class="mb-3">
        <button id="openModalin" class="btn btn-primary">Generar XLS</button>
            <button id="openModalCuadrosin" class="btn btn-secondary">Generar Cuadros</button>
        </div>

        <!-- Tabla donde se mostrarán los datos -->
        <table id="innovacion" class="display table table-striped table-bordered">
            <thead>
                <tr>                    <th>FACULTAD</th>
                    <th>DEPARTAMENTO</th>
                    <th>IDENTIFICADOR</th>

                    <th>NUMERO OFICIO</th>
                    <th>PROFESOR(ES)</th>
                    <th>PRODUCTO</th>
                    <th>PUNTAJE</th>
                    <th>ACCIONES</th>
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

                    // Columna PROFESOR(ES) con texto limitado y título completo
                    echo '<td class="limited-text" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">'
                         . substr(htmlspecialchars($row['DETALLES_PROFESORES']), 0, 30) . (strlen($row['DETALLES_PROFESORES']) > 30 ? '...' : '') . '</td>';

                    echo '<td>' . htmlspecialchars($row['producto']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['puntaje_final']) . '</td>';
                    echo '<td>';
echo '<a href="editar_innovacion.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a>';
                    echo '<button class="delete-btn btn btn-danger btn-sm" data-id="' . $row['id'] . '">Eliminar</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
</div>
 <!-- Modal para exportar a Excel -->
<div id="modalin" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_innovacion.php" method="GET">
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
<div id="modalCuadrosin" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_innovacion.php" method="GET">
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

<script>
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

        // Abrir el modal para generar XLS
        $("#openModalin").click(function() {
            $("#modalin").css("display", "block");
        });

        // Abrir el modal para generar Cuadros
        $("#openModalCuadrosin").click(function() {
            $("#modalCuadrosin").css("display", "block");
        });

        // Cerrar los modales
        $(".close").click(function() {
            $("#modalin").css("display", "none");
        });

        $(".close-cuadros").click(function() {
            $("#modalCuadrosin").css("display", "none");
        });

        // Cerrar los modales si se hace clic fuera de ellos
        $(window).click(function(event) {
            if ($(event.target).is("#modal")) {
                $("#modal").css("display", "none");
            }
            if ($(event.target).is("#modalCuadrosin")) {
                $("#modalCuadrosin").css("display", "none");
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
    });
</script>

<!-- Incluir Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
