<?php
// Requerir la conexión a la base de datos
include_once('conn.php');

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
f.nombre_fac_min as FACULTAD, d.depto_nom_propio as DEPARTAMENTO,
    tc.identificador AS `IDENTIFICADOR`, tc.numero_oficio, tc.id,
    tc.numero_oficio AS `NUMERO OFICIO`,
    tc.producto AS `PRODUCTO`,
    tc.difusion AS `DIFUSION`,
    tc.finalidad AS `FINALIDAD`,
    tc.area AS `AREA`,
     GROUP_CONCAT(
            DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero)
            ORDER BY t.documento_tercero
            SEPARATOR '\n'
        ) AS PROFESORES,
        tc.evaluador1 AS `EVALUADOR 1`,
    tc.evaluador2 AS `EVALUADOR 2`,
    tc.puntaje AS `PUNTAJE`,
    DATE_FORMAT(tc.fecha_solicitud_tr, '%Y-%m-%d %H:%i:%s') AS `FECHA SOLICITUD`, tc.estado_cient AS ESTADO,
    tc.tipo_productividad AS `TIPO DE PRODUCTIVIDAD`
FROM 
    trabajos_cientificos tc
JOIN 
    trabajo_profesor tp ON tc.id = tp.id_trabajo_cientifico
LEFT JOIN 
    tercero t ON tp.profesor_id = t.documento_tercero
join deparmanentos  d on d.PK_DEPTO =  t.fk_depto
join  facultad f  on f.PK_FAC  = d.FK_FAC

WHERE 1 = 1 ";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND tc.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND tc.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID del trabajo científico
$sql .= " GROUP BY tc.id";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores de solicitud
//$identificadores_sql = "SELECT DISTINCT identificador_solicitud FROM solicitud";
//$identificadores_result = $conn->query($identificadores_sql);
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM trabajos_cientificos"); // Reemplaza con tu consulta original
while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Trabajos Científicos</title>

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
    width: 60%; /* Reduce el ancho del modal */
    max-width: 600px; /* Puedes agregar un valor máximo para pantallas grandes */
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
.container-fluid {
    overflow-x: auto; /* Permite el desplazamiento horizontal si la tabla es más ancha */
}

table {
    width: 100%; /* Asegúrate de que la tabla use el ancho completo del contenedor */
}

th, td {
    white-space: nowrap; /* Evita que el texto se desborde fuera de las celdas */
}


    </style>
    
</head>
<body>

<div class="container-fluid mt-4">
    <h1>Listado de Trabajos Científicos</h1>

    <!-- Botones de acciones -->
    <div class="mb-3">
        <button id="openModalb" class="btn btn-primary">Generar XLS</button>
<button id="openmodalCuadrosb" class="btn btn-secondary">Generar Cuadros</button>
    </div>

    <!-- Tabla donde se mostrarán los datos -->
    <table id="trabajosCientificosTable" class="display table table-striped table-bordered">
        <thead>
            <tr>
                   <th>ID</th>
                <th>IDENTIFICADOR</th>

                    <th>DEPARTAMENTO</th>
                <th>NUMERO OFICIO</th>
                    <th>PROFESORES</th>

                <th>PRODUCTO</th>
                <th>DIFUSION</th>
                <th>FINALIDAD</th>
                <th>AREA</th>
          <!--      <th>EVALUADOR 1</th>
                <th>EVALUADOR 2</th>-->
                <th>PUNTAJE</th>
                                <th>ESTADO</th>

                <th>ACCIONES</th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            // Mostrar los resultados de la consulta
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['IDENTIFICADOR']) . '</td>';
          echo '<td class="facultad-truncate" title="' 
                    . htmlspecialchars($row['DEPARTAMENTO']) . ' - ' . htmlspecialchars($facultad) . '">'
                    . htmlspecialchars(substr($row['DEPARTAMENTO'], 0, 20)) . ' - ' . $facultadTruncada 
                    . '</td>';
                echo '<td>' . htmlspecialchars($row['NUMERO OFICIO']) . '</td>';
echo '<td class="detalle-profesores" title="' . htmlspecialchars($row['PROFESORES']) . '">'
                     . htmlspecialchars(substr($row['PROFESORES'], 0, 20)) . (strlen($row['PROFESORES']) > 20 ? '...' : '')
                     . '</td>';                    
                echo '<td>' . htmlspecialchars($row['PRODUCTO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['DIFUSION']) . '</td>';
                echo '<td>' . htmlspecialchars($row['FINALIDAD']) . '</td>';
                echo '<td>' . htmlspecialchars($row['AREA']) . '</td>';
            //    echo '<td>' . htmlspecialchars($row['EVALUADOR 1']) . '</td>';
            //    echo '<td>' . htmlspecialchars($row['EVALUADOR 2']) . '</td>';
                echo '<td>' . htmlspecialchars($row['PUNTAJE']) . '</td>';
          echo '<td>' . ($row['ESTADO']) . '</td>';
                
                echo '<td>';
                
                
echo '<a href="editar_trabajo_cient.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a>';
echo '<button class="btn btn-danger btn-sm" onclick="confirmDeleteWithReason(' . $row['id'] . ')">Eliminar</button>';
                    echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

<!-- Modal para exportar a Excel -->
<div id="modalb" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_trabajo_cient.php" method="GET">
            <label for="identificador_solicitud">Identificador de Solicitud:</label>
            <select name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
              foreach ($identificadores as $row_ident) {
    if (!empty($row_ident['identificador'])) {
        echo '<option value="' . htmlspecialchars($row_ident['identificador']) . '">' . htmlspecialchars($row_ident['identificador']) . '</option>';
    }
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
<div id="modalCuadrosb" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_trabajo_c.php" method="GET">
            <label for="cuadro_identificador_solicitud">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
              foreach ($identificadores as $row_ident_cuadro) {
                if (!empty($row_ident_cuadro['identificador'])) {
                    echo '<option value="' . htmlspecialchars($row_ident_cuadro['identificador']) . '">' . htmlspecialchars($row_ident_cuadro['identificador']) . '</option>';
                }
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
</div>
    <script>
    function confirmDeleteWithReason(id) {
        const confirmation = confirm("¿Estás seguro de que quieres eliminar esta solicitud?");
        if (confirmation) {
            const motivo = prompt("Por favor, indique el motivo de la anulación:");
            if (motivo && motivo.trim() !== "") {
                // Redirigir con el ID y el motivo como parámetros
                window.location.href = 'eliminar_solicitud_cient.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
            }
        }
    }
</script>
<script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#example').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });

        // Abrir el modal para generar XLS
        $("#openModalb").click(function() {
            $("#modalb").css("display", "block");
        });

        // Abrir el modal para generar Cuadros
        $("#openmodalCuadrosb").click(function() {
            $("#modalCuadrosb").css("display", "block");
        });

        // Cerrar los modales
        $(".close").click(function() {
            $("#modalb").css("display", "none");
        });

        $(".close-cuadros").click(function() {
            $("#modalCuadrosb").css("display", "none");
        });

        // Cerrar los modales si se hace clic fuera de ellos
        $(window).click(function(event) {
            if ($(event.target).is("#modal")) {
                $("#modalb").css("display", "none");
            }
            if ($(event.target).is("#modalCuadrosb")) {
                $("#modalCuadrosb").css("display", "none");
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
