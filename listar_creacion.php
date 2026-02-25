<?php
// Requerir la conexión a la base de datos
include_once  'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
   t.id,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    t.numeroOficio,
    t.fecha_solicitud,
    t.tipo_producto,    t.identificador_completo,
    t.impacto,
    t.producto,
    t.nombre_evento,
    t.evento,
    t.fecha_evento,
    t.lugar_evento,
    t.autores, 
    t.evaluacion1,
    t.evaluacion2,
    t.puntaje_final,t.estado_creacion,
    
    GROUP_CONCAT(DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero) ORDER BY ter.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    creacion t
JOIN 
    creacion_profesor tp ON tp.id_creacion = t.id
JOIN 
    tercero ter ON tp.documento_profesor = ter.documento_tercero
JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC

WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND t.identificador_completo = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numeroOficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID del trabajo científico
$sql .= " GROUP BY 
    t.id,  t.numeroOficio, t.fecha_solicitud, t.tipo_producto, t.impacto, t.producto, t.nombre_evento, t.evento, t.fecha_evento, t.lugar_evento, t.autores, t.evaluacion1, t.evaluacion2, t.puntaje_final
ORDER BY 
    f.nombre_fac_min, d.depto_nom_propio, t.fecha_solicitud
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores de solicitud
//$identificadores_sql = "SELECT DISTINCT identificador_solicitud FROM solicitud";
//$identificadores_result = $conn->query($identificadores_sql);
$identificadores_result = $conn->query("SELECT DISTINCT identificador_completo FROM creacion"); // Reemplaza con tu consulta original
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
    <title>Listado de Obra de Creación Artística</title>

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
<div class="container-fluid mt-4">
    <h1>Listado de Obra de Creación Artística</h1>

    <!-- Botones de acciones -->
    <div class="mb-3">
        <button id="openModalcr" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadroscr" class="btn btn-secondary">Generar Cuadros</button>
    </div>

    <!-- Tabla donde se mostrarán los datos -->
    <table id="creacion" class="display table table-striped table-bordered">
        <thead>
            <tr> <th>FACULTAD</th>
                <th>DEPARTAMENTO</th>
                <th>IDENTIFICADOR</th>
               
                <th>NUMERO OFICIO</th>
                <th>PROFESOR(ES)</th>
                <th>PRODUCTO</th>
                <th>PUNTAJE</th>                <th>ESTADO</th>

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
           
                 echo '<td>' . htmlspecialchars($row['identificador_completo']) . '</td>';
                
                echo '<td>' . htmlspecialchars($row['numeroOficio']) . '</td>';

                // Columna PROFESOR(ES) con texto limitado y título completo
                echo '<td class="limited-text" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">'
                     . substr(htmlspecialchars($row['DETALLES_PROFESORES']), 0, 30) . (strlen($row['DETALLES_PROFESORES']) > 30 ? '...' : '') . '</td>';

                echo '<td>' . htmlspecialchars($row['producto']) . '</td>';
                echo '<td>' . htmlspecialchars($row['puntaje_final']) . '</td>';
                echo '<td>' . htmlspecialchars($row['estado_creacion']) . '</td>';

                echo '<td>';
echo '<a href="editar_creacion_art.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a>';
                echo '<button class="delete-btn btn btn-danger btn-sm" data-id="' . $row['id'] . '">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>
<!-- Modal para exportar a Excel -->
<div id="modalcr" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_creacion.php" method="GET">
            <label for="identificador_solicitud">Identificador de Solicitud:</label>
            <select name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident) {
                    echo '<option value="' . htmlspecialchars($row_ident['identificador_completo']) . '">' . htmlspecialchars($row_ident['identificador_completo']) . '</option>';
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
<div id="modalCuadroscr" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_creacion.php" method="GET">
            <label for="cuadro_identificador_solicitud">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident_cuadro) {
                    echo '<option value="' . htmlspecialchars($row_ident_cuadro['identificador_completo']) . '">' . htmlspecialchars($row_ident_cuadro['identificador_completo']) . '</option>';
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
        $('#creacion').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });

        // Abrir el modal para generar XLS
        $("#openModalcr").click(function() {
            $("#modalcr").css("display", "block");
        });

        // Abrir el modal para generar Cuadros
        $("#openModalCuadroscr").click(function() {
            $("#modalCuadroscr").css("display", "block");
        });

        // Cerrar los modales
        $(".close").click(function() {
            $("#modal").css("display", "none");
        });

        $(".close-cuadros").click(function() {
            $("#modalCuadros").css("display", "none");
        });

        // Cerrar los modales si se hace clic fuera de ellos
        $(window).click(function(event) {
            if ($(event.target).is("#modalcr")) {
                $("#modalcr").css("display", "none");
            }
            if ($(event.target).is("#modalCuadroscr")) {
                $("#modalCuadroscr").css("display", "none");
            }
        });

        // Acciones de editar y eliminar
        $(".edit-btn").click(function() {
            var id = $(this).data("id");
            alert("Editar solicitud con ID: " + id);
        });

  $(document).on("click", ".delete-btn", function() {
        var id = $(this).data("id");
        console.log("ID a eliminar:", id); // Debug en consola

        var confirmDelete = confirm("¿Seguro que quieres eliminar esta solicitud?");
        if (confirmDelete) {
            $.ajax({
                url: 'eliminar_creacion.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload(); // Recargar la página para reflejar cambios
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("Error en la solicitud AJAX.");
                }
            });
        }
    });
    });
</script>

<!-- Incluir Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
