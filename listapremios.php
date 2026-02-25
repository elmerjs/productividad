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
    d.depto_nom_propio AS `DEPARTAMENTO`, p.numero_oficio,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    p.nombre_evento AS `EVENTO_PREMIO`,
    p.ambito AS `AMBITO`,
    p.categoria_premio AS `CATEGORIA_PREMIO`,
    p.nivel_ganado AS `NIVEL_GANADO`,
    p.lugar_fecha AS `LUGAR_Y_FECHA`, p.estado,
    GROUP_CONCAT(DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero) ORDER BY t.documento_tercero SEPARATOR '\n') AS `DETALLES PROFESORES`,
    p.numero_oficio AS `OFICIO`, p.puntos
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
$identificadores_result = $conn->query("SELECT DISTINCT identificador FROM premios");
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
    <title>Listado de Premios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <style>
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); padding-top: 60px; }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 60%; max-width: 600px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <h1>Listado de Premios</h1>
    
    <!-- Botones y modales -->
        <button id="openModalpr" class="btn btn-primary">Generar XLS</button>
    <button id="openModalCuadrospr" class="btn btn-secondary">Generar Cuadros</button><br><br>
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
            // Mostrar los resultados de la consulta
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['identificador']) . '</td>';

                
 echo '<td class="facultad-truncate" title="' 
                    . htmlspecialchars($row['DEPARTAMENTO']) . ' - ' . htmlspecialchars($facultad) . '">'
                    . htmlspecialchars(substr($row['DEPARTAMENTO'], 0, 20)) . ' - ' . $facultadTruncada 
                    . '</td>';
                                echo '<td>' . htmlspecialchars($row['numero_oficio']) . '</td>';

                echo '<td class="detalle-profesores" title="' . htmlspecialchars($row['DETALLES PROFESORES']) . '">'
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
echo '<a href="editar_premios.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a>';
echo '<button class="btn btn-danger btn-sm" onclick="confirmDeleteWithReason(' . $row['id'] . ')">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

<!-- Modal para exportar a Excel -->
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
            <span class="close close-cuadros">&times;</span>
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
                <label for="cuadro_ano">Año:</label>
                <input type="number" name="cuadro_ano" id="cuadro_ano" class="form-control">
                <br>
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
                window.location.href = 'eliminar_solicitud_premio.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
            }
        }
    }
</script>
<script>
    $(document).ready(function() {
        $('#premios').DataTable();
        $('#openModalpr').on('click', function() { $('#modalpr').show(); });
        $('#openModalCuadrospr').on('click', function() { $('#modalCuadrospr').show(); });
        $('.close').on('click', function() { $(this).closest('.modal').hide(); });
    });
</script>
</body>
</html>
