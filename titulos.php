<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Títulos Académicos | CIARP</title>
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
        .datos-container {
            font-size: 0.85rem;
            background-color: #f8fafd;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            min-height: 45px;
            display: flex;
            align-items: center;
            color: #334155;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.1);
        }
        label { font-size: 0.85rem; margin-bottom: 0.4rem; font-weight: 600; }
        .puntaje-destacado {
            font-size: 1.2rem;
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
                <i class="fas fa-graduation-cap text-primary mr-2"></i>Registro de Títulos
            </h2>

            <form action="guardar_titulo.php" method="post">
                <?php $identificador_base = date('Y_m'); ?>

                <div class="section-title">Información de Solicitud</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
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
                    <div class="form-group col-md-4">
                        <label>Número de Oficio:</label>
                        <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control shadow-sm" placeholder="Oficio TRD" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Documento Profesor:</label>
                        <div class="input-group shadow-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-id-card text-muted"></i></span>
                            </div>
                            <input type="text" id="documento_profesor" name="documento_profesor" class="form-control" oninput="buscarDatos(this)" placeholder="Cédula" required>
                        </div>
                    </div>
                </div>

                <div class="form-row mb-4">
                    <div class="col-md-12">
                        <div id="datos_profesor" class="datos-container shadow-sm">
                            <span class="text-muted"><i class="fas fa-info-circle mr-2"></i>Ingrese el documento para cargar los datos del docente.</span>
                        </div>
                    </div>
                </div>

                <div class="section-title">Detalles del Título Obtenido</div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Título Obtenido:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" id="producto" placeholder="Nombre completo del título" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Ubicación Institución:</label>
                        <select id="impacto" name="impacto" class="custom-select shadow-sm" onchange="toggleResolucionConvalidacion()">
                            <option value="NACIONAL" selected>NACIONAL</option>
                            <option value="EXTERIOR">EXTERIOR</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Nivel de Estudio:</label>
                        <select id="tipo_estudio" name="tipo_estudio" class="custom-select shadow-sm" onchange="actualizarPuntaje()">
                            <option value="DOCTORADO">Doctorado</option>
                            <option value="MAESTRIA">Maestría</option>
                            <option value="ESPECIALIZACION">Especialización</option>
                            <option value="ESPECIALIZACION_MEDICA">Especialización Médica</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Institución Educativa:</label>
                        <input type="text" class="form-control shadow-sm" name="institucion" id="institucion" placeholder="Nombre de la Universidad" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Fecha de Terminación:</label>
                        <input type="date" class="form-control shadow-sm" name="fecha_terminacion" id="fecha_terminacion" required>
                    </div>
                    <div class="form-group col-md-3" id="campo_resolucion_convalidacion" style="display: none;">
                        <label>Resolución Convalidación:</label>
                        <input type="text" class="form-control shadow-sm border-info" name="resolucion_convalidacion" placeholder="Nro. Resolución">
                    </div>
                    <div class="form-group col-md-3" id="campo_no_acta">
                        <label>N° Acta y Folio:</label>
                        <input type="text" class="form-control shadow-sm" name="no_acta" placeholder="Ej. 43, FOLIO 1315">
                    </div>
                </div>

                <div class="section-title">Puntaje Asignado</div>
                <div class="form-row justify-content-center">
                    <div class="form-group col-md-4 text-center">
                        <label class="text-success fw-bold">Puntaje Total Salarial</label>
                        <input type="number" class="form-control puntaje-destacado text-center shadow-sm" id="puntaje" name="puntaje" step="0.01" min="0" readonly required>
                        <small class="text-muted">Calculado automáticamente según nivel de estudio.</small>
                    </div>
                </div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end align-items-center">
                    <a href="index.php" class="btn btn-link text-muted mr-3">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                        <i class="fas fa-save mr-2"></i>Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        function actualizarPuntaje() {
            var tipoEstudio = document.getElementById("tipo_estudio").value;
            var puntaje = 0;
            switch(tipoEstudio) {
                case "DOCTORADO": puntaje = 80; break;
                case "MAESTRIA": puntaje = 40; break;
                case "ESPECIALIZACION": puntaje = 20; break;
                case "ESPECIALIZACION_MEDICA": puntaje = 15; break;
                default: puntaje = 0;
            }
            document.getElementById("puntaje").value = puntaje;
        }

        function buscarDatos(input) {
            const documento = input.value.trim(); 
            if (documento === '') return; 

            const datosContainer = document.getElementById('datos_profesor'); 
            datosContainer.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Buscando docente...'; 

            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        datosContainer.innerHTML = `<i class="fas fa-exclamation-circle text-danger mr-2"></i><span class="text-danger">${data.error}</span>`; 
                    } else {
                        datosContainer.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i><strong>${data.nombre_completo}</strong> | ${data.nombre_depto} | ${data.nombre_fac}`;
                        if (data.numero_oficio) {
                            document.getElementById('inputTrdFac').value = data.numero_oficio;
                        }
                    }
                })
                .catch(error => {
                    datosContainer.innerHTML = '<i class="fas fa-times-circle text-danger mr-2"></i>Error al cargar los datos';
                });
        }

        function toggleResolucionConvalidacion() {
            var tipo = document.getElementById('impacto').value; 
            var campoResolucion = document.getElementById('campo_resolucion_convalidacion'); 
            var campoNoActa = document.getElementById('campo_no_acta');

            if (tipo === 'NACIONAL') {
                campoResolucion.style.display = 'none';
                campoNoActa.style.display = 'block';
            } else {
                campoResolucion.style.display = 'block';
                campoNoActa.style.display = 'none';
            }
        }

        // Inicialización
        window.onload = function() {
            actualizarPuntaje();
            toggleResolucionConvalidacion();
        };
    </script>
</body>
</html>