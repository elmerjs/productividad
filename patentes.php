<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Patentes</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Patentes</h1>
        <!-- Formulario -->
        <form action="guardar_patente.php" method="post"> 
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
                            <select class="form-select form-select-sm" id="numero_envio" name="numero_envio" style="width: 50px;" required>
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
                        <input type="number" id="numero_profesores" min="1" class="form-control" placeholder="Ingrese el número de profesores">
                    </div>
                </div>

                <!-- Contenedor para documentos -->
                <div id="contenedor_documentos" class="mb-3"></div>

                <!-- Campos adicionales -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="producto">Nombre del producto</label>
                        <input type="text" class="form-control" name="producto" id="producto" placeholder="Ingrese el nombre del producto">
                    </div>
                </div>

                <!-- Campo de Puntaje -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="puntaje">Puntaje Total</label>
                        <input type="number" class="form-control" id="puntaje" name="puntaje" step="0.01" min="0" required>
                    </div>
                </div>

                <!-- Botones -->
                <div class="row">
                    <div class="col-md-12 text-right mt-3">
                        <button type="submit" class="btn btn-primary">Enviar</button>
                        <a href="index.php" class="btn btn-secondary">Volver</a>
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
        
        const hiddenInput = document.createElement('input');
hiddenInput.type = 'hidden';
hiddenInput.id = 'hidden_numero_profesores';
hiddenInput.name = 'hidden_numero_profesores';

// Actualizar el valor del campo oculto
numeroProfesoresInput.addEventListener('input', () => {
    const cantidad = parseInt(numeroProfesoresInput.value) || 0;
    hiddenInput.value = cantidad;
});

// Añadir el campo oculto al formulario
document.querySelector('form').appendChild(hiddenInput);
    </script>

    <!-- Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
