<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIARP | Gestión y Registro de Productividad</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
            margin: 0;
            min-height: 100vh;
        }

        /* ---- SIDEBAR ---- */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            width: 250px; 
            background: #0f172a;
            border-right: 1px solid #1e293b;
            display: flex;
            flex-direction: column;
            z-index: 200;
            transition: width 0.3s ease;
            overflow: hidden;
        }

        .sidebar-logo {
            padding: 20px 18px 16px;
            border-bottom: 1px solid #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #2563eb, #10b981);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
            transition: margin 0.3s ease;
        }

        .logo-text { font-size: 0.9rem; font-weight: 700; color: #f1f5f9; line-height: 1.2; white-space: nowrap; }
        .logo-sub  { font-size: 0.7rem; color: #64748b; font-weight: 400; white-space: nowrap; }

        .query-badge-container { padding: 15px 18px 5px; }
        .query-badge {
            background: #1e293b;
            color: #93c5fd;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 8px 12px;
            border-radius: 8px;
            letter-spacing: 0.05em;
            border: 1px solid #334155;
            display: flex; align-items: center; gap: 8px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
            white-space: nowrap;
        }

        .sidebar-nav { 
            flex: 1; overflow-y: hidden; padding: 12px 0; position: relative;
            scrollbar-width: none; -ms-overflow-style: none; 
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-group { margin-bottom: 6px; }

        .nav-group-label {
            padding: 10px 18px 4px; font-size: 0.65rem; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase; color: #475569;
            display: flex; align-items: center; gap: 8px; white-space: nowrap;
        }

        .nav-group-label .label-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .dot-salarial { background: #3b82f6; box-shadow: 0 0 6px #3b82f6; }
        .dot-bono     { background: #10b981; box-shadow: 0 0 6px #10b981; }

        .nav-item-btn {
            display: flex; align-items: center; gap: 10px; width: 100%;
            padding: 8px 18px; background: none; border: none; color: #94a3b8;
            font-size: 0.85rem; font-weight: 400; text-align: left; cursor: pointer;
            transition: all 0.18s ease; border-left: 3px solid transparent; white-space: nowrap;
        }
        .nav-item-btn i { width: 16px; text-align: center; font-size: 0.85rem; flex-shrink: 0; transition: font-size 0.2s; }
        .nav-item-btn:hover { color: #e2e8f0; background: rgba(255,255,255,0.04); }
        .nav-item-btn.active-salarial { color: #93c5fd; background: rgba(59,130,246,0.1); border-left-color: #3b82f6; font-weight: 500; }
        .nav-item-btn.active-bono { color: #6ee7b7; background: rgba(16,185,129,0.1); border-left-color: #10b981; font-weight: 500; }

        .sidebar-footer { padding: 12px 12px 16px; border-top: 1px solid #1e293b; display: flex; flex-direction: column; gap: 4px; }
        .sidebar-footer a {
            display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 8px;
            color: #64748b; font-size: 0.85rem; text-decoration: none; transition: all 0.18s; white-space: nowrap;
            font-weight: 500;
        }
        .sidebar-footer a:hover { background: rgba(255,255,255,0.08); filter: brightness(1.2); }

        /* ---- MAIN CONTENT ---- */
        .main-content { margin-left: 250px; min-height: 100vh; background: #f8fafc; color: #334155; transition: margin-left 0.3s ease; }

        /* Topbar Minimalista */
        .topbar {
            background: white; border-bottom: 1px solid #e2e8f0; padding: 14px 32px;
            display: flex; align-items: center; gap: 12px;
            position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .sidebar-toggle {
            background: none; border: none; color: #64748b; font-size: 1.2rem;
            cursor: pointer; padding: 4px 8px; display: block; transition: color 0.2s;
        }
        .sidebar-toggle:hover { color: #0f172a; }

        .topbar-title { font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .topbar-active-label { font-size: 0.85rem; font-weight: 500; color: #64748b; display: none; }

        .content-area { padding: 28px 32px 48px; }

        .empty-state {
            padding: 10px 0 40px 0 !important; background: transparent; color: inherit; text-align: left;
        }

        .table-container {
            display: none; background: white; border: 1px solid #e2e8f0; border-radius: 14px;
            padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.04);
            animation: fadeUp 0.3s ease-out; width: 100%;
        }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .dataTables_wrapper .dataTables_filter input {
            border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 6px 12px; font-size: 0.84rem; outline: none; transition: all 0.2s;
        }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .dataTables_wrapper .dataTables_length select { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 5px 10px; font-size: 0.84rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 6px !important; font-size: 0.8rem !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #2563eb !important; color: white !important; border-color: transparent !important; }
        table.dataTable thead th { background: #f8fafc; color: #475569; font-size: 0.76rem; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid #e2e8f0 !important; }
        table.dataTable tbody td { font-size: 0.86rem; color: #334155; border-bottom: 1px solid #f1f5f9 !important; vertical-align: middle; }

        @media (min-width: 769px) {
            .sidebar.collapsed { width: 72px; }
            .main-content.collapsed { margin-left: 72px; }
            .sidebar.collapsed .logo-text, .sidebar.collapsed .logo-sub, .sidebar.collapsed .query-badge span,
            .sidebar.collapsed .nav-group-label span, .sidebar.collapsed .nav-item-btn span, .sidebar.collapsed .sidebar-footer span { display: none; }
            .sidebar.collapsed .query-badge { justify-content: center; padding: 8px 0; }
            .sidebar.collapsed .query-badge i { margin: 0; }
            .sidebar.collapsed .nav-group-label { justify-content: center; padding: 10px 0 4px; }
            .sidebar.collapsed .nav-group-label .label-dot { margin: 0; }
            .sidebar.collapsed .sidebar-logo { padding: 20px 0 16px; justify-content: center; }
            .sidebar.collapsed .logo-icon { margin: 0; }
            .sidebar.collapsed .nav-item-btn { justify-content: center; padding: 12px 0; border-left: 3px solid transparent; }
            .sidebar.collapsed .nav-item-btn i { font-size: 1.15rem; margin: 0; }
            .sidebar.collapsed .sidebar-footer a { justify-content: center; padding: 12px 0; }
            .sidebar.collapsed .sidebar-footer i { font-size: 1.15rem; margin: 0;}
        }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 190; }

        @media (max-width: 768px) {
            .sidebar { left: -250px; transition: left 0.3s ease; }
            .sidebar.open { left: 0; }
            .sidebar-overlay.open { display: block; }
            .main-content { margin-left: 0; }
            .content-area { padding: 20px 16px 40px; }
            .topbar { padding: 12px 16px; }
        }

        .modal-backdrop { z-index: 1045 !important; }
        .modal { z-index: 1055 !important; }
        body.modal-open .sidebar, body.modal-open .topbar { z-index: 1030 !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fas fa-award"></i></div>
            <div>
                <div class="logo-text">PRODUCTIVIDAD</div>
                <div class="logo-sub">CIARP &mdash; Unicauca</div>
            </div>
        </div>

        <div class="query-badge-container">
            <div class="query-badge" title="Módulos de Consulta">
                <i class="fas fa-search"></i> <span>MÓDULOS DE CONSULTA</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-group">
                <div class="nav-group-label" title="Puntos Salariales">
                    <div class="label-dot dot-salarial"></div>
                    <span>Puntos Salariales</span>
                </div>
                <button class="nav-item-btn btn-cat-salarial" data-target="lista_solicitudes" title="Artículos"><i class="fas fa-newspaper"></i> <span>Artículos</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listar_trabajo_cient" title="Trabajo Científico"><i class="fas fa-flask"></i> <span>Trabajo Científico</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listalibros" title="Libros"><i class="fas fa-book"></i> <span>Libros</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listapremios" title="Premios"><i class="fas fa-trophy"></i> <span>Premios</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listar_patentes" title="Patentes"><i class="fas fa-lightbulb"></i> <span>Patentes</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listar_traduccion_libro" title="Traducción Libro"><i class="fas fa-language"></i> <span>Traducción Libro</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listar_creacion" title="Creación"><i class="fas fa-palette"></i> <span>Creación</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listar_innovacion" title="Innovación"><i class="fas fa-microchip"></i> <span>Innovación</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listar_titulos" title="Títulos"><i class="fas fa-graduation-cap"></i> <span>Títulos</span></button>
                <button class="nav-item-btn btn-cat-salarial" data-target="listar_produccion" title="Producción"><i class="fas fa-industry"></i> <span>Producción</span></button>
            </div>

            <div class="nav-group">
                <div class="nav-group-label" title="Bonificación">
                    <div class="label-dot dot-bono"></div>
                    <span>Bonificación</span>
                </div>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_cientifico_bon" title="Científico"><i class="fas fa-atom"></i> <span>Científico</span></button>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_obra_creacion_bon" title="Obra de Creación"><i class="fas fa-paint-brush"></i> <span>Obra de Creación</span></button>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_publicacion_bon" title="Publicación"><i class="fas fa-file-lines"></i> <span>Publicación</span></button>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_ponencias_bon" title="Ponencias"><i class="fas fa-person-chalkboard"></i> <span>Ponencias</span></button>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_posdoctoral_bon" title="Postdoctoral"><i class="fas fa-user-graduate"></i> <span>Postdoctoral</span></button>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_resena" title="Reseña"><i class="fas fa-comment-dots"></i> <span>Reseña</span></button>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_traduccion_bon" title="Traducción"><i class="fas fa-globe"></i> <span>Traducción</span></button>
                <button class="nav-item-btn btn-cat-bono" data-target="listar_dir_tesis" title="Dirección de Tesis"><i class="fas fa-scroll"></i> <span>Dirección de Tesis</span></button>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="index.php" style="color: #e2e8f0;" title="Formularios"><i class="fas fa-layer-group"></i> <span>Registrar Productividad</span></a>
            <a href="dashboard_analitica_full.php" style="color: #10b981;" title="Analítica Integral"><i class="fas fa-chart-pie"></i> <span>Analítica Integral</span></a>
            <a href="reporte_resoluciones.php" style="color: #eab308;" title="Historial Resoluciones"><i class="fas fa-file-signature"></i> <span>Historial Resoluciones</span></a>
            
            <a href="listados.php" style="color: #3b82f6;" title="Listados Generales"><i class="fas fa-list-check"></i> <span>Listados</span></a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="sidebar-toggle" id="sidebarToggle" title="Contraer/Expandir Menú"><i class="fas fa-bars"></i></button>
                <div>
                    <div class="topbar-title"><i class="fas fa-edit text-primary"></i> Panel de Ingreso y Gestión</div>
                    <div class="topbar-active-label" id="topbarLabel">— Seleccione una consulta en el menú lateral</div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="empty-state" id="emptyState">
                <?php include 'menu_ini.php'; ?>
            </div>

            <div id="tablesZone">
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
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function () {
            $('.table-container').hide();

            $('#sidebarToggle').on('click', function () {
                if (window.innerWidth <= 768) {
                    $('#sidebar').toggleClass('open');
                    $('#sidebarOverlay').toggleClass('open');
                } else {
                    $('#sidebar').toggleClass('collapsed');
                    $('.main-content').toggleClass('collapsed');
                    setTimeout(function() {
                        if ($.fn.DataTable) { $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc(); }
                    }, 350);
                }
            });

            $('#sidebarOverlay').on('click', function () {
                $('#sidebar').removeClass('open');
                $('#sidebarOverlay').removeClass('open');
            });

            $('.btn-cat-salarial').on('click', function () { manejarClic($(this), 'active-salarial'); });
            $('.btn-cat-bono').on('click', function () { manejarClic($(this), 'active-bono'); });

            function manejarClic(boton, claseActiva) {
                var target = boton.data('target');
                var tableContainer = $('#' + target);

                if (boton.hasClass(claseActiva)) {
                    boton.removeClass(claseActiva);
                    tableContainer.fadeOut(200);
                    $('#emptyState').fadeIn(200);
                    $('#topbarLabel').hide();
                    $('.topbar-title').html('<i class="fas fa-edit text-primary"></i> Panel de Ingreso y Gestión');
                    return;
                }

                $('.nav-item-btn').removeClass('active-salarial active-bono');
                boton.addClass(claseActiva);

                $('.topbar-title').html('<i class="fas fa-search text-info"></i> Modo Consulta Activo');
                var textoBoton = boton.find('span').text().trim();
                $('#topbarLabel').html('<span class="breadcrumb-sep">/</span> ' + textoBoton).show();

                $('#emptyState').hide();

                $('.table-container').not(tableContainer).fadeOut(150);
                setTimeout(function () {
                    tableContainer.fadeIn(280);
                    var table = tableContainer.find('table');
                    if ($.fn.DataTable.isDataTable(table)) { table.DataTable().destroy(); }
                    table.DataTable({ responsive: true, pageLength: 10, dom: 'lfrtip', language: { url: 'assets/es-ES.json' } });

                    if (window.innerWidth <= 768) {
                        $('#sidebar').removeClass('open');
                        $('#sidebarOverlay').removeClass('open');
                    }
                }, 160);
            }

            const nav = document.querySelector('.sidebar-nav');
            if (nav) {
                nav.addEventListener('mousemove', (e) => {
                    if (window.innerWidth > 768 && nav.scrollHeight > nav.clientHeight) {
                        const rect = nav.getBoundingClientRect();
                        const mouseY = e.clientY - rect.top; 
                        const scrollPercent = mouseY / rect.height;
                        const scrollMax = nav.scrollHeight - nav.clientHeight;
                        nav.scrollTo({ top: scrollMax * scrollPercent, behavior: 'auto' });
                    }
                });
            }
        });
    </script>

    <?php if (isset($_GET['status'])): ?>
    <script>
        $(document).ready(function() {
            const status = "<?php echo $_GET['status']; ?>";
            const msg = "<?php echo isset($_GET['msg']) ? addslashes(urldecode($_GET['msg'])) : ''; ?>";

            if (status === "success") {
                Swal.fire({ icon: 'success', title: '¡Registro Exitoso!', text: 'Los datos han sido guardados.', confirmButtonColor: '#2563eb', timer: 4000, timerProgressBar: true }).then(() => { window.history.replaceState({}, document.title, "index.php"); });
            } 
            else if (status === "success_anular") {
                Swal.fire({ icon: 'info', title: 'Solicitud Anulada', text: 'Marcado como anulado correctamente.', confirmButtonColor: '#3b82f6', timer: 3000, timerProgressBar: true }).then(() => { window.history.replaceState({}, document.title, "index.php"); });
            }
            else if (status === "error") {
                Swal.fire({ icon: 'error', title: 'Error en la Operación', text: 'Problema técnico: ' + msg, confirmButtonColor: '#ef4444' });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>