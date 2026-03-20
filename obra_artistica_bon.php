<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonificación - Obra Artística | CIARP</title>
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
        .puntaje-destacado {
            font-weight: 800;
            color: #10b981;
            border-color: #10b981 !important;
            background-color: #f0fdf4 !important;
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
        .radio-tile-group { display: flex; gap: 1rem; flex-wrap: wrap; }
        .radio-custom { cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom">
            <div class="text-center mb-2">
                <span class="badge-bono"><i class="fas fa-palette mr-1"></i> Módulo de Bonificación</span>
            </div>
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                Obra de Creación Artística
            </h2>

            <form action="guardar_creacion_bon.php" method="post">
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
                        <label class="text-success"># Profesores:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control border-success shadow-sm" placeholder="Ej: 1" required>
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

                <div class="section-title">Detalles de la Creación</div>
                <div class="form-row mb-3">
                    <div class="form-group col-md-4">
                        <label>Tipo de Obra:</label>
                        <div class="radio-tile-group mt-1">
                            <label class="radio-custom"><input type="radio" name="tipo_producto" value="original" required> Original</label>
                            <label class="radio-custom"><input type="radio" name="tipo_producto" value="complementaria"> Apoyo</label>
                            <label class="radio-custom"><input type="radio" name="tipo_producto" value="interpretacion"> Interpretación</label>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Impacto:</label>
                        <div class="radio-tile-group mt-1">
                            <label class="radio-custom"><input type="radio" name="impacto" value="internacional" required> Regional</label>
                            <label class="radio-custom"><input type="radio" name="impacto" value="local"> Local</label>
                        </div>
                    </div>
                    <div class="form-group col-md-5">
                        <label>Nombre del Producto / Obra:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" id="producto" required>
                    </div>
                </div>

                <div class="form-row mb-3">
                    <div class="form-group col-md-6">
                        <label>Nombre del Evento:</label>
                        <input type="text" class="form-control shadow-sm" name="nombre_evento" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Lugar del Evento:</label>
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
                        <label>Autores Totales:</label>
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
                        <label class="text-success">Puntaje Final:</label>
                        <input type="text" id="puntaje_f" name="puntaje_f" class="form-control puntaje-destacado text-center shadow-sm" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="small text-muted">Memoria de cálculo:</label>
                    <input type="text" id="puntaje" name="puntaje" class="form-control form-control-sm bg-light shadow-none" readonly>
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
        const puntajeMemo = document.getElementById('puntaje');
        const puntajeFinal = document.getElementById('puntaje_f');

        function getSelectedValue(name) {
            const selected = document.querySelector(`input[name="${name}"]:checked`);
            return selected ? selected.value : '';
        }

        function calcularPuntaje() {
            const autores = parseInt(autoresInput.value) || 1;
            const eval1 = parseFloat(eval1Input.value) || 0;
            const eval2 = parseFloat(eval2Input.value) || 0;
            const tipo = getSelectedValue('tipo_producto');
            const impacto = getSelectedValue('impacto');

            if (eval1 > 0 && eval2 > 0 && tipo && impacto) {
                const promedio = (eval1 + eval2) / 2;
                const porcentaje = promedio / 100;

                let maxPuntaje = 0;
                if (tipo === 'original') maxPuntaje = (impacto === 'internacional') ? 20 : 14;
                else if (tipo === 'complementaria') maxPuntaje = (impacto === 'internacional') ? 12 : 8;
                else if (tipo === 'interpretacion') maxPuntaje = (impacto === 'internacional') ? 14 : 8;

                const puntajeBase = porcentaje * maxPuntaje;
                let final;
                let detalle = '';

                if (autores <= 3) { final = puntajeBase; detalle = '(Total)'; }
                else if (autores <= 5) { final = puntajeBase / 2; detalle = '(50%)'; }
                else { final = puntajeBase / (autores / 2); detalle = `(/ ${autores/2})`; }

                puntajeMemo.value = `${promedio.toFixed(2)}% de ${maxPuntaje} pts = ${puntajeBase.toFixed(2)} ${detalle}`;
                puntajeFinal.value = Math.floor(final * 100) / 100;
            } else {
                puntajeMemo.value = '';
                puntajeFinal.value = '';
            }
        }

        // Listeners para el cálculo
        [autoresInput, eval1Input, eval2Input].forEach(el => el.addEventListener('input', calcularPuntaje));
        document.querySelectorAll('input[type="radio"]').forEach(el => el.addEventListener('change', calcularPuntaje));

        // --- MANEJO DINÁMICO DE PROFESORES ---
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
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Cédula Profesor ${i}:</label>
                        <input type="text" id="doc_${i}" name="cedulaProfesor${i}" class="form-control form-control-sm shadow-sm" required>
                    </div>
                    <div class="col-md-9">
                        <label class="small text-muted">Datos del Profesor:</label>
                        <div id="datos_${i}" class="alert alert-light border m-0 p-1 small shadow-sm" style="min-height:31px; display:flex; align-items:center;">
                            <i class="fas fa-id-badge mr-2 opacity-50"></i> Esperando identificación...
                        </div>
                    </div>`;
                contDocs.appendChild(div);
                document.getElementById(`doc_${i}`).addEventListener('blur', function() { buscarProfe(this, i); });
            }
        });

        function buscarProfe(input, idx) {
            if (!input.value.trim()) return;
            const res = document.getElementById(`datos_${idx}`);
            res.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-success"></i>Buscando...';
            fetch(`obtener_datos_profesor.php?documento=${input.value}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) res.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>${data.error}</span>`;
                    else {
                        res.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i><strong>${data.nombre_completo}</strong> | ${data.nombre_depto}`;
                        if (idx === 1 && data.numero_oficio) ofiInput.value = data.numero_oficio;
                    }
                })
                .catch(() => { res.innerHTML = '<span class="text-danger">Error de conexión</span>'; });
        }
    </script>
</body>
</html>