<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonificación - Reseña Crítica | CIARP</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; color: #4a4a4a; }
        .card-custom { border: none; border-radius: 15px; padding: 30px; margin: 30px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background-color: #ffffff; }
        .section-title { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #2c3e50; border-bottom: 2px solid #eef2f7; padding-bottom: 10px; margin: 20px 0; }
        #contenedor_documentos { background-color: #f8fafd; border-radius: 10px; border-left: 5px solid #10b981; transition: all 0.3s ease; }
        .puntaje-destacado { font-weight: 800; color: #10b981; border-color: #10b981 !important; background-color: #f0fdf4 !important; font-size: 1.1rem; }
        .badge-bono { background-color: #10b981; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom">
            <div class="text-center mb-2"><span class="badge-bono"><i class="fas fa-comment-dots mr-1"></i> Bonificación</span></div>
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">Reseña Crítica</h2>

            <form action="guardar_resena_bon.php" method="post">
                <?php $identificador_base = date('Y_m'); ?>

                <div class="section-title">Información de Solicitud</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Identificador:</label>
                        <div class="input-group shadow-sm">
                            <input type="text" class="form-control" name="identificador_base" value="<?= $identificador_base ?>" maxlength="7" required>
                            <div class="input-group-append">
                                <select class="custom-select" name="numero_envio" style="max-width: 60px;">
                                    <?php for ($i = 1; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="text-success font-weight-bold"># Profesores:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control border-success shadow-sm" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Número de Oficio:</label>
                        <input type="text" id="numeroOficio" name="numeroOficio" class="form-control shadow-sm" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Fecha Solicitud:</label>
                        <input type="date" name="fecha_solicitud" class="form-control shadow-sm" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles de la Reseña</div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Categoría Colciencias:</label>
                        <select class="custom-select shadow-sm" name="categoria_colciencias" required>
                            <option value="">Seleccione...</option>
                            <option value="A1">A1</option><option value="A2">A2</option>
                            <option value="B">B</option><option value="C">C</option>
                        </select>
                    </div>
                    <div class="form-group col-md-5">
                        <label class="font-weight-bold">Nombre del Producto / Reseña:</label>
                        <input type="text" class="form-control shadow-sm" name="producto" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">ISSN:</label>
                        <input type="text" class="form-control shadow-sm" name="issn" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Nombre de la Revista:</label>
                    <input type="text" class="form-control shadow-sm" name="nombre_revista" required>
                </div>

                <div class="section-title">Cálculo de Bonificación</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2"><label>Autores:</label><input type="number" id="autores" name="autores" class="form-control shadow-sm" min="1" value="1"></div>
                    <div class="form-group col-md-2"><label>Eval. 1:</label><input type="number" id="evaluacion1" name="evaluacion1" class="form-control shadow-sm" step="0.01"></div>
                    <div class="form-group col-md-2"><label>Eval. 2:</label><input type="number" id="evaluacion2" name="evaluacion2" class="form-control shadow-sm" step="0.01"></div>
                    <div class="form-group col-md-3"><label class="text-success font-weight-bold">Puntaje Final:</label><input type="text" id="puntaje_f" name="puntaje_f" class="form-control puntaje-destacado text-center shadow-sm" readonly></div>
                </div>
                <div class="form-group"><label class="small text-muted">Memoria de cálculo (Max 12 pts):</label><input type="text" id="puntaje" class="form-control form-control-sm bg-light shadow-none" readonly></div>

                <hr class="mt-4">
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-link text-muted mr-3" onclick="window.history.back();">Cancelar</button>
                    <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">Guardar Bonificación</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        const autoresInput = document.getElementById('autores');
        const eval1Input = document.getElementById('evaluacion1');
        const eval2Input = document.getElementById('evaluacion2');
        const puntajeMemo = document.getElementById('puntaje');
        const puntajeF = document.getElementById('puntaje_f');

        function getPorcentaje(promedio) {
            if (promedio >= 95) return 1.0; if (promedio >= 90) return 0.9;
            if (promedio >= 80) return 0.8; if (promedio >= 70) return 0.7; return 0;
        }

        function calcularPuntaje() {
            const autores = parseInt(autoresInput.value) || 1;
            const e1 = parseFloat(eval1Input.value) || 0;
            const e2 = parseFloat(eval2Input.value) || 0;
            if (e1 > 0 && e2 > 0) {
                const promedio = (e1 + e2) / 2;
                const porcentaje = getPorcentaje(promedio);
                const maxP = 12;
                const puntajeBase = porcentaje * maxP;
                let final = (autores <= 3) ? puntajeBase : (autores <= 5) ? puntajeBase/2 : puntajeBase/(autores/2);
                puntajeMemo.value = `Promedio: ${promedio.toFixed(2)} (${porcentaje*100}%) de ${maxP} = ${puntajeBase.toFixed(2)} pts (Factor autores aplicado)`;
                puntajeF.value = Math.floor(final * 100) / 100;
            } else { puntajeMemo.value = ''; puntajeF.value = ''; }
        }

        [autoresInput, eval1Input, eval2Input].forEach(el => el.addEventListener('input', calcularPuntaje));

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
                div.innerHTML = `<div class="col-md-3"><label class="small font-weight-bold">Cédula ${i}:</label>
                    <input type="text" id="doc_${i}" name="cedulaProfesor${i}" class="form-control form-control-sm" required></div>
                    <div class="col-md-9"><label class="small text-muted">Datos Docente:</label>
                    <div id="datos_${i}" class="alert alert-light border m-0 p-1 small">Esperando...</div></div>`;
                contDocs.appendChild(div);
                document.getElementById(`doc_${i}`).addEventListener('blur', function() { buscarProfe(this, i); });
            }
        });

        function buscarProfe(input, idx) {
            if (!input.value.trim()) return;
            const res = document.getElementById(`datos_${idx}`);
            res.innerHTML = '<i class="fas fa-spinner fa-spin text-success"></i>';
            fetch(`obtener_datos_profesor.php?documento=${input.value}`)
                .then(r => r.json()).then(data => {
                    if (data.error) res.innerHTML = data.error;
                    else {
                        res.innerHTML = `<strong>${data.nombre_completo}</strong> | ${data.nombre_depto}`;
                        if (idx === 1 && data.numero_oficio) ofiInput.value = data.numero_oficio;
                    }
                });
        }
    </script>
</body>
</html>