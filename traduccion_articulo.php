<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traducción articulo</title>
    <!-- Enlace al CDN de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados */
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 30px;
            max-width: 800px;
            margin-top: 30px;
        }
        h1 {
            font-weight: 600;
            color: #343a40;
            text-align: center;
        }
        label {
            font-weight: bold;
            color: #495057;
        }
        .input-group, .form-control {
            border-radius: 6px;
        }
        #contenedor_documentos {
            background-color: #f0f4f8;
            padding: 15px;
            border-radius: 6px;
        }
        .datos-container {
            font-style: italic;
            color: #6c757d;
        }
        .btn-primary, .btn-secondary {
            border-radius: 6px;
        }
        .custom-select {
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Traducción de artículo publicada en revista o libro</h1>
        <!-- Formulario -->
        <form action="guardar_traduc_bon.php" method="post">
            <?php
            // Generar identificador basado en el año y mes
            $identificador_base = date('Y_m');
            ?>

            <div class="row mb-3">
                <!-- Identificador -->
                <div class="col-md-6">
                    <label for="identificador_base">Identificador:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="identificador_base" name="identificador_base" 
                               value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" placeholder="Año_Mes" required>
                        <select class="form-control custom-select" id="numero_envio" name="numero_envio" style="width: 50px;" required>
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == 1 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Número de Oficio -->
                <div class="col-md-6">
                    <label for="inputTrdFac">Número de oficio:</label>
                    <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control" required>
                </div>
            </div>

            <!-- Número de Profesores -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="numero_profesores">Número de Profesores:</label>
                    <input type="number" id="numero_profesores" name="numero_profesores" min="1" class="form-control" placeholder="Ingrese el número de profesores" required>

                </div>
            </div>

            <!-- Contenedor para documentos -->
            <div id="contenedor_documentos" class="mb-3"></div>

            <!-- Campos adicionales -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="producto">Nombre del producto</label>
                    <input type="text" class="form-control" name="producto" id="producto" placeholder="Ingrese el nombre del producto" required>
                </div>
            </div>

            <!-- Fecha de Solicitud -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fecha_solicitud">Fecha de Solicitud:</label>
                    <input type="date" class="form-control" id="fecha_solicitud" name="fecha_solicitud" required>
                </div>
            </div>

         

            <!-- Campo de Puntaje -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="puntaje">Puntaje Total:</label>
<input type="number" class="form-control" id="puntaje" name="puntaje" step="0.01" min="0" value="36" required>
                </div>
            </div>

            <!-- Botones -->
            <div class="row">
                <div class="col-md-12 text-right mt-3">
                    <button type="submit" class="btn btn-primary">Enviar</button>
                    <a href="menu_ini.php" class="btn btn-secondary">Volver</a>
                </div>
            </div>

        </form>
    </div>

    <!-- Script para manejar los campos dinámicos -->
    <script>
        const numeroProfesoresInput = document.getElementById('numero_profesores');
        const contenedorDocumentos = document.getElementById('contenedor_documentos');
        const numeroOficioInput = document.getElementById('inputTrdFac');

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = '';

            const cantidad = parseInt(numeroProfesoresInput.value);
            if (isNaN(cantidad) || cantidad < 1) return;

            for (let i = 1; i <= cantidad; i++) {
                const fieldContainer = document.createElement('div');
                fieldContainer.classList.add('row', 'align-items-center', 'mb-2');

                const label = document.createElement('label');
                label.textContent = `Documento solicitante ${i}:`;
                label.setAttribute('for', `documento_${i}`);
                label.classList.add('col-sm-3', 'col-form-label', 'fw-bold');

                const input = document.createElement('input');
                input.type = 'text';
                input.id = `documento_${i}`;
                input.name = `documento_${i}`;
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
                        datosContainer.textContent = `Nombre: ${data.nombre_completo}, Departamento: ${data.nombre_depto}, Facultad: ${data.nombre_fac}`;
                        if (index === 1 && data.numero_oficio) {
                            numeroOficioInput.value = data.numero_oficio;
                            console.log(`Número de oficio prellenado: ${data.numero_oficio}`);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud fetch:', error);
                    datosContainer.textContent = 'Error al cargar los datos';
                });
        }
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log("Enviando formulario con el número de profesores:", numeroProfesoresInput.value);
        });
    </script>

    <!-- Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
