<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabajo de Carácter Científico | CIARP</title>
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
            border-left: 5px solid #007bff;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.1);
        }
        label { font-size: 0.85rem; margin-bottom: 0.4rem; font-weight: 600; }
        .promedio-destacado {
            background-color: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #4338ca;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom">
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                <i class="fas fa-flask text-primary mr-2"></i>Trabajo de Carácter Científico
            </h2>

            <form action="guardar_trabajo.php" method="post">
                <?php $identificador_base = date('Y_m'); ?>

                <div class="section-title">Información de Solicitud</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Identificador:</label>
                        <div class="input-group shadow-sm">
                            <input type="text" class="form-control" id="identificador_base" name="identificador_base" 
                                   value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" required>
                            <div class="input-group-append">
                                <select class="custom-select" name="numero_envio" id="numero_envio" style="max-width: 60px;">
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

                <div class="section-title">Detalles del Trabajo Científico</div>
                <div class="form-row mb-3">
                    <div class="form-group col-md-8">
                        <label>Nombre del Producto:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" id="producto" placeholder="Título del trabajo" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Difusión:</label>
                        <input type="text" class="form-control shadow-sm" name="difusion" id="difusion" placeholder="Medio de difusión">
                    </div>
                </div>

                <div class="form-row mb-3">
                    <div class="form-group col-md-6">
                        <label>Finalidad:</label>
                        <input type="text" class="form-control shadow-sm" name="finalidad" id="finalidad" placeholder="Objetivo del trabajo">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Área Disciplinar:</label>
                        <input type="text" class="form-control shadow-sm" name="area" id="area" placeholder="Campo de conocimiento">
                    </div>
                </div>

                <div class="section-title">Evaluación y Puntaje Asignado</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Puntaje Evaluador 1:</label>
                        <input type="number" class="form-control shadow-sm" name="evaluador1" id="evaluador1" step="0.1" placeholder="0.0">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Puntaje Evaluador 2:</label>
                        <input type="number" class="form-control shadow-sm" name="evaluador2" id="evaluador2" step="0.1" placeholder="0.0">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-indigo">Promedio:</label>
                        <input type="text" class="form-control promedio-destacado shadow-sm" id="promedio" readonly placeholder="Calculando...">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-success">Puntaje Total Salarial:</label>
                        <input type="number" class="form-control font-weight-bold border-success text-success shadow-sm" id="puntaje" name="puntaje" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                </div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end align-items-center">
                    <a href="index.php" class="btn btn-link text-muted mr-3">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Solicitud
                    </button>
                </div>
                
                <input type="hidden" id="hidden_numero_profesores" name="hidden_numero_profesores" value="0">
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // Cálculo de Promedio
        const eval1 = document.getElementById('evaluador1');
        const eval2 = document.getElementById('evaluador2');
        const prom = document.getElementById('promedio');

        function calcularPromedio() {
            const v1 = parseFloat(eval1.value);
            const v2 = parseFloat(evaluador2.value);
            if (!isNaN(v1) && !isNaN(v2)) {
                const suma = v1 + v2;
                const resultado = (suma / 2).toFixed(2);
                prom.value = `${resultado}% (${suma}/2)`;
            } else {
                prom.value = '';
            }
        }
        eval1.addEventListener('input', calcularPromedio);
        eval2.addEventListener('input', calcularPromedio);

        // Manejo Dinámico de Profesores
        const numProfInput = document.getElementById('numero_profesores');
        const contDocs = document.getElementById('contenedor_documentos');
        const hiddenNum = document.getElementById('hidden_numero_profesores');
        const ofiInput = document.getElementById('inputTrdFac');

        numProfInput.addEventListener('input', () => {
            contDocs.innerHTML = '';
            const cant = parseInt(numProfInput.value);
            hiddenNum.value = isNaN(cant) ? 0 : cant;

            if (isNaN(cant) || cant < 1) {
                contDocs.style.padding = "0";
                return;
            }

            contDocs.style.padding = "20px";
            for (let i = 1; i <= cant; i++) {
                const div = document.createElement('div');
                div.className = 'form-row align-items-center mb-3';
                div.innerHTML = `
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Cédula Solicitante ${i}:</label>
                        <input type="text" id="documento_${i}" name="documento_${i}" class="form-control form-control-sm shadow-sm" required>
                    </div>
                    <div class="col-md-9">
                        <label class="small text-muted">Datos del Profesor:</label>
                        <div id="datos_${i}" class="alert alert-light border m-0 p-1 small shadow-sm" style="min-height: 31px;">
                            Esperando identificación...
                        </div>
                    </div>
                `;
                contDocs.appendChild(div);
                document.getElementById(`documento_${i}`).addEventListener('blur', function() {
                    buscarDocente(this, i);
                });
            }
        });

        function buscarDocente(input, index) {
            const doc = input.value.trim();
            if (doc === '') return;

            const resCont = document.getElementById(`datos_${index}`);
            resCont.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Consultando...';

            fetch(`obtener_datos_profesor.php?documento=${doc}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        resCont.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-circle mr-2"></i>${data.error}</span>`;
                    } else {
                        resCont.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i><strong>${data.nombre_completo}</strong> | ${data.nombre_depto}`;
                        if (index === 1 && data.numero_oficio) {
                            ofiInput.value = data.numero_oficio;
                        }
                    }
                })
                .catch(() => {
                    resCont.innerHTML = '<span class="text-danger">Error de conexión</span>';
                });
        }
    </script>
</body>
</html>