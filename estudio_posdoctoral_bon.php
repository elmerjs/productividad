<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudio Posdoctoral | CIARP</title>
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
            border-left: 4px solid #10b981; /* Verde para bonificaciones */
            min-height: 45px;
            display: flex;
            align-items: center;
            color: #334155;
            transition: all 0.3s ease;
        }
        .form-control:focus {
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
                <span class="badge-bono"><i class="fas fa-user-graduate mr-1"></i> Módulo de Bonificación</span>
            </div>
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                Estudio Posdoctoral
            </h2>

            <form action="guardar_posdoctoral.php" method="post">
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
                                    <?php for ($i = 1; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?>
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
                            <span class="text-muted small"><i class="fas fa-info-circle mr-2"></i>Ingrese el documento para cargar los datos del docente.</span>
                        </div>
                    </div>
                </div>

                <div class="section-title">Detalles del Estudio Posdoctoral</div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Título Obtenido / Certificación:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" id="producto" placeholder="Nombre de la certificación posdoctoral" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Institución Educativa:</label>
                        <input type="text" class="form-control shadow-sm" name="institucion" id="institucion" placeholder="Universidad o Centro de Investigación" required>
                    </div>
                </div>

                <div class="form-row mb-3">
                    <div class="form-group col-md-4">
                        <label>Fecha de Terminación:</label>
                        <input type="date" class="form-control shadow-sm" name="fecha_terminacion" id="fecha_terminacion" required>
                    </div>
                    <div class="form-group col-md-8 text-center">
                        <label class="text-success fw-bold">Bonificación Asignada (Puntos)</label>
                        <input type="number" class="form-control puntaje-destacado text-center shadow-sm mx-auto" id="puntaje" name="puntaje" step="0.01" min="0" readonly style="max-width: 250px;">
                        <small class="text-muted">Valor fijo según normativa para estudios posdoctorales.</small>
                    </div>
                </div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end align-items-center">
                    <a href="index.php" class="btn btn-link text-muted mr-3">Cancelar</a>
                    <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">
                        <i class="fas fa-save mr-2"></i>Guardar Bonificación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // Establecer puntaje inicial (120 puntos fijos)
        function actualizarPuntaje() {
            document.getElementById("puntaje").value = 120;
        }

        function buscarDatos(input) {
            const documento = input.value.trim();
            if (documento === '') return;

            const datosContainer = document.getElementById('datos_profesor');
            datosContainer.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-success"></i>Buscando docente en el sistema...';

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
                .catch(() => {
                    datosContainer.innerHTML = '<i class="fas fa-times-circle text-danger mr-2"></i>Error al cargar los datos';
                });
        }

        window.onload = actualizarPuntaje;
    </script>
</body>
</html>