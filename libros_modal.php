<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Libros | CIARP</title>
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
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.1);
        }
        label { font-size: 0.85rem; margin-bottom: 0.4rem; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card-custom">
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #1a2a3a;">
                <i class="fas fa-book text-primary mr-2"></i>Registro de Libros
            </h2>

            <form action="guardar_libro.php" method="post">
                
                <div class="section-title">Información de Solicitud</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Identificador:</label>
                        <div class="input-group">
                            <?php $identificador_base = date('Y_m'); ?>
                            <input type="text" class="form-control" name="identificador_base" 
                                   value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" required>
                            <div class="input-group-append">
                                <select class="custom-select" name="numero_envio" style="border-radius: 0 5px 5px 0;">
                                    <?php for ($i = 1; $i <= 9; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold text-primary"># Profesores Solicitantes:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" class="form-control border-primary" min="1" placeholder="Ej: 1" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Número de Oficio:</label>
                        <input type="text" id="numeroOficio" name="numeroOficio" class="form-control" placeholder="Oficio TRD" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">Fecha Solicitud:</label>
                        <input type="date" name="fecha_solicitud" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4"></div>

                <div class="section-title">Detalles del Libro</div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Tipo de Libro:</label>
                        <select id="tipo_libro" name="tipo_libro" class="form-control" required>
                            <option value="INVESTIGACION">INVESTIGACIÓN</option>
                            <option value="TEXTO">TEXTO</option>
                            <option value="ENSAYO">ENSAYO</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Título del Libro (Producto):</label>
                        <input type="text" name="producto" class="form-control" required placeholder="Nombre completo del libro">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">ISBN:</label>
                        <input type="text" name="isbn" class="form-control" placeholder="000-00-0000-000-0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Mes y Año de Edición:</label>
                        <input type="month" name="mes_anio_edicion" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Nombre de la Editorial:</label>
                        <input type="text" name="nombre_editorial" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Tiraje:</label>
                        <input type="text" name="tiraje" class="form-control" placeholder="Cant. ejemplares">
                    </div>
                </div>

                <div class="section-title">Evaluación y Calificación</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold"># Autores:</label>
                        <input type="number" id="autores" name="autores" class="form-control" placeholder="Total" min="1" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold">Evaluación 1:</label>
                        <input type="text" id="evaluacion1" name="evaluacion1" class="form-control" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="font-weight-bold">Evaluación 2:</label>
                        <input type="text" id="evaluacion2" name="evaluacion2" class="form-control" pattern="^\d+(\.\d{1,2})?$" placeholder="0.00">
                    </div>

                    <div class="form-group col text-center" id="contenedor_btn_mas">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="mostrarTercerEvaluador()">
                            <i class="fas fa-plus mr-1"></i> Evaluador
                        </button>
                    </div>

                    <div class="form-group col-md-2" id="grupo_evaluacion3" style="display: none;">
                        <label class="font-weight-bold text-danger">Evaluación 3:</label>
                        <input type="text" id="evaluacion3" name="evaluacion3" class="form-control border-danger" pattern="^\d+(\.\d{1,2})?$" placeholder="Opcional">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="font-weight-bold text-success">Puntaje Final:</label>
                        <input type="text" id="puntaje_f" name="puntaje_f" class="form-control font-weight-bold border-success text-success" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="small text-muted font-italic">Memoria de cálculo:</label>
                    <input type="text" id="puntaje" name="puntaje" class="form-control form-control-sm bg-light" readonly>
                </div>

                <hr>
                <div class="d-flex justify-content-end align-items-center">
                    <button type="button" class="btn btn-link text-muted mr-3" onclick="window.history.back();">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                        <i class="fas fa-save mr-2"></i>Guardar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // Lógica de cálculo (reutilizada y optimizada)
        const autoresInput = document.getElementById('autores');
        const evaluacion1Input = document.getElementById('evaluacion1');
        const evaluacion2Input = document.getElementById('evaluacion2');
        const evaluacion3Input = document.getElementById('evaluacion3');
        const tipoLibroInput = document.getElementById('tipo_libro');
        const puntajeInput = document.getElementById('puntaje');
        const puntajeFInput = document.getElementById('puntaje_f');

        function mostrarTercerEvaluador() {
            document.getElementById('grupo_evaluacion3').style.display = 'block';
            document.getElementById('contenedor_btn_mas').style.display = 'none';
            calcularPuntaje();
        }

        function redondearHaciaAbajo(valor) { return Math.floor(valor * 100) / 100; }

        function calcularPuntaje() {
            const autores = parseInt(autoresInput.value);
            const eval1 = parseFloat(evaluacion1Input.value);
            const eval2 = parseFloat(evaluacion2Input.value);
            const eval3 = parseFloat(evaluacion3Input.value);
            const tipoLibro = tipoLibroInput.value.trim().toUpperCase();
            const eval3Activo = window.getComputedStyle(document.getElementById('grupo_evaluacion3')).display !== 'none';
            const usarEval3 = eval3Activo && !isNaN(eval3);

            if (!isNaN(eval1) && !isNaN(eval2) && !isNaN(autores) && autores > 0) {
                let suma = eval1 + eval2;
                let divisor = 2;
                if (usarEval3) { suma += eval3; divisor = 3; }

                const promedio = (suma / divisor).toFixed(2); 
                const porcentaje = (promedio / 100).toFixed(4);
                let multiplicador = (tipoLibro === 'INVESTIGACION') ? 20 : 15;

                const puntajeBase = porcentaje * multiplicador;
                let puntajeFinal;
                let detalleAutores = '';

                if (autores <= 3) {
                    puntajeFinal = puntajeBase;
                    detalleAutores = '(Hasta 3: 100%)';
                } else if (autores <= 5) {
                    puntajeFinal = puntajeBase / 2;
                    detalleAutores = '(4-5: 50%)';
                } else {
                    puntajeFinal = puntajeBase / (autores / 2);
                    detalleAutores = `(6+: / ${autores / 2})`;
                }

                const puntajeRedondeado = redondearHaciaAbajo(puntajeFinal).toFixed(2);
                const textoSuma = usarEval3 ? `(${eval1}+${eval2}+${eval3})/3` : `(${eval1}+${eval2})/2`;
                
                puntajeInput.value = `${textoSuma} = ${promedio}% * ${multiplicador} = ${puntajeBase.toFixed(2)} ${detalleAutores}`;
                puntajeFInput.value = puntajeRedondeado;
            }
        }

        [autoresInput, evaluacion1Input, evaluacion2Input, evaluacion3Input, tipoLibroInput].forEach(i => i.addEventListener('input', calcularPuntaje));

        // Generación dinámica de profesores
        const numeroProfesoresInput = document.getElementById('numero_profesores');
        const contenedorDocumentos = document.getElementById('contenedor_documentos');

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = '';
            const cantidad = parseInt(numeroProfesoresInput.value);
            if (isNaN(cantidad) || cantidad < 1) { contenedorDocumentos.style.padding = "0"; return; }

            contenedorDocumentos.style.padding = "15px";
            for (let i = 1; i <= cantidad; i++) {
                const row = document.createElement('div');
                row.className = 'form-row align-items-center mb-2';
                row.innerHTML = `
                    <div class="col-md-3">
                        <input type="text" id="cedulaProfesor${i}" name="cedulaProfesor${i}" class="form-control form-control-sm" placeholder="Cédula ${i}" required>
                    </div>
                    <div class="col-md-9 small text-muted" id="datos_${i}"><i class="fas fa-id-card mr-1"></i> Esperando documento...</div>
                `;
                contenedorDocumentos.appendChild(row);
                document.getElementById(`cedulaProfesor${i}`).addEventListener('blur', function() { buscarDatos(this, i); });
            }
        });

        function buscarDatos(input, index) {
            const doc = input.value.trim();
            if (doc === '') return;
            const res = document.getElementById(`datos_${index}`);
            res.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            fetch(`obtener_datos_profesor.php?documento=${doc}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) res.innerHTML = `<span class="text-danger"><i class="fas fa-times"></i> ${data.error}</span>`;
                    else {
                        res.innerHTML = `<i class="fas fa-check-circle text-success"></i> <strong>${data.nombre_completo}</strong> | ${data.nombre_depto}`;
                        if (index === 1 && data.numero_oficio) document.getElementById('numeroOficio').value = data.numero_oficio;
                    }
                })
                .catch(() => res.innerHTML = '<span class="text-danger">Error de conexión</span>');
        }
    </script>
</body>
</html>