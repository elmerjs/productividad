<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Premios</title>
    <!-- Enlaces a Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="ruta/a/tu/estilo.css">
    <style>
        .recuadro-gris {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Formulario de Premios</h1>
        <!-- Formulario -->
        <form action="guardar_premio.php" method="post">
            <div class="recuadro-gris">
                <div class="row mb-3">
                    <!-- Número de Profesores -->
                    <div class="col-md-6">
                        <label for="numero_profesores" class="form-label fw-bold">Número de Profesores:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control" placeholder="Ingrese el número de profesores">
                    </div>
                </div>

                <!-- Contenedor para Cédulas y Datos -->
                <div id="contenedor_documentos" class="mb-3"></div>

                <!-- Identificador, Número de Oficio, Fecha de Solicitud, Autores -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="identificador_base" class="form-label fw-bold">Identificador:</label>
                        <div class="input-group">
                            <?php $identificador_base = date('Y_m'); ?>
                            <input type="text" class="form-control" id="identificador_base" name="identificador_base" 
                                   value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" placeholder="Año_Mes" required>
                            <select class="form-select form-select-sm" id="numero_envio" name="numero_envio" style="width: 50px;" required>
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == 1 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="numeroOficio" class="form-label fw-bold">Número de Oficio:</label>
                        <input type="text" id="numeroOficio" name="numeroOficio" class="form-control" placeholder="Número de oficio">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_solicitud" class="form-label fw-bold">Fecha de Solicitud:</label>
                        <input type="date" class="form-control" id="fecha_solicitud" name="fecha_solicitud" required>
                    </div>
                    <div class="col-md-3">
                        <label for="autores" class="form-label fw-bold">Autores:</label>
                        <input type="text" class="form-control" id="autores" name="autores" required>
                    </div>
                </div>

                <!-- Nombre Evento - Premio, Lugar y Fecha de Realización -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre_evento" class="form-label fw-bold">Nombre Evento - Premio:</label>
                        <input type="text" class="form-control" id="nombre_evento" name="nombre_evento" required>
                    </div>
                    <div class="col-md-6">
                        <label for="lugar_fecha" class="form-label fw-bold">Lugar y Fecha de Realización:</label>
                        <input type="text" class="form-control" id="lugar_fecha" name="lugar_fecha" required>
                    </div>
                </div>

                <!-- Ámbito, Categorías y/o Niveles, Nivel Ganado, Puntos -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="ambito" class="form-label fw-bold">Ámbito:</label>
                        <input type="text" class="form-control" id="ambito" name="ambito" required>
                    </div>
                    <div class="col-md-3">
                        <label for="categoria_premio" class="form-label fw-bold">Categorías y/o Niveles del Premio:</label>
                        <input type="text" class="form-control" id="categoria_premio" name="categoria_premio" required>
                    </div>
                    <div class="col-md-3">
                        <label for="nivel_ganado" class="form-label fw-bold">Nivel o Categoría Ganado:</label>
                        <input type="text" class="form-control" id="nivel_ganado" name="nivel_ganado" required>
                    </div>
                    <div class="col-md-3">
                        <label for="puntos" class="form-label fw-bold">Puntos:</label>
                        <input type="number" class="form-control" id="puntos" name="puntos" step="0.01" required>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="row mb-3">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary mt-3">Enviar</button>
                    <a href="index.php" class="btn btn-secondary mt-3">Volver</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Tu script para manejar la generación de campos -->
    <script>
        const numeroProfesoresInput = document.getElementById('numero_profesores');
        const contenedorDocumentos = document.getElementById('contenedor_documentos');

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = ''; // Limpiar el contenedor

            const cantidad = parseInt(numeroProfesoresInput.value);
            if (isNaN(cantidad) || cantidad < 1) return;

            for (let i = 1; i <= cantidad; i++) {
                const fieldContainer = document.createElement('div');
                fieldContainer.classList.add('row', 'align-items-center', 'mb-2');

                const label = document.createElement('label');
                label.textContent = `Cédula del Profesor ${i}:`;
                label.setAttribute('for', `cedula_${i}`);
                label.classList.add('col-sm-3', 'col-form-label', 'fw-bold');

                const input = document.createElement('input');
                input.type = 'text';
                input.id = `cedula_${i}`;
                input.name = `cedula_${i}`;
                input.required = true;
                input.classList.add('form-control', 'col-sm-3', 'me-3');
                input.style.maxWidth = '150px';
                input.addEventListener('blur', () => buscarDatos(input, i));

                const datosContainer = document.createElement('div');
                datosContainer.id = `datos_${i}`;
                datosContainer.classList.add('col', 'datos-container', 'text-muted', 'ps-2');

                fieldContainer.appendChild(label);
                fieldContainer.appendChild(input);
                fieldContainer.appendChild(datosContainer);
                contenedorDocumentos.appendChild(fieldContainer);
            }
        });

        function buscarDatos(input, index) {
            const documento = input.value.trim();
            if (documento === '') return;

            console.log(`Buscando datos para el documento: ${documento}`);
            const datosContainer = document.getElementById(`datos_${index}`);
            datosContainer.textContent = 'Cargando...';

            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta del servidor: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    if (data.error) {
                        datosContainer.textContent = data.error;
                    } else {
                        datosContainer.textContent = `${data.nombre_completo}, Depto: ${data.nombre_depto}, Fac.: ${data.nombre_fac}`;
                           // Prellenar el campo "oficio" si es el primer profesor (index === 1)
                if (index === 1 && data.numero_oficio) {
                    const numeroOficioInput = document.getElementById('numeroOficio');
                    numeroOficioInput.value = data.numero_oficio;
                    console.log(`Número de oficio prellenado: ${data.numero_oficio}`);
                }
                    }
                })
                .catch(error => {
                    console.error('Error al obtener los datos del profesor:', error);
                    datosContainer.textContent = 'Error al obtener datos';
                });
        }
    </script>
</body>
</html>
