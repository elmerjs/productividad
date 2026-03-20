<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovación Tecnológica | CIARP</title>
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
            border-left: 5px solid #007bff;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.1);
        }
        label { font-size: 0.85rem; margin-bottom: 0.4rem; font-weight: 600; }
        .puntaje-destacado {
            font-weight: 800;
            color: #10b981;
            border-color: #10b981;
            background-color: #f0fdf4;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom">
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                <i class="fas fa-microchip text-primary mr-2"></i>Innovación Tecnológica
            </h2>
            <p class="text-center text-muted small mb-4">Diseño de sistemas o procesos que constituyen innovación tecnológica</p>

            <form action="guardar_innovacion.php" method="post"> 
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
                                    <?php for ($i = 1; $i <= 9; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-primary"># Profesores Solicitantes:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control border-primary shadow-sm" placeholder="Ej: 1" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Número de Oficio:</label>
                        <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control shadow-sm" placeholder="Oficio TRD" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Fecha Solicitud:</label>
                        <input type="date" name="fecha_solicitud" class="form-control shadow-sm" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles del Producto</div>
                <div class="form-row mb-3">
                    <div class="form-group col-md-8">
                        <label for="producto">Nombre del Producto / Sistema:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" id="producto" placeholder="Título de la innovación tecnológica" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="impacto">Impacto y Aplicación:</label>
                        <select id="impacto" name="impacto" class="custom-select shadow-sm" required>
                            <option value="INTERNACIONAL">INTERNACIONAL</option>
                            <option value="NACIONAL">NACIONAL</option>
                        </select>
                    </div>
                </div>

                <div class="section-title">Puntaje Asignado</div>
                <div class="form-row justify-content-center">
                    <div class="form-group col-md-4 text-center">
                        <label class="text-success fw-bold">Puntaje Total Salarial</label>
                        <input type="number" class="form-control puntaje-destacado text-center shadow-sm" id="puntaje" name="puntaje" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                </div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end align-items-center">
                    <a href="index.php" class="btn btn-link text-muted mr-3">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                        <i class="fas fa-save mr-2"></i>Guardar Innovación
                    </button>
                </div>

                <input type="hidden" id="hidden_numero_profesores" name="hidden_numero_profesores" value="0">
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        const numeroProfesoresInput = document.getElementById('numero_profesores');
        const contenedorDocumentos = document.getElementById('contenedor_documentos');
        const numeroOficioInput = document.getElementById('inputTrdFac');
        const hiddenNumProfesores = document.getElementById('hidden_numero_profesores');

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = '';
            const cantidad = parseInt(numeroProfesoresInput.value);
            hiddenNumProfesores.value = isNaN(cantidad) ? 0 : cantidad;

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
            datosContainer.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-primary"></i>Consultando...';

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
                    datosContainer.innerHTML = '<i class="fas fa-times-circle text-danger mr-2"></i>Error de conexión';
                });
        }
    </script>
</body>
</html>