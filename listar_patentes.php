<?php
// Requerir la conexión a la base de datos
include_once 'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numero_oficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
   p.id_patente,
    f.nombre_fac_min AS `FACULTAD`,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    p.numero_oficio,
    p.identificador,
    p.fecha_solicitud,
    p.producto,
    p.numero_profesores,
    p.puntaje,
    p.estado,
    p.tipo_productividad,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES_PROFESORES`
FROM 
    patentes p
JOIN 
    patente_profesor pp ON pp.id_patente = p.id_patente
JOIN 
    tercero t ON pp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC

WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador)) {
    $sql .= " AND p.identificador = '" . $conn->real_escape_string($identificador) . "'";
}
if (!empty($numero_oficio)) {
    $sql .= " AND p.numero_oficio = '" . $conn->real_escape_string($numero_oficio) . "'";
}

// Agrupar los resultados por el ID
$sql .= " GROUP BY 
    p.id_patente, p.numero_oficio, p.fecha_solicitud, p.producto, p.numero_profesores, p.puntaje, p.estado, p.tipo_productividad
ORDER BY p.id_patente DESC";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores de solicitud y extraer los años
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM patentes ORDER BY identificador DESC"); 

$identificadores = [];
$unique_years = [];

while ($row = $identificadores_result->fetch_assoc()) {
    $id_str = $row['identificador'];
    $identificadores[] = $id_str;
    
    // Extraer los primeros 4 caracteres para obtener el año (Ej: 2025_02_1 -> 2025)
    $year = substr($id_str, 0, 4);
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
    <title>Listado de Patentes</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    
<style>
    .modal {
        display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%;
        overflow: auto; background-color: rgba(0, 0, 0, 0.4); padding-top: 60px;
    }
    .modal-content {
        background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888;
        width: 60%; max-width: 600px;
        border-radius: 8px;
    }
    .close {
        color: #aaa; float: right; font-size: 28px; font-weight: bold;
    }
    .close:hover, .close:focus {
        color: black; text-decoration: none; cursor: pointer;
    }
    .limited-text {
        max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
</style>
</head>
<body>
<div class="container-fluid mt-4">
    <h1>Listado de Patentes</h1>

    <div class="mb-3">
        <button id="openModalpt" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadrospt" class="btn btn-secondary">Generar Cuadros</button>
        <button id="openModalResolucionespt" class="btn btn-success">Generar Resoluciones</button>
    </div>

    <table id="patentes" class="display table table-striped table-bordered">
        <thead>
            <tr>  
                <th>ID</th>                
                <th>IDENTIFICADOR</th>
                <th>DEPARTAMENTO</th>
                <th>NUMERO OFICIO</th>
                <th>PROFESOR(ES)</th>
                <th>PRODUCTO</th>
                <th>PUNTAJE</th>  
                <th>ESTADO</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Mostrar los resultados de la consulta
            while ($row = $result->fetch_assoc()) {
                
                // Definir variables para evitar errores en el title
                $facultad = $row['FACULTAD'];
                $facultadTruncada = strlen($facultad) > 20 ? substr($facultad, 0, 20) . '...' : $facultad;

                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id_patente']) . '</td>';
                echo '<td>' . htmlspecialchars($row['identificador']) . '</td>';

                echo '<td class="facultad-truncate" title="' 
                    . htmlspecialchars($row['DEPARTAMENTO']) . ' - ' . htmlspecialchars($facultad) . '">'
                    . htmlspecialchars(substr($row['DEPARTAMENTO'], 0, 20)) . ' - ' . $facultadTruncada 
                    . '</td>';

                echo '<td>' . htmlspecialchars($row['numero_oficio']) . '</td>';

                echo '<td class="limited-text" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">'
                     . substr(htmlspecialchars($row['DETALLES_PROFESORES']), 0, 30) . (strlen($row['DETALLES_PROFESORES']) > 30 ? '...' : '') . '</td>';

                echo '<td>' . htmlspecialchars($row['producto']) . '</td>';
                echo '<td>' . htmlspecialchars($row['puntaje']) . '</td>';
                echo '<td>' . htmlspecialchars($row['estado']) . '</td>';

                echo '<td>';
                echo '<a href="editar_patentes.php?id=' . $row['id_patente'] . '" class="btn btn-warning btn-sm">Editar</a> ';
                echo '<button class="btn btn-danger btn-sm" onclick="confirmDeleteWithReason(' . $row['id_patente'] . ')">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<div id="modalpt" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_patentes.php" method="GET">
            <label for="ano_xls">Año:</label>
            <select name="ano" id="ano_xls" class="form-control mb-3">
                <option value="">Todos los años...</option>
                <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
            </select>

            <label for="identificador_solicitud">Identificador de Solicitud (Paquete):</label>
            <select name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <option value="">Todos los paquetes...</option>
                <?php
                 foreach ($identificadores as $id) {
                    echo '<option value="' . htmlspecialchars($id) . '" data-ano="' . substr($id, 0, 4) . '">' . htmlspecialchars($id) . '</option>';
                }
                ?>
            </select>
            <br>
            <input type="submit" value="Generar Reporte" class="btn btn-primary">
        </form>
    </div>
</div>

<div id="modalCuadrospt" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_patentes.php" method="GET">
            <label for="ano_cuadros">Año:</label>
            <select name="cuadro_ano" id="ano_cuadros" class="form-control mb-3">
                <option value="">Todos los años...</option>
                <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
            </select>

            <label for="cuadro_identificador_solicitud">Identificador de Solicitud (Paquete):</label>
            <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-control">
                <option value="">Todos los paquetes...</option>
                <?php
                foreach ($identificadores as $id) {
                    echo '<option value="' . htmlspecialchars($id) . '" data-ano="' . substr($id, 0, 4) . '">' . htmlspecialchars($id) . '</option>';
                }
                ?>
            </select>
            <br>
            <input type="submit" value="Generar Cuadro" class="btn btn-secondary">
        </form>
    </div>
</div>

<div id="modalResolucionespt" class="modal">
    <div class="modal-content">
        <span class="close close-resoluciones">&times;</span>
        <h2>Filtrar para Generar Resoluciones</h2>
        <form action="resoluciones_patentes.php" method="GET">
            <label for="ano_res">Filtro por Año:</label>
            <select id="ano_res" class="form-control mb-3">
                <option value="">Seleccione un año...</option>
                <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
            </select>

            <label for="cuadro_identificador_patente">Identificador de Solicitud (Paquete):</label>
            <select name="cuadro_identificador_patente" id="cuadro_identificador_patente" class="form-control" required>
                <option value="">Selecciona un paquete</option>
                <?php
                foreach ($identificadores as $id) {
                    echo '<option value="' . htmlspecialchars($id) . '" data-ano="' . substr($id, 0, 4) . '">' . htmlspecialchars($id) . '</option>';
                }
                ?>
            </select>
            <br>
            <input type="submit" value="Generar Resoluciones" class="btn btn-success">
        </form>
    </div>
</div>

<script>
    function confirmDeleteWithReason(id) {
        const confirmation = confirm("¿Estás seguro de que quieres eliminar esta solicitud?");
        if (confirmation) {
            const motivo = prompt("Por favor, indique el motivo de la anulación:");
            if (motivo && motivo.trim() !== "") {
                window.location.href = 'eliminar_solicitud_patente.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
            }
        }
    }

    // --- Función para filtrar los paquetes según el año seleccionado ---
    function applyYearFilter(yearSelectId, idSelectId) {
        const year = $('#' + yearSelectId).val();
        $('#' + idSelectId + ' option').each(function() {
            if ($(this).val() === "") return; // Ignorar la opción por defecto
            
            // Mostrar si el año coincide o si no hay año seleccionado
            if (year === "" || $(this).data('ano').toString() === year) {
                $(this).show().prop('disabled', false);
            } else {
                $(this).hide().prop('disabled', true);
            }
        });
        $('#' + idSelectId).val(''); // Resetear la selección actual
    }

    $(document).ready(function() {
        // Inicializar DataTable
        $('#patentes').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json'
            }
        });

        // Abrir modales
        $("#openModalpt").click(function() { $("#modalpt").css("display", "block"); });
        $("#openModalCuadrospt").click(function() { $("#modalCuadrospt").css("display", "block"); });
        $("#openModalResolucionespt").click(function() { $("#modalResolucionespt").css("display", "block"); }); 

        // Escuchar los cambios en los selectores de año
        $('#ano_xls').on('change', function() { applyYearFilter('ano_xls', 'identificador_solicitud'); });
        $('#ano_cuadros').on('change', function() { applyYearFilter('ano_cuadros', 'cuadro_identificador_solicitud'); });
        $('#ano_res').on('change', function() { applyYearFilter('ano_res', 'cuadro_identificador_patente'); });

        // Cerrar los modales
        $(".close").click(function() { $(this).closest('.modal').css("display", "none"); });

        // Cerrar los modales si se hace clic fuera de ellos
        $(window).click(function(event) {
            if ($(event.target).is(".modal")) {
                $(".modal").css("display", "none");
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>