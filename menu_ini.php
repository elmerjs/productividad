<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Menú de Reconocimientos Académicos</title>
    <style>
    :root {
        --color-dark: #202124; /* Color similar al negro suave de Google */
        --color-primary: #4285f4; /* Azul característico de Google */
        --color-light: #ffffff; /* Blanco */
        --color-border: #dadce0; /* Color de borde gris suave */
        --color-hover: #f1f3f4; /* Fondo de hover */
        --borderRadius: 8px;
        --font-face: 'Roboto', Arial, sans-serif;
    }

    body {
        background-color: #f8f9fa;
        font-family: var(--font-face);
    }

    .container {
        margin-top: 50px;
        padding: 30px;
        border-radius: var(--borderRadius);
        background: var(--color-light);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--color-border);
    }

    h2 {
        color: var(--color-dark);
        margin-bottom: 30px;
    }

    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--borderRadius);
        padding: 10px 15px;
        font-size: 1rem;
        background-color: var(--color-light);
        color: var(--color-dark);
        border: 1px solid var(--color-border);
        text-decoration: none;
        transition: background-color 0.3s, box-shadow 0.3s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn:hover {
        background-color: var(--color-hover);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .btn i {
        margin-right: 8px;
        color: var(--color-primary);
    }

    .btn-group-custom {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .category h5 {
        color: var(--color-dark);
        margin-bottom: 20px;
    }
</style>
</head>
<body>
<div class="container">
    <h2 class="text-center">Productividad Académica</h2>
    <div class="row">
        <div class="col-md-6 category">
            <h5 class="text-center">Reconocimiento Puntos Salariales</h5>
            <div class="btn-group-custom">
                 <a href="#" class="btn btn-icon" data-toggle="modal" data-target="#modalBusquedaArticulo">
        <i class="fas fa-newspaper"></i> Publicación en revista especializada
    </a>
                 <a href="trabajo_cientifico.php" class="btn btn-icon">
    <i class="fas fa-flask"></i> Trabajo de carácter científico, técnico
</a>
          <a href="libros_modal.php" class="btn btn-icon">
    <i class="fas fa-book"></i> Libros
</a>
                <a href="premios.php" class="btn btn-icon">
                    <i class="fas fa-trophy"></i> Premios
                </a>
               <a href="patentes.php" class="btn btn-icon">
    <i class="fas fa-lightbulb"></i> Patente de invención
</a>
                <a href="traduccion_libro.php" class="btn btn-icon">
                    <i class="fas fa-language"></i> Traducción de libro
                </a>
                <a href="obra_artistica.php" class="btn btn-icon">
                    <i class="fas fa-paint-brush"></i> Obra de creación artística
                </a>
                <a href="innovacion_tecnologica.php" class="btn btn-icon">
                    <i class="fas fa-cogs"></i> Diseño de sistemas o procesos
                </a>
                <a href="titulos.php" class="btn btn-icon">
                    <i class="fas fa-graduation-cap"></i> Títulos de Postgrado
                </a>
                <a href="produccion.php" class="btn btn-icon">
                    <i class="fas fa-industry"></i> Producción
                </a>
            </div>
        </div>

        <div class="col-md-6 category">
            <h5 class="text-center">Reconocimientos por Bonificación</h5>
            <div class="btn-group-custom">
                <a href="trabajo_cientifico_bon.php" class="btn btn-icon">
                    <i class="fas fa-flask"></i> Trabajo de carácter científico, técnico o artístico
                </a>
                <a href="obra_artistica_bon.php" class="btn btn-icon">
                    <i class="fas fa-paint-brush"></i> Obra de creación artística
                </a>
                <a href="publicacion_impresa_bon.php" class="btn btn-icon">
                    <i class="fas fa-newspaper"></i> Publicaciones impresas
                </a>
                <a href="ponencias_bon.php" class="btn btn-icon">
                    <i class="fas fa-file-alt"></i> Ponencias
                </a>
                <a href="estudio_posdoctoral_bon.php" class="btn btn-icon">
                    <i class="fas fa-graduation-cap"></i> Estudio Postdoctoral
                </a>
                <a href="resena_bon.php" class="btn btn-icon">
                    <i class="fas fa-book-open"></i> Reseña crítica
                </a>
                <a href="traduccion_articulo.php" class="btn btn-icon">
                    <i class="fas fa-language"></i> Traducción de artículo publicada en revista o libro
                </a>
                <a href="direccion_tesis.php" class="btn btn-icon">
                    <i class="fas fa-user-graduate"></i> Dirección de Tesis
                </a>
            </div>
        </div>
    </div>
    <div class="text-end mt-3">
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Regresar
    </a>
</div>
</div>

<!-- Modal  articulos-->
    <div class="modal fade" id="modalBusquedaArticulo" tabindex="-1" role="dialog" aria-labelledby="modalBusquedaArticuloLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBusquedaArticuloLabel">Búsqueda de Artículo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="buscar_limpio.php" method="POST">
                        <div class="form-group">
                            <label for="issn">ISSN:</label>
                            <input type="text" id="issn" name="issn" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="nombre_articulo">Nombre del Artículo:</label>
                            <input type="text" id="nombre_articulo" name="nombre_articulo" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Buscar</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Modal de Trabajo Científico -->
<div class="modal fade" id="modalTrabajoCientifico" tabindex="-1" role="dialog" aria-labelledby="modalTrabajoCientificoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrabajoCientificoLabel">Trabajo de Carácter Científico</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario -->
                <form action="guardar_trabajo.php" method="post">
                    <?php
                    $identificador_base = date('Y_m');
                    ?>
                    
                    <div class="row mb-3">
                        <!-- Identificador -->
                        <div class="col-md-6">
                            <label for="identificador_base" class="form-label fw-bold">Identificador:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="identificador_base" name="identificador_base" 
                                       value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" placeholder="Año_Mes" required>
                                <select class="form-select form-select-sm" id="numero_envio" name="numero_envio" style="width: 50px;" required>
                                    <?php for ($i = 1; $i <= 9; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == 1 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Número de Oficio -->
                        <div class="col-md-6">
                            <label for="inputTrdFac" class="form-label fw-bold">Número de oficio:</label>
                            <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control" required>
                        </div>
                    </div>

                    <!-- Número de Profesores -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="numero_profesores" class="form-label">Número de Profesores:</label>
                            <input type="number" id="numero_profesores" min="1" class="form-control" placeholder="Ingrese el número de profesores">
                        </div>
                    </div>

                    <!-- Contenedor para documentos -->
                    <div id="contenedor_documentos" class="mb-3"></div>

                    <!-- Campos adicionales -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="producto" class="form-label">Nombre del producto</label>
                            <input type="text" class="form-control" name="producto" id="producto" placeholder="Ingrese el nombre del producto">
                        </div>
                        <div class="col-md-4">
                            <label for="difusion" class="form-label">Difusión</label>
                            <input type="text" class="form-control" name="difusion" id="difusion" placeholder="Ingrese la difusión">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="finalidad" class="form-label">Finalidad</label>
                            <input type="text" class="form-control" name="finalidad" id="finalidad" placeholder="Ingrese la finalidad">
                        </div>
                        <div class="col-md-6">
                            <label for="area" class="form-label">Área disciplinar</label>
                            <input type="text" class="form-control" name="area" id="area" placeholder="Ingrese el área disciplinar">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="evaluador1" class="form-label">Evaluador 1 (puntaje)</label>
                            <input type="number" class="form-control" name="evaluador1" id="evaluador1" step="0.1" placeholder="Ingrese puntaje de evaluador 1">
                        </div>
                        <div class="col-md-6">
                            <label for="evaluador2" class="form-label">Evaluador 2 (puntaje)</label>
                            <input type="number" class="form-control" name="evaluador2" id="evaluador2" step="0.1" placeholder="Ingrese puntaje de evaluador 2">
                        </div>
                        <div class="col-md-12">
                            <label for="promedio" class="fw-bold">Promedio de Evaluadores:</label>
                            <input type="text" class="form-control" id="promedio" readonly placeholder="El promedio se mostrará aquí">
                        </div>
                    </div>

                    <!-- Campo de Puntaje -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="puntaje" class="form-label">Puntaje Total</label>
                            <input type="text" class="form-control" id="puntaje" name="puntaje">
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row mb-3">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary mt-3">Enviar</button>
                            <button type="button" class="btn btn-secondary mt-3" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

</div>


    
    <!--modal LIBROS -->

    <!-- Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>



</body>
</html>
