<?php
// Incluir el archivo de conexión
include_once('conn.php'); 

// Obtener los filtros desde el formulario (si existen)
$identificador_completo = isset($_POST['identificador']) ? $_POST['identificador'] : null;
$numeroOficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;

// Consulta SQL
$sql = "
    SELECT 
        pb.id AS id,
        f.nombre_fac_min AS FACULTAD,
        d.depto_nom_propio AS DEPARTAMENTO,
        pb.identificador_completo,
        pb.numeroOficio,
        pb.fecha_solicitud,
        pb.tipo_producto,
        pb.nombre_revista,
        pb.producto,
        pb.isbn,
        pb.fecha_publicacion,
        pb.lugar_publicacion,
        pb.autores,
        pb.evaluacion1,
        pb.evaluacion2,
        pb.puntaje,
        pb.puntaje_final,
        pb.tipo_productividad,
        GROUP_CONCAT(
            DISTINCT CONCAT(ter.nombre_completo, ' c.c ', ter.documento_tercero)
            ORDER BY ter.documento_tercero
            SEPARATOR '\n'
        ) AS DETALLES_PROFESORES
    FROM 
        publicacion_bon pb
    JOIN 
        publicacion_bon_profesor pbp ON pbp.id_publicacion_bon = pb.id
    JOIN 
        tercero ter ON pbp.documento_profesor = ter.documento_tercero
    JOIN 
        deparmanentos d ON ter.fk_depto = d.PK_DEPTO
    JOIN 
        facultad f ON d.FK_FAC = f.PK_FAC
    WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador_completo)) {
    $sql .= " AND pb.identificador_completo = '" . $conn->real_escape_string($identificador_completo) . "'";
}
if (!empty($numeroOficio)) {
    $sql .= " AND pb.numeroOficio = '" . $conn->real_escape_string($numeroOficio) . "'";
}

$sql .= " 
    GROUP BY 
        pb.id, pb.identificador_completo, pb.numeroOficio, pb.fecha_solicitud, 
        pb.tipo_producto, pb.nombre_revista, pb.producto, pb.isbn, 
        pb.fecha_publicacion, pb.lugar_publicacion, pb.autores, 
        pb.evaluacion1, pb.evaluacion2, pb.puntaje, pb.puntaje_final, 
        pb.tipo_productividad
    ORDER BY 
        pb.fecha_solicitud DESC;
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Realizar la consulta para obtener los identificadores y extraer años
$identificadores_result = $conn->query("SELECT DISTINCT identificador_completo FROM publicacion_bon ORDER BY identificador_completo DESC");
$identificadores = [];
$unique_years = [];

while ($row = $identificadores_result->fetch_assoc()) {
    $id_str = $row['identificador_completo'];
    $identificadores[] = $id_str;
    
    // Extraer los primeros 4 caracteres para el año
    $year = substr($id_str, 0, 4);
    if (!empty($year) && is_numeric($year) && !in_array($year, $unique_years)) {
        $unique_years[] = $year;
    }
}
rsort($unique_years); // Ordenar años de mayor a menor

$data = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicaciones Bonif</title>

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
            width: 60%; max-width: 600px; border-radius: 8px;
        }
        .close {
            color: #aaa; float: right; font-size: 28px; font-weight: bold;
        }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #004080; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h1>Lista de Publicaciones Impresas (Bonificación)</h1>

    <div class="mb-3">
        <button id="openModalpb" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadrospb" class="btn btn-secondary">Generar Cuadros</button>
        <button id="openModalResolucionespb" class="btn btn-success">Generar Resoluciones</button>
    </div>

    <table id="publicacionTable" class="table-striped">
        <thead>
            <tr> 
                <th>Facultad</th>
                <th>Departamento</th>
                <th>Identificador</th>
                <th>Número Oficio</th>
                <th>Profesores</th>
                <th>Revista</th>
                <th>Producto</th>
                <th>ISBN/ISSN</th>
                <th>Fecha y Lugar de Publicación</th>
                <th>Ev. 1</th>
                <th>Ev. 2</th>
                <th>Puntaje</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td title="<?php echo htmlspecialchars($row['FACULTAD']); ?>">
                        <?php
                            $facultad = str_replace("Facultad de ", "", $row['FACULTAD']);
                            echo htmlspecialchars(substr($facultad, 0, 15) . (strlen($facultad) > 15 ? '...' : ''));
                        ?>
                    </td>
                    <td title="<?php echo htmlspecialchars($row['DEPARTAMENTO']); ?>">
                        <?php
                            $departamento = $row['DEPARTAMENTO'];
                            echo htmlspecialchars(substr($departamento, 0, 15) . (strlen($departamento) > 15 ? '...' : ''));
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['identificador_completo']); ?></td>
                    <td><?php echo htmlspecialchars($row['numeroOficio']); ?></td>
                    <td title="<?php echo htmlspecialchars($row['DETALLES_PROFESORES']); ?>">
                        <?php echo nl2br(htmlspecialchars(substr($row['DETALLES_PROFESORES'], 0, 25) . (strlen($row['DETALLES_PROFESORES']) > 25 ? '...' : ''))); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['nombre_revista']); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['producto'], 0, 20) . '...'); ?></td>
                    <td><?php echo htmlspecialchars($row['isbn']); ?></td>
                    <td><?php echo htmlspecialchars($row['lugar_publicacion'] . ' : ' . $row['fecha_publicacion']); ?></td>
                    <td><?php echo htmlspecialchars($row['evaluacion1']); ?></td>
                    <td><?php echo htmlspecialchars($row['evaluacion2']); ?></td>
                    <td><?php echo htmlspecialchars($row['puntaje_final']); ?></td>
                    <td>
                        <a href="editar_publicacion_bon.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <button class="delete-btn btn btn-danger btn-sm" data-id="<?php echo $row['id']; ?>">Eliminar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalpb" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_publicaciones_bon.php" method="GET">
            <label for="ano_xls">Año:</label>
            <select name="ano" id="ano_xls" class="form-control mb-3">
                <option value="">Todos los años...</option>
                <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
            </select>

            <label for="identificador_solicitud">Identificador de Solicitud:</label>
            <select name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
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

<div id="modalCuadrospb" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_publicacion_bon.php" method="GET">
            <label for="ano_cuadros">Año:</label>
            <select name="cuadro_ano" id="ano_cuadros" class="form-control mb-3">
                <option value="">Todos los años...</option>
                <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
            </select>

            <label for="cuadro_identificador_solicitud">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_solicitud" id="cuadro_identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
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

<div id="modalResolucionespb" class="modal">
    <div class="modal-content">
        <span class="close close-resoluciones">&times;</span>
        <h2>Filtrar para Generar Resoluciones</h2>
        <form action="resoluciones_publicacion_bon.php" method="GET">
            <label for="ano_res">Año:</label>
            <select id="ano_res" class="form-control mb-3">
                <option value="">Todos los años...</option>
                <?php foreach($unique_years as $y) echo "<option value='$y'>$y</option>"; ?>
            </select>

            <label for="cuadro_identificador_pub_bon">Identificador de Solicitud (Paquete):</label>
            <select name="cuadro_identificador_pub_bon" id="cuadro_identificador_pub_bon" class="form-control" required>
                <option value="">Selecciona un identificador</option>
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
    // Función para filtrar los paquetes según el año seleccionado
    function applyYearFilter(yearSelectId, idSelectId) {
        const year = $('#' + yearSelectId).val();
        $('#' + idSelectId + ' option').each(function() {
            if ($(this).val() === "") return; 
            
            if (year === "" || $(this).data('ano').toString() === year) {
                $(this).show().prop('disabled', false);
            } else {
                $(this).hide().prop('disabled', true);
            }
        });
        $('#' + idSelectId).val(''); 
    }

    $(document).ready(function() {
        $('#publicacionTable').DataTable({
            responsive: true,
            dom: 'Bfrtip', 
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json' }
        });

        // Abrir modales
        $("#openModalpb").click(function() { $("#modalpb").css("display", "block"); });
        $("#openModalCuadrospb").click(function() { $("#modalCuadrospb").css("display", "block"); });
        $("#openModalResolucionespb").click(function() { $("#modalResolucionespb").css("display", "block"); });

        // Escuchar los cambios en los selectores de año
        $('#ano_xls').on('change', function() { applyYearFilter('ano_xls', 'identificador_solicitud'); });
        $('#ano_cuadros').on('change', function() { applyYearFilter('ano_cuadros', 'cuadro_identificador_solicitud'); });
        $('#ano_res').on('change', function() { applyYearFilter('ano_res', 'cuadro_identificador_pub_bon'); });

        // Cerrar los modales
        $(".close").click(function() { $(this).closest('.modal').css("display", "none"); });
        $(window).click(function(event) {
            if ($(event.target).is(".modal")) {
                $(".modal").css("display", "none");
            }
        });

        // Eliminar
        $(".delete-btn").click(function() {
            var id = $(this).data("id");
            var confirmDelete = confirm("¿Seguro que quieres eliminar esta solicitud?");
            if (confirmDelete) {
                alert("Acá iría la redirección a tu archivo eliminar.php?id=" + id);
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>