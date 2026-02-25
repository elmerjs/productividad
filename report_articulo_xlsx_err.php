<?php
// Requerir la conexión a la base de datos
require 'conn.php';

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
    s.titulo_articulo AS `NOMBRE DEL PRODUCTO`,
    s.tipo_articulo AS `TIPO DE ARTICULO`,
    s.tipo_revista AS `TIPO REVISTA`,
    s.nombre_revista AS `NOMBRE REVISTA`,
    s.issn AS `ISSN`,
    s.identificador_solicitud
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Solicitudes</title>

    <!-- Incluir el CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Incluir jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Incluir los estilos de DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

    <!-- Incluir los scripts de DataTables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 100%;
            margin: 0 auto;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Estilo para truncar el texto */
        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px; /* Ajusta el tamaño según lo necesario */
        }

        /* Estilo al pasar el ratón para mostrar el texto completo */
        .truncate:hover {
            text-overflow: unset;
            overflow: visible;
            white-space: normal;
            background-color: #f0f0f0;
            position: relative;
            z-index: 10;
        }

        .facultad-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px; /* Ajusta el tamaño según lo necesario */
        }

        .facultad-truncate:hover {
            text-overflow: unset;
            overflow: visible;
            white-space: normal;
            background-color: #f0f0f0;
            position: relative;
            z-index: 10;
        }

        .nombre-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px; /* Ajusta el tamaño según lo necesario */
        }

        .nombre-truncate:hover {
            text-overflow: unset;
            overflow: visible;
            white-space: normal;
            background-color: #f0f0f0;
            position: relative;
            z-index: 10;
        }

        /* Estilos para el modal */
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
            width: 80%;
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
    </style>
</head>
<body>

    <h1>Listado de Solicitudes</h1>

    <!-- Tabla donde se mostrarán los datos -->
    <table id="example" class="display">
        <thead>
            <tr>
                <th>FACULTAD</th>
                <th>DEPARTAMENTO</th>
                <th>CEDULA</th>
                <th>NOMBRES</th>
                <th>NOMBRE DEL PRODUCTO</th>
                <th>TIPO DE ARTICULO</th>
                <th>TIPO REVISTA</th>
                <th>NOMBRE REVISTA</th>
                <th>ISSN</th>
                <th>ACCIONES</th> <!-- Columna de Acciones -->
            </tr>
        </thead>
        <tbody>
            <?php
            // Mostrar los resultados de la consulta
            while ($row = $result->fetch_assoc()) {
                // Eliminar la primera parte "Facultad de "
                $facultad = str_replace("Facultad de ", "", $row['FACULTAD']);

                // Truncar los nombres y productos
                $nombreProducto = htmlspecialchars($row['NOMBRE DEL PRODUCTO']);
                $nombreRevista = htmlspecialchars($row['NOMBRE REVISTA']);
                $nombres = htmlspecialchars($row['NOMBRES']);

                // Truncar texto largo
                $facultadTruncada = strlen($facultad) > 30 ? substr($facultad, 0, 30) . "..." : $facultad;
                $nombreProductoTruncado = strlen($nombreProducto) > 30 ? substr($nombreProducto, 0, 30) . "..." : $nombreProducto;
                $nombreRevistaTruncado = strlen($nombreRevista) > 30 ? substr($nombreRevista, 0, 30) . "..." : $nombreRevista;
                $nombresTruncados = strlen($nombres) > 20 ? substr($nombres, 0, 20) . "..." : $nombres;

                // Mostrar los resultados de la tabla
                echo '<tr>';
                echo '<td class="facultad-truncate" title="' . $facultad . '">' . $facultadTruncada . '</td>';
                echo '<td>' . htmlspecialchars($row['DEPARTAMENTO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['CEDULA']) . '</td>';
                echo '<td class="nombre-truncate" title="' . $nombres . '">' . $nombresTruncados . '</td>';
                echo '<td class="truncate" title="' . $nombreProducto . '">' . $nombreProductoTruncado . '</td>';
                echo '<td>' . htmlspecialchars($row['TIPO DE ARTICULO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['TIPO REVISTA']) . '</td>';
                echo '<td class="truncate" title="' . $nombreRevista . '">' . $nombreRevistaTruncado . '</td>';
                echo '<td>' . htmlspecialchars($row['ISSN']) . '</td>';
                // Columna de acciones con botones estilizados de Bootstrap
                echo '<td>';
                echo '<button class="btn btn-warning btn-sm edit-btn" data-id="' . $row['identificador_solicitud'] . '">Editar</button>';
                echo ' ';
                echo '<button class="btn btn-danger btn-sm delete-btn" data-id="' . $row['identificador_solicitud'] . '">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Botón para abrir el modal -->
    <button id="openModal" class="btn btn-primary">Generar XLS</button>

    <!-- Modal para filtrar los datos -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Filtrar por Solicitud o Año</h2>
            <form action="report_excel.php" method="GET">
                <label for="identificador_solicitud">Identificador de Solicitud:</label>
                <input type="text" name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <br><br>
                <label for="ano">Año:</label>
                <input type="number" name="ano" id="ano" class="form-control">
                <br><br>
                <input type="submit" value="Generar Reporte" class="btn btn-success">
            </form>
        </div>
    </div>

    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#example').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
                }
            });

            // Abrir el modal para generar XLS
            $("#openModal").click(function() {
                $("#modal").css("display", "block");
            });

            // Cerrar el modal
            $(".close").click(function() {
                $("#modal").css("display", "none");
            });

            // Cerrar el modal si se hace clic fuera del modal
            $(window).click(function(event) {
                if ($(event.target).is("#modal")) {
                    $("#modal").css("display", "none");
                }
            });

            // Acciones de editar y eliminar
            $(".edit-btn").click(function() {
                var id = $(this).data("id");
                alert("Editar solicitud con ID: " + id);
                // Aquí puedes implementar el código para editar
            });

            $(".delete-btn").click(function() {
                var id = $(this).data("id");
                var confirmDelete = confirm("¿Seguro que quieres eliminar esta solicitud?");
                if (confirmDelete) {
                    // Lógica para eliminar
                    alert("Eliminar solicitud con ID: " + id);
                }
            });
        });
    </script>

</body>
</html>
