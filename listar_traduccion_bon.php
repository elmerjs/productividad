    <?php
    // Incluir el archivo de conexión
    include_once('conn.php'); // Asegúrate de que la ruta sea correcta

    // Obtener los filtros desde el formulario (si existen)
    $identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
    $numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;
// Consulta SQL
// Consulta SQL
$sql = "
    SELECT 
    t.id AS id,
    t.identificador AS identificador,
    t.numero_oficio AS numero_oficio,
    t.fecha_solicitud AS fecha_solicitud,
    t.producto AS producto,
    t.numero_profesores AS numero_profesores,
    t.puntaje AS puntaje,
    t.estado AS estado,
    t.tipo_productividad AS tipo_productividad,

    -- Facultad y Departamento
    f.nombre_fac_min AS facultad,
    d.depto_nom_propio AS departamento,

    -- Concatenar los detalles de los profesores
    GROUP_CONCAT(
        DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
        ORDER BY ter.documento_tercero
        SEPARATOR '\\n'
    ) AS detalles_profesores

FROM 
    traduccion_bon t
JOIN 
    traduccion_bon_profesor tp ON tp.id_traduccion = t.id
JOIN 
    tercero ter ON tp.id_profesor = ter.documento_tercero
JOIN 
    deparmanentos d ON ter.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC

WHERE 1 = 1
";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND t.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND t.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

$sql .= " 
GROUP BY 
    t.id, t.identificador, t.numero_oficio, t.fecha_solicitud, t.producto, 
    t.numero_profesores, t.puntaje, t.estado, t.tipo_productividad, f.nombre_fac_min, d.depto_nom_propio

ORDER BY 
    t.fecha_solicitud;
";


    // Ejecutar la consulta
    $result = $conn->query($sql);

    // Realizar la consulta para obtener los identificadores de título
    $identificadores_result = $conn->query("SELECT DISTINCT identificador FROM traduccion_bon");
    $identificadores = [];
    while ($row = $identificadores_result->fetch_assoc()) {
        $identificadores[] = $row;
    }


    // Crear un array para almacenar los datos
    $data = array();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    // Cerrar la conexión
   // $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tabla de traduccion_bonificación</title>
        <!-- Incluir los archivos CSS de DataTables -->

       <!-- Incluir Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Incluir jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Incluir los estilos de DataTables -->
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

        <!-- Incluir los scripts de DataTables -->
        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>    <!-- Estilos personalizados -->
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

            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                padding: 8px;
                text-align: center;
                border: 1px solid #ddd;
            }
            th {
                background-color: #004080;
                color: white;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
        </style>
    </head>
    <body><div class="container-fluid mt-4">

        <h1>Lista de traducciones por bonificación(bonificación)</h1>


        <!-- Botones de acciones -->
        <div class="mb-3">
        <button id="openModaltrb" class="btn btn-primary">Generar XLS</button>
            <button id="openModalCuadrostrb" class="btn btn-secondary">Generar Cuadros</button>
        </div>
   <table id="traduccionBonTable" class="table-striped">
    <thead>
        <tr> <th>Facultad</th>
            <th>Departamento</th>
            <th>Identificador</th>
            <th>Número de Oficio</th>
            <th>Profesores</th>

            <th>Fecha de Solicitud</th>
            <th>Producto</th>
            <th>Puntaje</th>
            <th>Estado</th>
           
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr><td title="<?php echo $row['facultad']; ?>">
    <?php
        // Eliminar "Facultad de " del texto
        $facultad = str_replace("Facultad de ", "", $row['facultad']);
        // Mostrar solo los primeros 15 caracteres
        echo substr($facultad, 0, 15) . (strlen($facultad) > 15 ? '...' : '');
    ?>
</td>
<td title="<?php echo $row['departamento']; ?>">
    <?php
        // Mostrar solo los primeros 15 caracteres del departamento
        $departamento = $row['departamento'];
        echo substr($departamento, 0, 15) . (strlen($departamento) > 15 ? '...' : '');
    ?>
</td>
                <td><?php echo $row['identificador']; ?></td>
                <td><?php echo $row['numero_oficio']; ?></td>
                                <td><?php echo nl2br($row['detalles_profesores']); ?></td>

                <td><?php echo $row['fecha_solicitud']; ?></td>
                <td><?php echo $row['producto']; ?></td>
                <td><?php echo $row['puntaje']; ?></td>
                <td><?php echo $row['estado']; ?></td>
                
                <td>
                    <!-- Botones para editar o eliminar -->
                    <button class="edit-btn btn btn-warning btn-sm" data-id="<?php echo $row['id']; ?>">Editar</button>
                    <button class="delete-btn btn btn-danger btn-sm" data-id="<?php echo $row['id']; ?>">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    <!-- Modal para exportar a Excel -->
        <!-- Modal para filtrar los datos -->
<div id="modaltrb" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_traduccion_bon.php" method="GET">
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
    <div id="modalCuadrostrb" class="modal">
        <div class="modal-content">
            <span class="close close-cuadros">&times;</span>
            <h2>Filtrar para Generar Cuadros</h2>
            <form action="cuadros_traduccion_bon.php" method="GET">
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
        </div></div>



        <!-- Incluir los archivos JS de jQuery y DataTables -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

        <script>
            $(document).ready(function() {
            // Inicializar DataTable
            $('#traduccionBonTable').DataTable({
                responsive: true, // Hace la tabla responsive


        dom: 'Bfrtip', // Incluye los botones de exportación
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
        }
            });

            // Abrir el modal para generar XLS
            $("#openModaltrb").click(function() {
                $("#modaltrb").css("display", "block");
            });

            // Abrir el modal para generar Cuadros
            $("#openModalCuadrostrb").click(function() {
                $("#modalCuadrostrb").css("display", "block");
            });

            // Cerrar los modales
            $(".close").click(function() {
                $("#modal").css("display", "none");
            });

            $(".close-cuadrostrb").click(function() {
                $("#modalCuadrostrb").css("display", "none");
            });

            // Cerrar los modales si se hace clic fuera de ellos
            $(window).click(function(event) {
                if ($(event.target).is("#modaltrb")) {
                    $("#modaltrb").css("display", "none");
                }
                if ($(event.target).is("#modalCuadrostrb")) {
                    $("#modalCuadrostrb").css("display", "none");
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