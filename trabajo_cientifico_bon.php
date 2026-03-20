<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonificación - Trabajo Científico | CIARP</title>
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
        .promedio-destacado {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            font-weight: 700;
        }
        .puntaje-destacado {
            font-weight: 800;
            color: #10b981;
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        /* Etiqueta superior para diferenciar bonificación */
        .badge-bono {
            background-color: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom">
            <div class="text-center mb-2">
                <span class="badge-bono"><i class="fas fa-star mr-1"></i> Módulo de Bonificación</span>
            </div>
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                <i class="fas fa-atom text-success mr-2"></i>Trabajo de Carácter Científico
            </h2>

            <form action="guardar_trabajo_bon.php" method="post">
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
                        <label class="text-success"># Profesores Solicitantes:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control border-success shadow-sm" placeholder="Ej: 1" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Número de Oficio:</label>
                        <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control shadow-sm" placeholder="Oficio TRD" required>
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles del Trabajo Científico</div>
                <div class="form-row mb-3">
                    <div class="form-group col-md-8">
                        <label for="producto">Nombre del Producto:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" id="producto" placeholder="Ingrese el título del trabajo" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="difusion">Nivel de Difusión:</label>
                        <select class="custom-select shadow-sm" name="difusion" id="difusion" required>
                            <option value="REGIONAL">Regional</option>
                            <option value="LOCAL">Local</option>
                        </select>
                    </div>
                </div>

                <div class="form-row mb-3">
                    <div class="form-group col-md-6">
                        <label for="finalidad">Finalidad:</label>
                        <select class="custom-select shadow-sm" name="finalidad" id="finalidad" required>
                            <option value="didactico">Didáctico</option>
                            <option value="documental">Documental</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="area">Área Disciplinar:</label>
                        <input type="text" class="form-control shadow-sm" name="area" id="area" placeholder="Campo de conocimiento" required>
                    </div>
                </div>

                <div class="section-title">Evaluación y Puntaje Asignado</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Evaluador 1 (Nota):</label>
                        <input type="number" class="form-control shadow-sm" name="evaluador1" id="evaluador1" step="0.1" placeholder="0.0" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Evaluador 2 (Nota):</label>
                        <input type="number" class="form-control shadow-sm" name="evaluador2" id="evaluador2" step="0.1" placeholder="0.0" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-info">Promedio Evaluadores:</label>
                        <input type="text" class="form-control promedio-destacado shadow-sm" id="promedio" readonly placeholder="Calculando...">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-success">Total Bonificación:</label>
                        <input type="number" class="form-control puntaje-destacado text-center shadow-sm" id="puntaje" name="puntaje" step="0.01" min="0" placeholder="0.00" readonly required>
                    </div>
                </div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end align-items-center">
                    <a href="index.php" class="btn btn-link text-muted mr-3">Cancelar</a>
                    <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">
                        <i class="fas fa-save mr-2"></i>Guardar Bonificación
                    </button>
                </div>
                
                <input type="hidden" id="hidden_numero_profesores" name="hidden_numero_profesores" value="0">
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        const evaluador1Input = document.getElementById('evaluador1');
        const evaluador2Input = document.getElementById('evaluador2');
        const promedioInput = document.getElementById('promedio');
        const puntajeInput = document.getElementById('puntaje');
        const numProfInput = document.getElementById('numero_profesores');
        const hiddenNumInput = document.getElementById('hidden_numero_profesores');
        const contDocs = document.getElementById('contenedor_documentos');
        const ofiInput = document.getElementById('inputTrdFac');

        // 1. CÁLCULO DE PROMEDIO Y PUNTAJE (Lógica unificada)
        function calcularValores() {
            const eval1 = parseFloat(evaluador1Input.value) || 0;
            const eval2 = parseFloat(evaluador2Input.value) || 0;
            const autores = parseInt(numProfInput.value) || 1;

            if (eval1 > 0 && eval2 > 0) {
                // Promedio
                const suma = eval1 + eval2;
                const promedioEvaluadores = suma / 2;
                promedioInput.value = `(${eval1} + ${eval2}) / 2 = ${promedioEvaluadores.toFixed(2)}%`;

                // Puntaje Base (Promedio * 48)
                const puntajeBase = promedioEvaluadores * 48;
                let puntajeFinal;

                // Reglas por cantidad de autores
                if (autores <= 3) {
                    puntajeFinal = puntajeBase;
                } else if (autores >= 4 && autores <= 5) {
                    puntajeFinal = puntajeBase / 2;
                } else {
                    puntajeFinal = puntajeBase / (autores / 2);
                }

                // Lógica de redondeo heredada del código original
                puntajeInput.value = (Math.floor(puntajeFinal) / 100).toFixed(2);
            } else {
                promedioInput.value = '';
                puntajeInput.value = '';
            }
        }

        evaluador1Input.addEventListener('input', calcularValores);
        evaluador2Input.addEventListener('input', calcularValores);
        numProfInput.addEventListener('input', calcularValores);

        // 2. GENERACIÓN DINÁMICA DE PROFESORES Y BÚSQUEDA AJAX
        numProfInput.addEventListener('input', () => {
            contDocs.innerHTML = '';
            const cantidad = parseInt(numProfInput.value);
            hiddenNumInput.value = isNaN(cantidad) ? 0 : cantidad;

            if (isNaN(cantidad) || cantidad < 1) {
                contDocs.style.padding = "0";
                return;
            }

            contDocs.style.padding = "20px";
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
                            <i class="fas fa-id-badge mr-2 text-muted"></i> Esperando identificación...
                        </div>
                    </div>
                `;
                contDocs.appendChild(div);

                document.getElementById(`documento_${i}`).addEventListener('blur', function() {
                    buscarDatos(this, i);
                });
            }
        });

        function buscarDatos(input, index) {
            const documento = input.value.trim();
            if (documento === '') return;

            const datosContainer = document.getElementById(`datos_${index}`);
            datosContainer.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-success"></i>Buscando docente...';

            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        datosContainer.innerHTML = `<i class="fas fa-exclamation-circle text-danger mr-2"></i><span class="text-danger">${data.error}</span>`;
                    } else {
                        datosContainer.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i><strong>${data.nombre_completo}</strong> | ${data.nombre_depto} | ${data.nombre_fac}`;
                        if (index === 1 && data.numero_oficio) {
                            ofiInput.value = data.numero_oficio;
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