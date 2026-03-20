<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traducción de Artículo | CIARP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; color: #4a4a4a; }
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background-color: #ffffff;
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2c3e50;
            border-bottom: 2px solid #eef2f7;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 20px;
        }
        #contenedor_documentos {
            background-color: #f8fafd;
            border-radius: 10px;
            border-left: 5px solid #10b981; /* Verde para bonificaciones */
            transition: all 0.3s ease;
        }
        .form-control:focus, .custom-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.1);
        }
        label { font-size: 0.85rem; margin-bottom: 0.4rem; font-weight: 600; }
        .puntaje-destacado {
            font-size: 1.2rem;
            font-weight: 800;
            color: #10b981;
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        .badge-bono {
            background-color: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom">
            <div class="text-center mb-2">
                <span class="badge-bono"><i class="fas fa-globe mr-1"></i> Módulo de Bonificación</span>
            </div>
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                Traducción de Artículo
            </h2>
            <p class="text-center text-muted small mb-4">Traducción de artículo publicada en revista o libro</p>

            <form action="guardar_traduc_bon.php" method="post">
                <?php $identificador_base = date('Y_m'); ?>

                <div class="section-title">Información de Solicitud</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Identificador:</label>
                        <div class="input-group shadow-sm">
                            <input type="text" class="form-control" id="identificador_base" name="identificador_base" 
                                   value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" required>
                            <div class="input-group-append">
                                <select class="custom-select" name="numero_envio" style="max-width: 65px;">
                                    <?php for ($i = 1; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-success"># Profesores Solicitantes:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control border-success shadow-sm" placeholder="Ej: 1" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Número de Oficio:</label>
                        <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control shadow-sm" placeholder="Oficio TRD" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Fecha de Solicitud:</label>
                        <input type="date" name="fecha_solicitud" class="form-control shadow-sm" required>
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles de la Traducción</div>
                <div class="form-row mb-3">
                    <div class="form-group col-md-12">
                        <label for="producto">Nombre del Producto / Artículo Traducido:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" id="producto" placeholder="Título completo del artículo" required>
                    </div>
                </div>

                <div class="section-title">Bonificación Asignada</div>
                <div class="form-row justify-content-center">
                    <div class="form-group col-md-4 text-center">
                        <label class="text-success fw-bold">Puntaje Total Salarial</label>
                        <input type="number" class="form-control puntaje-destacado text-center shadow-sm" id="puntaje" name="puntaje" step="0.01" min="0" value="36.00" required>
                        <small class="text-muted">Valor base sugerido según normativa.</small>
                    </div>
                </div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end align-items-center">
                    <a href="index.php" class="btn btn-link text-muted mr-3">Cancelar</a>
                    <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        const numeroProfesoresInput = document.getElementById('numero_profesores');
        const contenedorDocumentos = document.getElementById('contenedor_documentos');
        const numeroOficioInput = document.getElementById('inputTrdFac');

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = '';
            const cantidad = parseInt(numeroProfesoresInput.value);
            
            if (isNaN(cantidad) || cantidad < 1) {
                contenedorDocumentos.style.padding = "0";
                return;
            }

            contenedorDocumentos.style.padding = "20px";
            for (let i = 1; i <= cantidad; i++) {
                const div = document.createElement('div');
                div.className = 'form-row align-items-center mb-3';
                div.innerHTML = `
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Cédula Solicitante ${i}:</label>
                        <input type="text" id="documento_${i}" name="documento_${i}" class="form-control form-control-sm shadow-sm" required>
                    </div>
                    <div class="col-md-9">
                        <label class="small text-muted">Datos del Profesor:</label>
                        <div id="datos_${i}" class="alert alert-light border m-0 p-1 small shadow-sm" style="min-height: 31px; display: flex; align-items: center;">
                            <i class="fas fa-user-check mr-2 text-muted"></i> Esperando identificación...
                        </div>
                    </div>
                `;
                contenedorDocumentos.appendChild(div);

                document.getElementById(`documento_${i}`).addEventListener('blur', function() {
                    buscarDatos(this, i);
                });
            }
        });

        function buscarDatos(input, index) {
            const documento = input.value.trim();
            if (documento === '') return;

            const datosContainer = document.getElementById(`datos_${index}`);
            datosContainer.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-success"></i>Consultando...';

            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        datosContainer.innerHTML = `<i class="fas fa-exclamation-circle text-danger mr-2"></i><span class="text-danger">${data.error}</span>`;
                    } else {
                        datosContainer.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i><strong>${data.nombre_completo}</strong> | ${data.nombre_depto} | ${data.nombre_fac}`;
                        if (index === 1 && data.numero_oficio) {
                            numeroOficioInput.value = data.numero_oficio;
                        }
                    }
                })
                .catch(() => {
                    datosContainer.innerHTML = '<i class="fas fa-times-circle text-danger mr-2"></i>Error al cargar los datos';
                });
        }
    </script>
</body>
</html>