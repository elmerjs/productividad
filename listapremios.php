<?php
// Requerir la conexión a la base de datos
include_once  'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    p.id,
    p.identificador,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`, 
    p.numero_oficio,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    p.nombre_evento AS `EVENTO_PREMIO`,
    p.ambito AS `AMBITO`,
    p.categoria_premio AS `CATEGORIA_PREMIO`,
    p.nivel_ganado AS `NIVEL_GANADO`,
    p.lugar_fecha AS `LUGAR_Y_FECHA`, 
    p.estado,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`,
    p.numero_oficio AS `OFICIO`, 
    p.puntos
FROM 
    premios p 
JOIN 
    premios_profesor pp ON pp.id_premio = p.id
JOIN 
    tercero t ON pp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 
    1 = 1
";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND p.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(p.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados
$sql .= " GROUP BY p.id, f.nombre_fac_min, d.depto_nom_propio, p.nombre_evento, p.ambito, p.categoria_premio, p.nivel_ganado, p.lugar_fecha, p.numero_oficio";
$sql .= " ORDER BY p.id DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

// Obtener los identificadores de solicitud para los filtros
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM premios ORDER BY identificador DESC");
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
    <title>Listado de Premios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <style>
        .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.5); padding-top: 60px; }
        .modal-content { background-color: #fefefe; margin: 2% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px;}
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .limited-text { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <h1>Listado de Premios</h1>
    
    <div class="mb-3">
        <button id="openModalpr" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadrospr" class="btn btn-secondary">Generar Cuadros</button>
        <button id="openModalResolucionespr" class="btn btn-info text-white">Generar Resoluciones</button>
    </div>

    <table id="premios" class="display table table-striped table-bordered">
        <thead>
            <tr>                
                <th>ID</th>
                <th>IDENTIFICADOR</th>
                <th>DEPARTAMENTO</th>
                <th>OFICIO</th>
                <th>NOMBRES</th>
                <th>EVENTO PREMIO</th>
                <th>AMBITO</th>
                <th>CATEGORIA PREMIO</th>
                <th>NIVEL GANADO</th>
                <th>LUGAR Y FECHA</th>
                <th>PUNTAJE</th>
                <th>ESTADO</th>
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

                echo '<td class="limited-text" title="' . htmlspecialchars($row['DETALLES PROFESORES']) . '">'
                     . htmlspecialchars(substr($row['DETALLES PROFESORES'], 0, 20)) . (strlen($row['DETALLES PROFESORES']) > 20 ? '...' : '')
                     . '</td>';
                
                echo '<td>' . htmlspecialchars($row['EVENTO_PREMIO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['AMBITO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['CATEGORIA_PREMIO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['NIVEL_GANADO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['LUGAR_Y_FECHA']) . '</td>';
                echo '<td>' . htmlspecialchars($row['puntos']) . '</td>';
                echo '<td>' . htmlspecialchars($row['estado']) . '</td>';

                echo '<td>';
                echo '<a href="editar_premios.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a> ';
                echo '<button class="btn btn-danger btn-sm" onclick="confirmDeleteWithReason(' . $row['id'] . ')">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

    <div id="modalpr" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Filtrar por Solicitud o Año</h2>
            <form action="report_premios.php" method="GET">
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

    <div id="modalCuadrospr" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Filtrar para Generar Cuadros</h2>
            <form action="cuadros_premios.php" method="GET">
                <label for="cuadro_identificador">Identificador de Solicitud:</label>
                <select name="cuadro_identificador" id="cuadro_identificador" class="form-control">
                    <option value="">Selecciona un identificador</option>
                    <?php
                    foreach ($identificadores as $row_ident) {
                        echo '<option value="' . htmlspecialchars($row_ident['identificador']) . '">' . htmlspecialchars($row_ident['identificador']) . '</option>';
                    }
                    ?>
                </select>
                <br>
                <label for="cuadro_ano">Año:</label>
                <input type="number" name="cuadro_ano" id="cuadro_ano" class="form-control">
                <br><br>
                <input type="submit" value="Generar Cuadro" class="btn btn-secondary">
            </form>
        </div>
    </div>

    <div id="modalResolucionespr" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close">&times;</span>
            <h2 class="mb-4">Generar Resoluciones de Premios (Word)</h2>
            <form action="resoluciones_premios.php" method="GET">
                
                <div class="row bg-light p-3 mb-3 border rounded">
                    <div class="col-md-6 mb-2">
                        <label for="filtro_ano_premios" class="fw-bold">Año del Paquete:</label>
                        <select id="filtro_ano_premios" class="form-control">
                            <option value="todos">Todos los años</option>
                            <?php
                            foreach ($unique_years as $ano_val) {
                                echo '<option value="' . htmlspecialchars($ano_val) . '">' . htmlspecialchars($ano_val) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="cuadro_identificador_premio" class="fw-bold">Identificador (Paquete):</label>
                        <select name="cuadro_identificador_premio" id="cuadro_identificador_premio" class="form-control" required>
                            <option value="">Selecciona un identificador</option>
                            <?php
                            foreach ($identificadores as $row_ident) {
                                echo '<option value="' . htmlspecialchars($row_ident['identificador']) . '" data-ano="' . substr($row_ident['identificador'], 0, 4) . '">'
                                    . htmlspecialchars($row_ident['identificador']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <h5 class="mb-3 text-primary">Datos de la Resolución (Opcionales)</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="num_resolucion">Número de resolución (Inicial):</label>
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

</div>

<script>
    function confirmDeleteWithReason(id) {
        const confirmation = confirm("¿Estás seguro de que quieres eliminar esta solicitud?");
        if (confirmation) {
            const motivo = prompt("Por favor, indique el motivo de la anulación:");
            if (motivo && motivo.trim() !== "") {
                window.location.href = 'eliminar_solicitud_premio.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
            }
        }
    }
</script>

<script>
    $(document).ready(function() {
        $('#premios').DataTable({
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });
        
        // Control de los modales
        $('#openModalpr').on('click', function() { $('#modalpr').show(); });
        $('#openModalCuadrospr').on('click', function() { $('#modalCuadrospr').show(); });
        $('#openModalResolucionespr').on('click', function() { $('#modalResolucionespr').show(); }); 
        
        // Cerrar cualquier modal al hacer clic en la "X"
        $('.close').on('click', function() { $(this).closest('.modal').hide(); });
        
        // Cerrar si se hace clic fuera de la caja del modal
        $(window).click(function(event) {
            if ($(event.target).is(".modal")) {
                $(".modal").hide();
            }
        });

        // Evento de filtrado por año para el modal de Resoluciones
        $('#filtro_ano_premios').on('change', function() {
            var anoSeleccionado = $(this).val();
            
            $('#cuadro_identificador_premio').val(""); // Resetear la selección
            
            $('#cuadro_identificador_premio option').each(function() {
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
    });
</script>
</body>
</html>