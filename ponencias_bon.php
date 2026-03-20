<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonificación - Ponencias | CIARP</title>
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
            margin: 30px 0;
        }
        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2c3e50;
            border-bottom: 2px solid #eef2f7;
            padding-bottom: 10px;
            margin: 20px 0;
        }
        #contenedor_documentos {
            background-color: #f8fafd;
            border-radius: 10px;
            border-left: 5px solid #10b981; /* Verde Bonificación */
            transition: all 0.3s ease;
        }
        .form-control:focus, .custom-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.1);
        }
        label { font-size: 0.85rem; margin-bottom: 0.4rem; font-weight: 600; }
        .puntaje-destacado {
            font-weight: 800;
            color: #10b981;
            border-color: #10b981 !important;
            background-color: #f0fdf4 !important;
            font-size: 1.1rem;
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
                <span class="badge-bono"><i class="fas fa-chalkboard-teacher mr-1"></i> Módulo de Bonificación</span>
            </div>
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">Ponencias</h2>

            <form action="guardar_ponencias_bon.php" method="post">
                <?php $identificador_base = date('Y_m'); ?>

                <div class="section-title">Información de Solicitud</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Identificador:</label>
                        <div class="input-group shadow-sm">
                            <input type="text" class="form-control" name="identificador_base" value="<?= $identificador_base ?>" maxlength="7" pattern="\d{4}_\d{2}" required>
                            <div class="input-group-append">
                                <select class="custom-select" name="numero_envio" style="max-width: 60px;">
                                    <?php for ($i = 1; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-success"># Profesores Solicitantes:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control border-success shadow-sm" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Número de Oficio:</label>
                        <input type="text" id="numeroOficio" name="numeroOficio" class="form-control shadow-sm" placeholder="Oficio TRD" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Fecha Solicitud:</label>
                        <input type="date" name="fecha_solicitud" class="form-control shadow-sm" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles de la Ponencia</div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Grado de Difusión Geográfico:</label>
                        <select name="difusion" id="difusion" class="custom-select shadow-sm" required>
                            <option value="" disabled selected>Seleccione...</option>
                            <option value="regional">Evento Regional (Max 24 pts)</option>
                            <option value="nacional">Evento Nacional (Max 48 pts)</option>
                            <option value="internacional">Evento Internacional (Max 84 pts)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Título de la Ponencia:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" placeholder="Nombre completo de la ponencia" required>
                    </div>
                </div>
                <div class="form-row mb-3">
                    <div class="form-group col-md-6">
                        <label>Nombre del Evento:</label>
                        <input type="text" class="form-control shadow-sm" name="nombre_evento" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Lugar:</label>
                        <input type="text" class="form-control shadow-sm" name="lugar_evento" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Fecha del Evento:</label>
                        <input type="date" class="form-control shadow-sm" name="fecha_evento" required>
                    </div>
                </div>

                <div class="section-title">Evaluación y Cálculo de Bonificación</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label>Autores:</label>
                        <input type="number" id="autores" name="autores" class="form-control shadow-sm" min="1" value="1">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Evaluación 1:</label>
                        <input type="number" id="evaluacion1" name="evaluacion1" class="form-control shadow-sm" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Evaluación 2:</label>
                        <input type="number" id="evaluacion2" name="evaluacion2" class="form-control shadow-sm" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-success font-weight-bold">Puntaje Final:</label>
                        <input type="text" id="puntaje_f" name="puntaje_f" class="form-control puntaje-destacado text-center shadow-sm" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="small text-muted">Memoria de cálculo (Basado en Artículo 9):</label>
                    <input type="text" id="puntaje" class="form-control form-control-sm bg-light shadow-none" readonly>
                </div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end align-items-center">
                    <button type="button" class="btn btn-link text-muted mr-3" onclick="window.history.back();">Cancelar</button>
                    <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">
                        <i class="fas fa-save mr-2"></i>Guardar Bonificación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // --- LÓGICA DE CÁLCULO ---
        const autoresInput = document.getElementById('autores');
        const eval1Input = document.getElementById('evaluacion1');
        const eval2Input = document.getElementById('evaluacion2');
        const difSelect = document.getElementById('difusion');
        const puntajeMemo = document.getElementById('puntaje');
        const puntajeFinal = document.getElementById('puntaje_f');

        function getPorcentaje(promedio) {
            if (promedio >= 95) return { p: 1.0, t: "Excelente (100%)" };
            if (promedio >= 90) return { p: 0.9, t: "Sobresaliente (90%)" };
            if (promedio >= 80) return { p: 0.8, t: "Bueno (80%)" };
            if (promedio >= 70) return { p: 0.7, t: "Aceptable (70%)" };
            return { p: 0, t: "No bonifica (<70)" };
        }

        function calcularPuntaje() {
            const autores = parseInt(autoresInput.value) || 1;
            const e1 = parseFloat(eval1Input.value) || 0;
            const e2 = parseFloat(eval2Input.value) || 0;
            const dif = difSelect.value;

            if (e1 > 0 && e2 > 0 && dif) {
                const promedio = (e1 + e2) / 2;
                const { p: factorBono, t: rangoLabel } = getPorcentaje(promedio);
                
                let maxP = (dif === 'internacional') ? 84 : (dif === 'nacional') ? 48 : 24;
                const puntajeBase = factorBono * maxP;
                
                let final;
                let detalleAutores = "";

                if (autores <= 3) { final = puntajeBase; detalleAutores = "(100% puntaje)"; }
                else if (autores <= 5) { final = puntajeBase / 2; detalleAutores = "(50% puntaje)"; }
                else { final = puntajeBase / (autores / 2); detalleAutores = `(Dividido por ${autores/2})`; }

                puntajeMemo.value = `Promedio: ${promedio.toFixed(2)} [${rangoLabel}] -> ${maxP} * ${factorBono * 100}% = ${puntajeBase.toFixed(2)} pts ${detalleAutores}`;
                puntajeFinal.value = (Math.floor(final * 100) / 100).toFixed(2);
            } else {
                puntajeMemo.value = '';
                puntajeFinal.value = '';
            }
        }

        [autoresInput, eval1Input, eval2Input, difSelect].forEach(el => el.addEventListener('input', calcularPuntaje));

        // --- GESTIÓN DE PROFESORES ---
        const numProfInput = document.getElementById('numero_profesores');
        const contDocs = document.getElementById('contenedor_documentos');
        const ofiInput = document.getElementById('numeroOficio');

        numProfInput.addEventListener('input', () => {
            contDocs.innerHTML = '';
            const cant = parseInt(numProfInput.value);
            if (isNaN(cant) || cant < 1) { contDocs.style.padding = "0"; return; }
            contDocs.style.padding = "20px";
            for (let i = 1; i <= cant; i++) {
                const div = document.createElement('div');
                div.className = 'form-row align-items-center mb-3';
                div.innerHTML = `
                    <div class="col-md-3"><label class="small font-weight-bold">Cédula Profesor ${i}:</label>
                    <input type="text" id="doc_${i}" name="cedulaProfesor${i}" class="form-control form-control-sm shadow-sm" required></div>
                    <div class="col-md-9"><label class="small text-muted">Datos del Profesor:</label>
                    <div id="datos_${i}" class="alert alert-light border m-0 p-1 small shadow-sm" style="min-height:31px; display:flex; align-items:center;">
                        <i class="fas fa-user mr-2 opacity-50"></i> Esperando identificación...
                    </div></div>`;
                contDocs.appendChild(div);
                document.getElementById(`doc_${i}`).addEventListener('blur', function() { buscarDocente(this, i); });
            }
        });

        function buscarDocente(input, idx) {
            if (!input.value.trim()) return;
            const res = document.getElementById(`datos_${idx}`);
            res.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-success"></i>Buscando...';
            fetch(`obtener_datos_profesor.php?documento=${input.value}`)
                .then(r => r.json()).then(data => {
                    if (data.error) res.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>${data.error}</span>`;
                    else {
                        res.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i><strong>${data.nombre_completo}</strong> | ${data.nombre_depto}`;
                        if (idx === 1 && data.numero_oficio) ofiInput.value = data.numero_oficio;
                    }
                }).catch(() => res.innerHTML = '<span class="text-danger">Error de conexión</span>');
        }
    </script>
</body>
</html>