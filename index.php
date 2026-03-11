<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Productividad | Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    
    <style>
        /* 1. CONFIGURACIÓN BASE */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9; /* Mismo fondo que la lista interior */
            color: #334155;
            margin: 0;
        }

        /* 2. CABECERA PRINCIPAL (Estilo Navbar superior) */
        .main-header {
            background-color: #ffffff;
            padding: 25px 40px;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 30px;
        }
        
        .main-title {
            font-size: 1.85rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .main-subtitle {
            font-size: 0.95rem;
            color: #64748b;
            margin-top: 5px;
            margin-bottom: 0;
        }

        /* 3. TARJETAS DE CATEGORÍAS */
        .category-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 25px;
            height: 100%;
        }

        .category-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .title-salarial { color: #3b82f6; }
        .title-bono { color: #10b981; }

        /* 4. BOTONES TIPO "PÍLDORA" */
        .btn-modern-outline {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-modern-outline:hover {
            background-color: #e2e8f0;
            color: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        /* Estilo cuando el botón está activado (Clickeado) */
        .btn-modern-outline.active-salarial {
            background-color: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        }

        .btn-modern-outline.active-bono {
            background-color: #10b981;
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }

        /* 5. CONTENEDOR DE TABLAS (Con animación de entrada) */
        .table-container {
            display: none;
            animation: fadeIn 0.4s ease-out;
            margin-top: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Ajustes de los botones de arriba a la derecha */
        .top-actions .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>

<body>
    
    <div class="main-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="main-title"><i class="fas fa-laptop-code text-primary me-2"></i>Módulos de Productividad</h1>
            <p class="main-subtitle">Seleccione una categoría para visualizar los registros de docentes.</p>
        </div>
        <div class="top-actions d-flex gap-2">
                <a href="MENU_INI.PHP" class="btn btn-light border shadow-sm text-secondary">
                    <i class="fas fa-bars"></i> Formularios
                </a>
                <a href="listados.php" class="btn btn-primary shadow-sm">
                    <i class="fas fa-list-check"></i> Ver Listados
                </a>
                <a href="reporte_resoluciones.php" class="btn btn-dark shadow-sm" title="Historial completo de todas las resoluciones">
                    <i class="fas fa-file-signature text-warning"></i> Historial Resoluciones
                </a>
            </div>
    </div>

    <div class="container-fluid px-4 mb-5">
        
        <div class="row g-4 mb-4">
            
            <div class="col-xl-6">
                <div class="category-card">
                    <h4 class="category-title title-salarial">
                        <i class="fas fa-coins bg-primary bg-opacity-10 p-2 rounded-circle"></i> Puntos Salariales
                    </h4>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#lista_solicitudes">Artículos</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listar_trabajo_cient">Trabajo Científico</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listalibros">Libros</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listapremios">Premios</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listar_patentes">Patentes</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listar_traduccion_libro">Traducción de Libro</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listar_creacion">Creación</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listar_innovacion">Innovación</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listar_titulos">Títulos</button>
                        <button class="btn btn-modern-outline btn-cat-salarial" data-target="#listar_produccion">Producción</button>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="category-card">
                    <h4 class="category-title title-bono">
                        <i class="fas fa-gift bg-success bg-opacity-10 p-2 rounded-circle"></i> Bonificación
                    </h4>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_cientifico_bon">Científico</button>
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_obra_creacion_bon">Obra de Creación</button>
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_publicacion_bon">Publicación</button>
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_ponencias_bon">Ponencias</button>
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_posdoctoral_bon">Postdoctoral</button>
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_resena">Reseña</button>
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_traduccion_bon">Traducción</button>
                        <button class="btn btn-modern-outline btn-cat-bono" data-target="#listar_dir_tesis">Dirección de Tesis</button>
                    </div>
                </div>
            </div>

        </div>

        <div class="tables-wrapper">
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
            
            <div class="table-container" id="listar_cientifico_bon"><?php include('listar_cientifico_bon.php'); ?></div>
            <div class="table-container" id="listar_obra_creacion_bon"><?php include('listar_obra_creacion_bon.php'); ?></div>
            <div class="table-container" id="listar_publicacion_bon"><?php include('listar_publicacion_bon.php'); ?></div>
            <div class="table-container" id="listar_ponencias_bon"><?php include('listar_ponencias_bon.php'); ?></div>
            <div class="table-container" id="listar_posdoctoral_bon"><?php include('listar_posdoctoral_bon.php'); ?></div>
            <div class="table-container" id="listar_resena"><?php include('listar_resena.php'); ?></div>
            <div class="table-container" id="listar_traduccion_bon"><?php include('listar_traduccion_bon.php'); ?></div>
            <div class="table-container" id="listar_dir_tesis"><?php include('listar_dir_tesis.php'); ?></div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script>
    $(document).ready(function() {
        // Ocultar todas las tablas al iniciar
        $('.table-container').hide();

        // Al hacer clic en un botón de "Puntos Salariales"
        $('.btn-cat-salarial').click(function() {
            manejarClic(this, 'active-salarial');
        });

        // Al hacer clic en un botón de "Bonificación"
        $('.btn-cat-bono').click(function() {
            manejarClic(this, 'active-bono');
        });

        // Función inteligente para manejar el cambio visual y desplegar la tabla
        function manejarClic(boton, claseActiva) {
            var target = $(boton).data('target');
            var $tableContainer = $(target);
            
            // Si el botón ya estaba activo, se "apaga" y esconde la tabla
            if ($(boton).hasClass(claseActiva)) {
                $(boton).removeClass(claseActiva);
                $tableContainer.hide();
                return;
            }

            // Apaga todos los botones de ambos grupos
            $('.btn-modern-outline').removeClass('active-salarial active-bono');
            
            // Prende solo el botón seleccionado
            $(boton).addClass(claseActiva);
            
            // Esconde todas las tablas y muestra solo la solicitada con una transición suave
            $('.table-container').hide(); 
            $tableContainer.fadeIn(300);

            // Refrescar y configurar la DataTable mostrada
            var table = $tableContainer.find('table');
            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy(); // Destruir instancia previa
            }
            table.DataTable({ 
                "responsive": true,
                "pageLength": 10,
                "dom": 'lfrtip',
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.12.1/i18n/Spanish.json"
                }
            });
        }
    });
    </script>
</body>
</html>