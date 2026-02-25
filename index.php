
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LISTADOS</title>

    <!-- Incluir estilos de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Incluir tus estilos personalizados después de Bootstrap -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        /* Contenedor de la página */
        .container {
    margin-top: 50px;
    width: 100%; /* Asegúrate de que el contenedor principal ocupe todo el ancho */
}

/* Recuadro gris leve para cada grupo */
.group-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    width: 100%; /* Aumenta el ancho del contenedor */
}

        /* Contenedor para tablas, ocultas por defecto */
        .table-container {
            margin-top: 20px;
            display: none;
        }

        /* Botones personalizados */
        .btn-dark-blue {
            background-color: #003366 !important;
            border-color: #003366 !important;
            color: white !important;
        }

        .btn-dark-blue:hover {
            background-color: #002244 !important;
            border-color: #002244 !important;
        }
    </style>
</head>

<body>
    
  <div class="container-fluid mt-4">

        <h2 class="text-center">Listas productividad</h2>

        <!-- Grupo de Puntos Salariales -->
        <div class="group-container">
            <h4 class="text-center">Puntos Salariales</h4>
            <div class="text-center mb-3">
                <button class="btn btn-dark-blue" data-target="#lista_solicitudes">Revistas indexadas</button>
                <button class="btn btn-dark-blue" data-target="#listar_trabajo_cient">Trabajo Científico</button>
                <button class="btn btn-dark-blue" data-target="#listalibros">Libros</button>
                <button class="btn btn-dark-blue" data-target="#listapremios">Premios</button>
                <button class="btn btn-dark-blue" data-target="#listar_patentes">Patentes</button>
                <button class="btn btn-dark-blue" data-target="#listar_traduccion_libro">Traducción de Libro</button>
                <button class="btn btn-dark-blue" data-target="#listar_creacion">Creación</button>
                <button class="btn btn-dark-blue" data-target="#listar_innovacion">Innovación</button>
                <button class="btn btn-dark-blue" data-target="#listar_titulos">Títulos</button>
                <button class="btn btn-dark-blue" data-target="#listar_produccion">Producción</button>
    
                
            </div>
            <div class="table-container" id="lista_solicitudes"><?php include('lista_solicitudes.php'); ?></div>
            <div class="table-container" id="listar_trabajo_cient"><?php include('listar_trabajo_cient.php'); ?></div>
            <div class="table-container" id="listalibros"><?php include('listalibros.php'); ?></div>
            <div class="table-container" id="listapremios"><?php include('listapremios.php'); ?></div>
            <div class="table-container" id="listar_patentes"><?php include('listar_patentes.php'); ?></div>
            <div class="table-container" id="listar_traduccion_libro"><?php include('listar_traduccion_libro.php'); ?></div>
            <div class="table-container" id="listar_creacion"><?php include('listar_creacion.php'); ?></div>
            <div class="table-container" id="listar_innovacion"><?php include('listar_innovacion.php'); ?></div>
            <div class="table-container" id="listar_titulos"><?php include('listar_titulos.php'); ?></div>
            <div class="table-container" id="listar_produccion"><?php include('listar_produccion.php'); ?></div>
        </div>

        <!-- Grupo de Bonificación -->
        <div class="group-container">
            <h4 class="text-center">Bonificación</h4>
            <div class="text-center mb-3">
                <button class="btn btn-dark-blue" data-target="#listar_cientifico_bon">Científico</button>
                <button class="btn btn-dark-blue" data-target="#listar_obra_creacion_bon">Obra de Creación</button>
                <button class="btn btn-dark-blue" data-target="#listar_publicacion_bon">Publicación</button>
                <button class="btn btn-dark-blue" data-target="#listar_ponencias_bon">Ponencias</button>
                <button class="btn btn-dark-blue" data-target="#listar_posdoctoral_bon">Postdoctoral</button>
                <button class="btn btn-dark-blue" data-target="#listar_resena">Reseña</button>
                <button class="btn btn-dark-blue" data-target="#listar_traduccion_bon">Traducción</button>
                <button class="btn btn-dark-blue" data-target="#listar_dir_tesis">Dirección de Tesis</button>
            </div>
            <div class="table-container" id="listar_cientifico_bon"><?php include('listar_cientifico_bon.php'); ?></div>
            <div class="table-container" id="listar_obra_creacion_bon"><?php include('listar_obra_creacion_bon.php'); ?></div>
            <div class="table-container" id="listar_publicacion_bon"><?php include('listar_publicacion_bon.php'); ?></div>
            <div class="table-container" id="listar_ponencias_bon"><?php include('listar_ponencias_bon.php'); ?></div>
            <div class="table-container" id="listar_posdoctoral_bon"><?php include('listar_posdoctoral_bon.php'); ?></div>
            <div class="table-container" id="listar_resena"><?php include('listar_resena.php'); ?></div>
            <div class="table-container" id="listar_traduccion_bon"><?php include('listar_traduccion_bon.php'); ?></div>
            <div class="table-container" id="listar_dir_tesis"><?php include('listar_dir_tesis.php'); ?></div>
        </div>
      <a href="MENU_INI.PHP" class="btn btn-primary">Formularios</a>
<a href="listados.php" class="btn btn-primary">Ver Listados</a>

    </div>

    <!-- Incluir Scripts de jQuery, Bootstrap y DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    // Ocultar todas las tablas por defecto
    $('.table-container').hide();

    // Alternar la visibilidad de la tabla correspondiente al hacer clic en un botón
    $('.btn-dark-blue').click(function() {
        var target = $(this).data('target');
        var $tableContainer = $(target);
        
        if ($tableContainer.is(':visible')) {
            $tableContainer.hide(); // Ocultar si ya está visible
        } else {
            $('.table-container').hide(); // Ocultar todas las tablas
            $tableContainer.show(); // Mostrar la tabla seleccionada
        }

        // Destruir y volver a inicializar DataTable para evitar el error de re-inicialización
        var table = $tableContainer.find('table');
        if ($.fn.DataTable.isDataTable(table)) {
            table.DataTable().destroy(); // Destruir instancia previa
        }
        table.DataTable({ // Inicializar DataTable
            "responsive": true,
            "pageLength": 10,
            "dom": 'lfrtip'
        });
    });
});
</script>
</body>
</html>
