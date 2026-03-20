<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Creación Artística | CIARP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; color: #4a4a4a; }
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background-color: #ffffff;
            padding: 30px;
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
        .radio-group-custom {
            background: #fff;
            border: 1px solid #ced4da;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card-custom">
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                <i class="fas fa-palette text-primary mr-2"></i>Creación Artística
            </h2>

            <form action="guardar_creacion.php" method="post">
                
                <div class="section-title">Información de Solicitud</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Identificador:</label>
                        <div class="input-group">
                            <?php $identificador_base = date('Y_m'); ?>
                            <input type="text" class="form-control" name="identificador_base" value="<?= $identificador_base ?>" maxlength="7" required>
                            <select class="custom-select" name="numero_envio" style="max-width: 60px;">
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold text-primary"># Profesores:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" class="form-control border-primary" min="1" placeholder="Ej: 1">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Número de Oficio:</label>
                        <input type="text" id="numeroOficio" name="numeroOficio" class="form-control" placeholder="Oficio TRD">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Fecha Solicitud:</label>
                        <input type="date" name="fecha_solicitud" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles de la Obra y Evento</div>
                <div class="form-row mb-3">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Tipo de Obra:</label>
                        <div class="radio-group-custom">
                            <label class="mb-0"><input type="radio" name="tipo_producto" value="original" required> Original</label>
                            <label class="mb-0"><input type="radio" name="tipo_producto" value="complementaria" required> Apoyo</label>
                            <label class="mb-0"><input type="radio" name="tipo_producto" value="interpretacion" required> Interpretación</label>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Impacto Obra:</label>
                        <div class="radio-group-custom">
                            <label class="mb-0"><input type="radio" name="impacto" value="internacional" required> Int.</label>
                            <label class="mb-0"><input type="radio" name="impacto" value="nacional" required> Nac.</label>
                        </div>
                    </div>
                    <div class="form-group col-md-5">
                        <label for="producto" class="font-weight-bold">Nombre del Producto (Obra):</label>
                        <input type="text" id="producto" name="producto" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="nombre_evento" class="font-weight-bold">Nombre del Evento:</label>
                        <input type="text" id="nombre_evento" name="nombre_evento" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold">Ámbito Evento:</label>
                        <div class="radio-group-custom">
                            <label class="mb-0"><input type="radio" name="evento" value="internacional" required> Int.</label>
                            <label class="mb-0"><input type="radio" name="evento" value="nacional" required> Nac.</label>
                        </div>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold text-info">Fecha Inicio:</label>
                        <input type="date" name="fecha_evento" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold text-info">Fecha Fin:</label>
                        <input type="date" name="fecha_evento_f" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold">Lugar:</label>
                        <input type="text" name="lugar_evento" class="form-control" placeholder="Ciudad, País" required>
                    </div>
                </div>

                <div class="section-title">Evaluación y Puntaje</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold">Autores:</label>
                        <input type="number" id="autores" name="autores" class="form-control" min="1" placeholder="Cant.">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold">Eval. 1:</label>
                        <input type="number" id="evaluacion1" name="evaluacion1" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold">Eval. 2:</label>
                        <input type="number" id="evaluacion2" name="evaluacion2" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                    
                    <div class="form-group col text-center" id="contenedor_btn_mas">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="mostrarTercerEvaluador()">+ Evaluador</button>
                    </div>

                    <div class="form-group col-md-2" id="grupo_evaluacion3" style="display: none;">
                        <label class="font-weight-bold text-danger">Eval. 3:</label>
                        <input type="number" id="evaluacion3" name="evaluacion3" class="form-control border-danger" step="0.01" placeholder="0.00">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="font-weight-bold text-success">Puntaje Final:</label>
                        <input type="text" id="puntaje_f" name="puntaje_f" class="form-control font-weight-bold border-success" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="small text-muted">Detalle del cálculo:</label>
                    <input type="text" id="puntaje" name="puntaje" class="form-control form-control-sm bg-light" readonly>
                </div>

                <hr>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-light mr-2" onclick="window.history.back();">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm"><i class="fas fa-save mr-2"></i>Guardar Creación</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        const autoresInput = document.getElementById('autores');
        const evaluacion1Input = document.getElementById('evaluacion1');
        const evaluacion2Input = document.getElementById('evaluacion2');
        const evaluacion3Input = document.getElementById('evaluacion3');
        const puntajeInput = document.getElementById('puntaje');
        const puntajeFInput = document.getElementById('puntaje_f');

        function mostrarTercerEvaluador() {
            document.getElementById('grupo_evaluacion3').style.display = 'block';
            document.getElementById('contenedor_btn_mas').style.display = 'none';
            calcularPuntaje();
        }

        function getSelectedValue(name) {
            const selected = document.querySelector(`input[name="${name}"]:checked`);
            return selected ? selected.value : '';
        }

        function redondearHaciaAbajo(valor) { return Math.floor(valor * 100) / 100; }

        function calcularPuntaje() {
            const autores = parseInt(autoresInput.value);
            const eval1 = parseFloat(evaluacion1Input.value);
            const eval2 = parseFloat(evaluacion2Input.value);
            const eval3 = parseFloat(evaluacion3Input.value);
            const tipoProducto = getSelectedValue('tipo_producto').toLowerCase();
            const impacto = getSelectedValue('impacto').toLowerCase();

            const eval3Activa = document.getElementById('grupo_evaluacion3').style.display !== 'none';
            const usarEval3 = eval3Activa && !isNaN(eval3);

            if (!isNaN(eval1) && !isNaN(eval2) && !isNaN(autores) && autores > 0) {
                let suma = eval1 + eval2;
                let divisor = 2;
                if (usarEval3) { suma += eval3; divisor = 3; }

                const promedio = (suma / divisor).toFixed(2);
                const porcentaje = (promedio / 100).toFixed(4);

                let maxPuntaje = 0;
                if (tipoProducto === 'original') maxPuntaje = (impacto === 'internacional') ? 20 : 14;
                else if (tipoProducto === 'complementaria') maxPuntaje = (impacto === 'internacional') ? 12 : 8;
                else if (tipoProducto === 'interpretacion') maxPuntaje = (impacto === 'internacional') ? 14 : 8;

                if (maxPuntaje > 0) {
                    const puntajeBase = porcentaje * maxPuntaje;
                    let puntajeFinal;
                    let detalleAutores = '';

                    if (autores <= 3) {
                        puntajeFinal = puntajeBase;
                        detalleAutores = '(100% puntaje)';
                    } else if (autores <= 5) {
                        puntajeFinal = puntajeBase / 2;
                        detalleAutores = '(50% puntaje)';
                    } else {
                        puntajeFinal = puntajeBase / (autores / 2);
                        detalleAutores = `(/ ${autores / 2})`;
                    }

                    const puntajeRedondeado = redondearHaciaAbajo(puntajeFinal).toFixed(2);
                    const formulaTexto = usarEval3 ? `(${eval1}+${eval2}+${eval3})/3` : `(${eval1}+${eval2})/2`;
                    
                    puntajeInput.value = `${formulaTexto} = ${promedio}% * ${maxPuntaje} = ${puntajeBase.toFixed(2)} ${detalleAutores}`;
                    puntajeFInput.value = puntajeRedondeado;
                }
            }
        }

        [autoresInput, evaluacion1Input, evaluacion2Input, evaluacion3Input].forEach(inp => inp.addEventListener('input', calcularPuntaje));
        document.querySelectorAll('input[name="tipo_producto"], input[name="impacto"]').forEach(rad => rad.addEventListener('change', calcularPuntaje));

        const numeroProfesoresInput = document.getElementById('numero_profesores');
        const contenedorDocumentos = document.getElementById('contenedor_documentos');

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = '';
            const cantidad = parseInt(numeroProfesoresInput.value);
            if (isNaN(cantidad) || cantidad < 1) { contenedorDocumentos.style.padding = "0"; return; }
            
            contenedorDocumentos.style.padding = "15px";
            for (let i = 1; i <= cantidad; i++) {
                const div = document.createElement('div');
                div.className = 'form-row align-items-center mb-2';
                div.innerHTML = `
                    <div class="col-md-3">
                        <input type="text" id="cedulaProfesor${i}" name="cedulaProfesor${i}" class="form-control form-control-sm" placeholder="Cédula ${i}" required>
                    </div>
                    <div class="col-md-9 small text-muted" id="datos_${i}">Esperando documento...</div>
                `;
                contenedorDocumentos.appendChild(div);
                document.getElementById(`cedulaProfesor${i}`).addEventListener('blur', function() { buscarDatos(this, i); });
            }
        });

        function buscarDatos(input, index) {
            const documento = input.value.trim();
            if (documento === '') return;
            const res = document.getElementById(`datos_${index}`);
            res.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) res.innerHTML = `<span class="text-danger">${data.error}</span>`;
                    else {
                        res.innerHTML = `<i class="fas fa-check-circle text-success"></i> <strong>${data.nombre_completo}</strong> | ${data.nombre_depto}`;
                        if (index === 1 && data.numero_oficio) document.getElementById('numeroOficio').value = data.numero_oficio;
                    }
                });
        }
    </script>
</body>
</html>