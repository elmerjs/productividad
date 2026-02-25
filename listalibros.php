<?php
// Requerir la conexión a la base de datos
include_once  'conn.php';

// Obtener los filtros desde el formulario (si existen)
$identificador_solicitud = isset($_POST['identificador_solicitud']) ? $_POST['identificador_solicitud'] : null;
$ano = isset($_POST['ano']) ? $_POST['ano'] : null;

// Crear la consulta SQL con los filtros opcionales
$sql = "
SELECT 
    f.nombre_fac_min AS `FACULTAD`, l.id_libro,
    d.depto_nom_propio AS `DEPARTAMENTO`,
    l.numero_oficio AS `NUMERO_OFICIO`,
    GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS `CEDULA`,
    GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS `NOMBRES`,
    GROUP_CONCAT(
        DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero)
        ORDER BY t.documento_tercero
        SEPARATOR '\n'
    ) AS `DETALLES_PROFESORES`,
    l.identificador AS `IDENTIFICADOR`,
    l.tipo_libro AS `TIPO DE LIBRO`,
    l.producto AS `NOMBRE DEL PRODUCTO`,
    l.isbn AS `ISBN`,
    l.mes_ano_edicion AS `MES Y AÑO DE EDICIÓN`,
    l.nombre_editorial AS `EDITORIAL`,
    l.tiraje AS `TIRAJE`,
    l.numero_profesores AS `NUMERO DE PROFESORES`,
    l.autores AS `NUMERO DE AUTORES`,
    l.evaluacion_1 AS `EVALUACION 1`,
    l.evaluacion_2 AS `EVALUACION 2`,
    l.calculo AS `CALCULO`,
    l.puntaje_final AS `PUNTAJE FINAL`,
    l.estado AS `ESTADO`,
    l.tipo_productividad AS `TIPO DE PRODUCTIVIDAD`
FROM 
    libros l
JOIN 
    libro_profesor lp ON l.id_libro = lp.id_libro
JOIN 
    tercero t ON lp.id_profesor = t.documento_tercero
JOIN 
    deparmanentos d ON t.fk_depto = d.PK_DEPTO
JOIN 
    facultad f ON d.FK_FAC = f.PK_FAC
WHERE 1 = 1";

// Añadir condiciones según los filtros
if (!empty($identificador_solicitud)) {
    $sql .= " AND l.identificador = '" . $conn->real_escape_string($identificador_solicitud) . "'";
}
if (!empty($ano)) {
    $sql .= " AND YEAR(l.fecha_solicitud) = '" . $conn->real_escape_string($ano) . "'";
}

// Agrupar resultados por id de solicitud
$sql .= " GROUP BY l.id_libro";

// Ejecutar la consulta
$result = $conn->query($sql);

// Obtener los identificadores de solicitud para los filtros
$identificadores = [];

$identificadores_result = $conn->query("SELECT DISTINCT TRIM(identificador) AS identificador FROM libros");
while ($rowb = $identificadores_result->fetch_assoc()) {
    $identificadores[] = $rowb['identificador'];
}
$identificadores = array_unique($identificadores);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Solicitudes Libros</title>

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
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <h1>Listado de Libros</h1>
  <!-- Botones para abrir los modales -->
        <button id="openModallb" class="btn btn-primary">Generar XLS</button>
        <button id="openModalCuadroslb" class="btn btn-secondary">Generar Cuadros</button>
        <button id="openModalResolucionesLibros" class="btn btn-info text-white">Generar Resoluciones</button>
    <br>    <br>
    <!-- Tabla donde se mostrarán los datos -->
    <table id="libros" class="display table table-striped table-bordered">
        <thead>
            <tr>
                
                
                        <th>ID</th>
          <th>IDENTIFICADOR</th>
                <th>DEPARTAMENTO</th>
                  <th>OFICIO</th>
                <th>NOMBRES</th>
                <th>NOMBRE DEL PRODUCTO</th>
                <th>TIPO DE ARTICULO</th>
                <th>ISSN</th>
                <th>EDITORIAL</th>
                <th>TIRAJE</th>
                    <th>ESTADO</th>

                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Mostrar los resultados de la consulta
            while ($row = $result->fetch_assoc()) {
                // Truncar los campos largos para mejorar la visualización
                $facultad = htmlspecialchars($row['FACULTAD']);
                $nombres = htmlspecialchars($row['NOMBRES']);
                $nombreProducto = htmlspecialchars($row['NOMBRE DEL PRODUCTO']);
                $nombreRevista = htmlspecialchars($row['EDITORIAL']);
                $nombresTruncados = strlen($nombres) > 20 ? substr($nombres, 0, 20) . "..." : $nombres;
                $nombreProductoTruncado = strlen($nombreProducto) > 30 ? substr($nombreProducto, 0, 30) . "..." : $nombreProducto;
                $nombreRevistaTruncado = strlen($nombreRevista) > 30 ? substr($nombreRevista, 0, 30) . "..." : $nombreRevista;

                // Mostrar cada fila
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id_libro']) . '</td>';
                echo '<td>' . htmlspecialchars($row['IDENTIFICADOR']) . '</td>';

              // echo '<td title="' . $row['FACULTAD'] . '">' . substr(str_replace("Facultad de ", "", $row['FACULTAD']), 0, 15) . (strlen($row['FACULTAD']) > 15 ? '...' : '') . '</td>'; 

 echo '<td class="facultad-truncate" title="' 
                    . htmlspecialchars($row['DEPARTAMENTO']) . ' - ' . htmlspecialchars($facultad) . '">'
                    . htmlspecialchars(substr($row['DEPARTAMENTO'], 0, 20)) . ' - ' . $facultadTruncada 
                    . '</td>';
                
echo '<td>' . htmlspecialchars($row['NUMERO_OFICIO']) . '</td>';
echo '<td class="detalle-profesores" title="' . htmlspecialchars($row['DETALLES_PROFESORES']) . '">'
                     . htmlspecialchars(substr($row['DETALLES_PROFESORES'], 0, 20)) . (strlen($row['DETALLES_PROFESORES']) > 20 ? '...' : '')
                     . '</td>';                    echo '<td class="truncate" title="' . $nombreProducto . '">' . $nombreProductoTruncado . '</td>';
                echo '<td>' . htmlspecialchars($row['TIPO DE LIBRO']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ISBN']) . '</td>';
                echo '<td class="truncate" title="' . $nombreRevista . '">' . $nombreRevistaTruncado . '</td>';
                echo '<td>' . htmlspecialchars($row['TIRAJE']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ESTADO']) . '</td>';

                echo '<td>';
echo '<a href="editar_libros.php?id=' . $row['id_libro'] . '" class="btn btn-warning btn-sm">Editar</a>';
echo '<button class="btn btn-danger btn-sm" onclick="confirmDeleteWithReason(' . $row['id_libro'] . ')">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

  
<!-- Modal para exportar a Excel -->
<div id="modallb" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Filtrar por Solicitud o Año</h2>
        <form action="report_libros.php" method="GET">
            <label for="identificador_solicitud">Identificador de Solicitud:</label>
            <select name="identificador_solicitud" id="identificador_solicitud" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                    foreach ($identificadores as $identificador) {
                        if (!empty($identificador)) {
                            echo '<option value="' . htmlspecialchars($identificador) . '">' . htmlspecialchars($identificador) . '</option>';
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
    <!-- Modal para generar cuadros -->
  <div id="ModalCuadroslb" class="modal">
    <div class="modal-content">
        <span class="close close-cuadros">&times;</span>
        <h2>Filtrar para Generar Cuadros</h2>
        <form action="cuadros_libros.php" method="GET">
            <label for="cuadro_identificador_libro">Identificador de Solicitud:</label>
            <select name="cuadro_identificador_libro" id="cuadro_identificador_libro" class="form-control">
                <option value="">Selecciona un identificador</option>
                <?php
                         
                
           

            // Bucle `foreach` para generar las opciones del select
                foreach ($identificadores as $row_ident_cuadro) {
                    if (!empty($row_ident_cuadro)) {
                        echo '<option value="' . htmlspecialchars($row_ident_cuadro) . '">' . htmlspecialchars($row_ident_cuadro) . '</option>';
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
<div id="modalResolucionesLibros" class="modal">
    <div class="modal-content">
        <span class="close close-resoluciones-libros">&times;</span>
        <h2>Generar Resoluciones de Libros</h2>
        
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="filtro_ano_libros">1. Selecciona el Año:</label>
                <select id="filtro_ano_libros" class="form-control">
                    <option value="todos">-- Mostrar todos los años --</option>
                    <?php
                    // Obtenemos años únicos directamente de los identificadores guardados en la tabla libros
                    $resAnosL = $conn->query("SELECT DISTINCT SUBSTRING(identificador, 1, 4) as ano FROM libros ORDER BY ano DESC");
                    while($a = $resAnosL->fetch_assoc()) {
                        echo '<option value="'.$a['ano'].'">'.$a['ano'].'</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <form action="resoluciones_libros.php" method="GET">
            <label for="cuadro_identificador_libro">2. Selecciona el Paquete:</label>
            <select name="cuadro_identificador_libro" id="res_identificador_libros" class="form-control" required>
                <option value="">-- Selecciona un identificador --</option>
                <?php
                // Cargamos todos los identificadores con su respectivo data-ano para el filtrado JS
                $resIdsL = $conn->query("SELECT DISTINCT identificador FROM libros ORDER BY identificador DESC");
                while ($row = $resIdsL->fetch_assoc()) {
                    if (!empty($row['identificador'])) {
                        $ano_paquete = substr($row['identificador'], 0, 4);
                        echo '<option value="' . htmlspecialchars($row['identificador']) . '" data-ano="'.$ano_paquete.'">' 
                             . htmlspecialchars($row['identificador']) . '</option>';
                    }
                }
                ?>
            </select>
            <br>
            <button type="submit" class="btn btn-info text-white w-100">
                <i class="fas fa-file-word"></i> Generar Resoluciones Word (Libros)
            </button>
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
                window.location.href = 'eliminar_solicitud_libro.php?id_solicitud=' + id + '&motivo=' + encodeURIComponent(motivo);
            } else {
                alert("El motivo de la anulación es obligatorio.");
            }
        }
    }
</script>
<script>
    $(document).ready(function() {
        // Inicializar el DataTable
        $('#libros').DataTable();

        // Función para abrir el modal
        $('#openModallb').on('click', function() {
            $('#modallb').show();
        });

        // Función para abrir el modal de cuadros
        $('#openModalCuadroslb').on('click', function() {
            $('#ModalCuadroslb').show();
        });

        // Cerrar el modal cuando se hace clic en la "X"
        $('.close').on('click', function() {
            $(this).closest('.modal').hide();
        });

        // Función para manejar la edición y eliminación de registros
        $('.edit-btn').on('click', function() {
            let id = $(this).data('id');
            alert('Editar solicitud con ID: ' + id);
            // Aquí iría el código para abrir el modal de edición
        });

        $('.delete-btn').on('click', function() {
            let id = $(this).data('id');
            if (confirm('¿Estás seguro de eliminar la solicitud con ID ' + id + '?')) {
                // Aquí iría el código para eliminar el registro
                alert('Solicitud eliminada');
            }
        });
        
      
    });
    $(document).ready(function() {
    // Evento de filtrado por año para Libros
    $('#filtro_ano_libros').on('change', function() {
        var anoSeleccionado = $(this).val();
        
        // Resetear la selección del paquete
        $('#res_identificador_libros').val("");
        
        // Recorrer las opciones del select de paquetes
        $('#res_identificador_libros option').each(function() {
            var optionAno = $(this).data('ano');
            
            if (anoSeleccionado === "todos") {
                $(this).show(); // Mostrar todos si no hay filtro
            } else {
                // Mostrar solo si coincide el año o es la opción por defecto
                if (optionAno == anoSeleccionado || $(this).val() === "") {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        });
    });

    // Control de apertura/cierre del modal de Libros
    $('#openModalResolucionesLibros').on('click', function() {
        $('#modalResolucionesLibros').show();
    });

    $('.close-resoluciones-libros').on('click', function() {
        $('#modalResolucionesLibros').hide();
    });
});
</script>
</body>
</html>
