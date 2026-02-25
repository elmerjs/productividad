<?php
// Requerir la conexión a la base de datos
include_once('conn.php');

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
     GROUP_CONCAT(
            DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero)
            ORDER BY t.documento_tercero
            SEPARATOR '\n'
        ) AS DETALLES_PROFESORES,s.id_solicitud_articulo,
    s.titulo_articulo AS `NOMBRE DEL PRODUCTO`,
    s.tipo_articulo AS `TIPO DE ARTICULO`,
    s.tipo_revista AS `TIPO REVISTA`,
    s.nombre_revista AS `NOMBRE REVISTA`,
    s.issn AS `ISSN`, s.estado_solicitud as estado_solicitud,
    s.identificador_solicitud, s.numero_oficio, s.id_solicitud_articulo as ID_S, s.puntaje
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

// Realizar la consulta para obtener los identificadores de solicitud
//$identificadores_sql = "SELECT DISTINCT identificador_solicitud FROM solicitud";
//$identificadores_result = $conn->query($identificadores_sql);
$identificadores_result = $conn->query("SELECT DISTINCT identificador_solicitud FROM solicitud"); // Reemplaza con tu consulta original
while ($row = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $row;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Solicitudes Revistas</title>

    <!-- Incluir Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Incluir jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Incluir los estilos de DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

    <!-- Incluir los scripts de DataTables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <style>
        /* Estilos personalizados para el modal */
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
    </style>
</head>
<body>

 <div class="container-fluid mt-4">
        <h1>Listado de Solicitudes Revistas Indexadas</h1>
   <!-- Botón para abrir el modal -->
       <div class="mb-3">
            <button id="openModal" class="btn btn-primary">Generar XLS</button>
            <button id="openModalCuadros" class="btn btn-secondary">Generar Cuadros</button>
            <button id="openModalResoluciones" class="btn btn-info text-white">Generar Resoluciones</button> 
        </div>
        <!-- Tabla donde se mostrarán los datos -->
        <table id="revistas"  class="table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>IDENTIFICADOR</th>
                    <th>DEPARTAMENTO</th>                    <th>OFICIO</th>
                    
                    <th>PROFESOR(ES)</th>
                    <th>PRODUCTO</th>
                    <th>TIPO ARTICULO</th>
                    <th>TIPO REVISTA</th>
                    <th>NOMBRE REVISTA</th>
                    <th>ISSN</th>
                    <th>PUNTAJE</th>
                                            <th>ESTADO</th> <!-- Columna de Acciones -->

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
                    $facultadTruncada = strlen($facultad) > 20 ? substr($facultad, 0, 20) . "..." : $facultad;
                    $nombreProductoTruncado = strlen($nombreProducto) > 30 ? substr($nombreProducto, 0, 30) . "..." : $nombreProducto;
                    $nombreRevistaTruncado = strlen($nombreRevista) > 30 ? substr($nombreRevista, 0, 30) . "..." : $nombreRevista;
                    $nombresTruncados = strlen($nombres) > 20 ? substr($nombres, 0, 20) . "..." : $nombres;

                    // Mostrar los resultados de la tabla
                    echo '<tr>';
                    echo '<td>
                        <a href="reporte_articulo.php?id_solicitud_articulo=' . htmlspecialchars($row['id_solicitud_articulo']) . '" 
                           class="btn btn-link">
                           ' . htmlspecialchars($row['id_solicitud_articulo']) . '
                        </a>
                    </td>';                   
                    echo '<td>' . htmlspecialchars($row['identificador_solicitud']) . '</td>';
                    echo '<td class="facultad-truncate" title="' 
                    . htmlspecialchars($row['DEPARTAMENTO']) . ' - ' . htmlspecialchars($facultad) . '">'
                    . htmlspecialchars(substr($row['DEPARTAMENTO'], 0, 20)) . ' - ' . $facultadTruncada 
                    . '</td>';
                    echo '<td>' . htmlspecialchars($row['numero_oficio']) . '</td>';

                    echo '<td class="detalle-profesores" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">'
                     . htmlspecialchars(substr($row['DETALLES_PROFESORES'], 0, 20)) . (strlen($row['DETALLES_PROFESORES']) > 20 ? '...' : '')
                     . '</td>';                    echo '<td class="truncate" title="' . $nombreProducto . '">' . $nombreProductoTruncado . '</td>';
                    echo '<td>' . htmlspecialchars($row['TIPO DE ARTICULO']) . '</td>';
                    echo '<td>' . ucfirst(strtolower($row['TIPO REVISTA'])) . '</td>';
                    echo '<td class="truncate" title="' . $nombreRevista . '">' . $nombreRevistaTruncado . '</td>';
                    echo '<td>' . htmlspecialchars($row['ISSN']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['puntaje']) . '</td>';

                    echo '<td>' . htmlspecialchars($row['estado_solicitud']) . '</td>';

                    // Columna de acciones
                    echo '<td>';
echo '<a href="actualizar_articulo.php?id_solicitud=' . $row['ID_S'] . '" class="btn btn-warning btn-sm">Editar</a>';
echo '<button class="btn btn-danger btn-sm" onclick="confirmDeleteWithReason(' . $row['ID_S'] . ')">Eliminar</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>

     
        <!-- Modal para filtrar los datos -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_articulo_xlsx.php" method="GET">
            <label for="identificador_solicitud">Identificador de Solicitud:</label>
            <select name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident) {
                    echo '<option value="' . htmlspecialchars($row_ident['identificador_solicitud']) . '">' . htmlspecialchars($row_ident['identificador_solicitud']) . '</option>';
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

<!-- Modal para Generar Cuadros -->
<div id="modalCuadros" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_articulos.php" method="GET">
            
            <label for="cuadro_ano">Año:</label>
            <select name="cuadro_ano" id="cuadro_ano" class="form-control">
                <option value="">Selecciona un año</option>
                <?php
                // Generar los años únicos
                $anos = array_unique(array_map(function($row) {
                    return substr($row['identificador_solicitud'], 0, 4);
                }, $identificadores));

                // Ordenar los años (opcional)
                sort($anos);

                // Generar las opciones del select
                foreach ($anos as $ano) {
                    echo '<option value="' . htmlspecialchars($ano) . '">' . htmlspecialchars($ano) . '</option>';
                }
                ?>
            </select>
            
            <br><br>
            <label for="cuadro_identificador_solicitud">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident_cuadro) {
                    echo '<option value="' . htmlspecialchars($row_ident_cuadro['identificador_solicitud']) . '" data-ano="' . substr($row_ident_cuadro['identificador_solicitud'], 0, 4) . '">'
                        . htmlspecialchars($row_ident_cuadro['identificador_solicitud']) . '</option>';
                }
                ?>
            </select>
            
            <br><br>
            <input type="submit" value="Generar Cuadro" class="btn btn-secondary">
        </form>
    </div>
</div>
     

<div id="modalResoluciones" class="modal">
    <div class="modal-content">
        <span class="close close-resoluciones">&times;</span>
        <h2>Generar Resoluciones (Word)</h2>
        <form action="resoluciones_articulos.php" method="GET">
            <label for="res_ano">Año:</label>
            <select name="res_ano" id="res_ano" class="form-control">
                <option value="">Selecciona un año</option>
                <?php
                // Usamos la misma lógica de años que ya tienes
                foreach ($anos as $ano_val) {
                    echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                }
                ?>
            </select>
            <br>
            <label for="cuadro_identificador_solicitud">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_solicitud" id="res_identificador" class="form-control" required>
                <option value="">Selecciona un identificador</option>
                <?php
                foreach ($identificadores as $row_ident_res) {
                    echo '<option value="' . htmlspecialchars($row_ident_res['identificador_solicitud']) . '" data-ano="' . substr($row_ident_res['identificador_solicitud'], 0, 4) . '">'
                        . htmlspecialchars($row_ident_res['identificador_solicitud']) . '</option>';
                }
                ?>
            </select>
            <br>
            <input type="submit" value="Generar Resoluciones Word" class="btn btn-info text-white">
        </form>
    </div>
</div>  
     
    </div>

    <!-- Incluir Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // JavaScript para filtrar los identificadores por año
    document.getElementById('cuadro_ano').addEventListener('change', function () {
        const selectedYear = this.value; // Año seleccionado
        const identificadorSelect = document.getElementById('cuadro_identificador_solicitud');
        const opciones = identificadorSelect.querySelectorAll('option');

        // Mostrar todas las opciones si no se selecciona un año
        if (!selectedYear) {
            opciones.forEach(option => option.style.display = 'block');
            identificadorSelect.value = '';
            return;
        }

        // Filtrar opciones según el año seleccionado
        opciones.forEach(option => {
            if (option.dataset.ano === selectedYear || option.value === '') {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });

        // Reiniciar la selección
        identificadorSelect.value = '';
    });
</script>
<script>
    function confirmDeleteWithReason(id) {
        const confirmation = confirm("¿Estás seguro de que quieres eliminar esta solicitud?");
        if (confirmation) {
            const motivo = prompt("Por favor, indique el motivo de la anulación:");
            if (motivo && motivo.trim() !== "") {
                // Redirigir con el ID y el motivo como parámetros
                window.location.href = 'eliminar_solicitud.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
            }
        }
    }
</script>
<script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#revistas').DataTable({
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

        // Abrir el modal para generar Cuadros
        $("#openModalCuadros").click(function() {
            $("#modalCuadros").css("display", "block");
        });
         // Abrir el modal para generar Cuadros
        $("#openModalsol").click(function() {
            $("#modalsol").css("display", "block");
        });

        // Cerrar los modales
        $(".close").click(function() {
            $("#modal").css("display", "none");
        });

        $(".close-cuadros").click(function() {
            $("#modalCuadros").css("display", "none");
        });

          $(".close-cuadros").click(function() {
            $("#modalsol").css("display", "none");
        });
        
        // Cerrar los modales si se hace clic fuera de ellos
        $(window).click(function(event) {
            if ($(event.target).is("#modal")) {
                $("#modal").css("display", "none");
            }
            if ($(event.target).is("#modalCuadros")) {
                $("#modalCuadros").css("display", "none");
            }
             if ($(event.target).is("#modalsol")) {
                $("#modalsol").css("display", "none");
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
        
        
        // Abrir modal Resoluciones
    $("#openModalResoluciones").click(function() {
        $("#modalResoluciones").css("display", "block");
    });

    // Cerrar modal Resoluciones
    $(".close-resoluciones").click(function() {
        $("#modalResoluciones").css("display", "none");
    });

    // Filtro de años para el modal de resoluciones (opcional pero recomendado)
    document.getElementById('res_ano').addEventListener('change', function () {
        const selectedYear = this.value;
        const selectIdent = document.getElementById('res_identificador');
        const opciones = selectIdent.querySelectorAll('option');
        opciones.forEach(opt => {
            opt.style.display = (!selectedYear || opt.dataset.ano === selectedYear || opt.value === '') ? 'block' : 'none';
        });
        selectIdent.value = '';
    });
        
    });
</script>
</body>
</html>
