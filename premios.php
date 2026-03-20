<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Premios | CIARP</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; color: #4a4a4a; }
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background-color: #ffffff;
            padding: 30px;
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
        label { font-size: 0.85rem; margin-bottom: 0.4rem; }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="card-custom">
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                <i class="fas fa-medal text-warning mr-2"></i>Formulario de Premios
            </h2>
            
            <form action="guardar_premio.php" method="post">
                
                <div class="section-title">Información de Solicitud</div>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="font-weight-bold">Identificador:</label>
                        <div class="input-group">
                            <?php $identificador_base = date('Y_m'); ?>
                            <input type="text" class="form-control" name="identificador_base" 
                                   value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" required>
                            <div class="input-group-append">
                                <select class="custom-select" name="numero_envio" style="border-radius: 0 5px 5px 0;">
                                    <?php for ($i = 1; $i <= 9; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold text-primary"># Profesores Solicitantes:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control border-primary" placeholder="Ej: 1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">Número de Oficio:</label>
                        <input type="text" id="numeroOficio" name="numeroOficio" class="form-control" placeholder="Oficio TRD" required>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">Fecha Solicitud:</label>
                        <input type="date" class="form-control" name="fecha_solicitud" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles del Reconocimiento</div>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="font-weight-bold">Nombre del Evento o Premio:</label>
                        <input type="text" class="form-control" name="nombre_evento" placeholder="Nombre completo del galardón" required>
                    </div>
                    <div class="col-md-4">
                        <label class="font-weight-bold">Autores (Cantidad):</label>
                        <input type="text" class="form-control" name="autores" placeholder="Ej: 3 autores" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="font-weight-bold">Lugar y Fecha de Realización:</label>
                        <input type="text" class="form-control" name="lugar_fecha" placeholder="Ciudad, País - Fecha" required>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">Ámbito:</label>
                        <select class="custom-select" name="ambito" required>
                            <option value="">Seleccione...</option>
                            <option value="NACIONAL">NACIONAL</option>
                            <option value="INTERNACIONAL">INTERNACIONAL</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">Puntos:</label>
                        <input type="number" class="form-control font-weight-bold text-success" name="puntos" step="0.01" placeholder="0.00" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="font-weight-bold">Categorías/Niveles del Premio:</label>
                        <input type="text" class="form-control" name="categoria_premio" placeholder="Ej: Investigador Senior, Primer Puesto" required>
                    </div>
                    <div class="col-md-6">
                        <label class="font-weight-bold">Nivel/Categoría Ganada:</label>
                        <input type="text" class="form-control" name="nivel_ganado" placeholder="Especifique el logro obtenido" required>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end align-items-center">
                    <a href="index.php" class="btn btn-link text-muted mr-3">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
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

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = '';
            const cantidad = parseInt(numeroProfesoresInput.value);
            if (isNaN(cantidad) || cantidad < 1) {
                contenedorDocumentos.style.padding = "0";
                return;
            }

            contenedorDocumentos.style.padding = "20px";
            for (let i = 1; i <= cantidad; i++) {
                const row = document.createElement('div');
                row.classList.add('form-row', 'align-items-center', 'mb-3');

                row.innerHTML = `
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Cédula Profesor ${i}:</label>
                        <input type="text" id="cedula_${i}" name="cedula_${i}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-9">
                        <label class="small text-muted">Datos del Docente:</label>
                        <div id="datos_${i}" class="alert alert-light border m-0 p-1 small shadow-sm" style="min-height: 31px;">
                            Esperando identificación...
                        </div>
                    </div>
                `;
                contenedorDocumentos.appendChild(row);

                document.getElementById(`cedula_${i}`).addEventListener('blur', function() {
                    buscarDatos(this, i);
                });
            }
        });

        function buscarDatos(input, index) {
            const documento = input.value.trim();
            if (documento === '') return;

            const datosContainer = document.getElementById(`datos_${index}`);
            datosContainer.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Buscando...';

            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        datosContainer.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-circle mr-2"></i>${data.error}</span>`;
                    } else {
                        datosContainer.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i><strong>${data.nombre_completo}</strong> | Depto: ${data.nombre_depto}`;
                        
                        // Prellenado de oficio UX
                        if (index === 1 && data.numero_oficio) {
                            document.getElementById('numeroOficio').value = data.numero_oficio;
                        }
                    }
                })
                .catch(() => {
                    datosContainer.innerHTML = '<span class="text-danger">Error de conexión</span>';
                });
        }
    </script>
</body>
</html>