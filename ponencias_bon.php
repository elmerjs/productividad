    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Bonificación - Formulario  ponencias</title>
        </head>
    <body>
        <div class="container mt-5">
            <div class="modal-content p-4">
                <h5 class="modal-title mb-3">Bonificaicón - Ponencias</h5>
                <form action="guardar_ponencias_bon.php" method="post">

                      <!-- Fila 4: Número de Profesores -->
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="numero_profesores">Número de Profesores</label>
                            <input type="number" id="numero_profesores" name="numero_profesores" class="form-control">
                        </div>
                    </div>

                    <!-- Contenedor para documentos de profesores -->
                    <div id="contenedor_documentos"></div>
                    <!-- Fila 1: Identificador Base - Oficio - Fecha de Solicitud -->
                    <div class="form-row">
                       <?php
                        $identificador_base = date('Y_m');
                        ?>


                            <!-- Identificador -->
                            <div class="form-group col-md-4">
                                <label for="identificador_base" class="form-label fw-bold">Identificador:</label>
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
                       <div class="form-group col-md-4">
        <label for="numeroOficio">Número de Oficio</label>
        <input type="text" id="numeroOficio" name="numeroOficio" class="form-control" placeholder="Número de oficio">
    </div>
                        <div class="form-group col-md-4">
                            <label for="fecha_solicitud">Fecha de Solicitud</label>
                            <input type="date" id="fecha_solicitud" name="fecha_solicitud" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <!-- Fila 2: Tipo de Libro - Producto - ISBN -->
                    <div class="form-row">
                        <div class="form-group col-md-4">

               <div class="select-group">
        <label for="difusion" >Grado de Difusión geográfico:</label>
        <select name="difusion" id="difusion"  class="form-control" required>
            <option value="" disabled selected>Seleccione una opción</option>
            <option value="regional">Evento Regional</option>
            <option value="nacional">Evento Nacional</option>
            <option value="internacional">Evento Internacional</option>
        </select>
    </div>
                                        </div>



                        <div class="form-group col-md-5">
                            <label for="producto">Ponencia</label>
                            <input type="text" id="producto" name="producto" class="form-control">
                        </div></div>
                      <div class="form-row">
        <div class="form-group col-md-5">
            <label for="nombre_evento">Nombre del Evento:</label>
            <input type="text" id="nombre_evento" name="nombre_evento" class="form-control" required>
        </div>



        <div class="form-group col-md-2">
            <label for="fecha_evento">Fecha del Evento:</label>
            <input type="date" id="fecha_evento" name="fecha_evento" class="form-control" required>
        </div>

        <div class="form-group col-md-2">
            <label for="lugar_evento">Lugar del Evento:</label>
            <input type="text" id="lugar_evento" name="lugar_evento" class="form-control" required>
        </div>
    </div>



                    <!-- Fila 5: Autores - Evaluación 1 - Evaluación 2 - Puntaje -->
                      <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="autores">Autores</label>
                            <input type="number" id="autores" name="autores" class="form-control" min="1" placeholder="Cantidad de autores">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="evaluacion1">Evaluación 1</label>
                            <input type="number" id="evaluacion1" name="evaluacion1" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>

                        <div class="form-group col-md-3">
                            <label for="evaluacion2">Evaluación 2</label>
                            <input type="number" id="evaluacion2" name="evaluacion2" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                             <div class="form-group col-md-3">
            <label for="puntaje_f">Puntaje Final</label>
            <input type="text" id="puntaje_f" name="puntaje_f" class="form-control">
        </div>


                    </div>                  <div class="form-row">

                    <div class="form-group col-md-12">
                            <label for="puntaje">Puntaje</label>
                            <input type="text" id="puntaje" name="puntaje" class="form-control" readonly>
                        </div></div>
                                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back();">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

        <!-- Script para calcular el promedio -->
    <script>
        // Obtener referencias a los elementos de entrada
        const autoresInput = document.getElementById('autores');
        const evaluacion1Input = document.getElementById('evaluacion1');
        const evaluacion2Input = document.getElementById('evaluacion2');
        const difusionSelect = document.getElementById('difusion');
        const puntajeInput = document.getElementById('puntaje');
        const puntajeFInput = document.getElementById('puntaje_f');

        // Función para redondear hacia abajo a dos decimales
        function redondearHaciaAbajo(valor) {
            return Math.floor(valor * 100) / 100;
        }
    // Función para calcular el puntaje

    // Función para calcular el puntaje
    // Función para calcular el puntaje
        function calcularPuntaje() {
            const autores = parseInt(autoresInput.value);
            const eval1 = parseFloat(evaluacion1Input.value);
            const eval2 = parseFloat(evaluacion2Input.value);
            const difusion = difusionSelect.value.trim().toLowerCase();

            if (!isNaN(eval1) && !isNaN(eval2) && !isNaN(autores) && autores > 0) {
                const suma = eval1 + eval2;
                // 1. Usamos el promedio numérico para la lógica
                const promedioNum = suma / 2;
                // 2. Guardamos el promedio con 2 decimales solo para mostrarlo
                const promedioDisplay = promedioNum.toFixed(2);

                // 3. *** LÓGICA CORREGIDA SEGÚN ARTÍCULO 9 ***
                // Determinar el porcentaje de bonificación según el RANGO
                let porcentajeBonificacion = 0;
                let rangoTexto = "Inferior a 70 (No bonifica)";

                if (promedioNum >= 95) {
                    porcentajeBonificacion = 1.0; // 100% para Excelente (95-100)
                    rangoTexto = "Excelente (95-100) -> 100%";
                } else if (promedioNum >= 90) {
                    porcentajeBonificacion = 0.9; // 90% para Sobresaliente (90-94.99)
                    rangoTexto = "Sobresaliente (90-94) -> 90%";
                } else if (promedioNum >= 80) {
                    porcentajeBonificacion = 0.8; // 80% para Bueno (80-89.99)
                    rangoTexto = "Bueno (80-89) -> 80%";
                } else if (promedioNum >= 70) {
                    porcentajeBonificacion = 0.7; // 70% para Aceptable (70-79.99)
                    rangoTexto = "Aceptable (70-79) -> 70%";
                }
                // Si es menor a 70, porcentajeBonificacion permanece en 0.

                let maxPuntaje = 0;
                if (difusion === 'internacional') {
                    maxPuntaje = 84;
                } else if (difusion === 'nacional') {
                    maxPuntaje = 48;
                } else if (difusion === 'regional') {
                    maxPuntaje = 24;
                }

                if (maxPuntaje > 0 && porcentajeBonificacion > 0) {
                    // 4. *** CÁLCULO CORREGIDO ***
                    // Usamos el porcentajeBonificacion (0.8) en lugar del promedio (0.8621)
                    const puntajeBase = porcentajeBonificacion * maxPuntaje;
                    
                    let puntajeFinal;
                    let detalleAutores = '';

                    if (autores <= 3) {
                        puntajeFinal = puntajeBase;
                        detalleAutores = '(Hasta 3 autores: puntaje total)';
                    } else if (autores >= 4 && autores <= 5) {
                        puntajeFinal = puntajeBase / 2;
                        detalleAutores = '(4 a 5 autores: mitad del puntaje)';
                    } else {
                        puntajeFinal = puntajeBase / (autores / 2);
                        detalleAutores = `(6 o más autores: dividido por ${autores / 2})`;
                    }

                    const puntajeRedondeado = redondearHaciaAbajo(puntajeFinal).toFixed(2);

                    // 5. Mostramos el cálculo correcto al usuario
                    puntajeInput.value = `Promedio: ${promedioDisplay}. Rango: ${rangoTexto}. Cálculo: ${maxPuntaje} * ${porcentajeBonificacion * 100}% = ${puntajeBase.toFixed(2)} ${detalleAutores}`;
                    puntajeFInput.value = puntajeRedondeado;

                } else if (maxPuntaje === 0) {
                    puntajeInput.value = 'Grado de difusión no válido';
                    puntajeFInput.value = '';
                } else { // (Si el porcentaje es 0, o sea, promedio < 70)
                    puntajeInput.value = `Promedio: ${promedioDisplay}. ${rangoTexto}`;
                    puntajeFInput.value = '0.00';
                }
            } else {
                puntajeInput.value = '';
                puntajeFInput.value = '';
            }
        }
   // Añadir eventos para calcular el puntaje cuando cambian los valores
        autoresInput.addEventListener('input', calcularPuntaje);
        evaluacion1Input.addEventListener('input', calcularPuntaje);
        evaluacion2Input.addEventListener('input', calcularPuntaje);
        difusionSelect.addEventListener('change', calcularPuntaje);

        // Esto estaba duplicado en tu código, lo limpié pero no afecta la funcionalidad.
        /* document.querySelectorAll('input[name="difusion"]').forEach((element) => {
            element.addEventListener('change', calcularPuntaje);
        });
        */
    </script>



        <!-- Script para generar campos de documentos de profesores -->

        <!-- Script para generar campos de documentos de profesores -->
        <script>
           const numeroProfesoresInput = document.getElementById('numero_profesores');
        const contenedorDocumentos = document.getElementById('contenedor_documentos');

        numeroProfesoresInput.addEventListener('input', () => {
            contenedorDocumentos.innerHTML = ''; // Limpiar el contenedor cada vez que se cambie el número

            const cantidad = parseInt(numeroProfesoresInput.value);
            if (isNaN(cantidad) || cantidad < 1) return; // Validación de cantidad

            for (let i = 1; i <= cantidad; i++) {
                // Crear el contenedor del campo de documento y datos
                const fieldContainer = document.createElement('div');
                fieldContainer.classList.add('form-group', 'd-flex', 'align-items-center');

                // Etiqueta para el campo de documento
                const label = document.createElement('label');
                label.textContent = `Cédula ${i}`;
                label.setAttribute('for', `cedulaProfesor${i}`);
                label.classList.add('mr-2'); // Añadir margen a la derecha para espacio

                // Campo de entrada de documento
                const input = document.createElement('input');
                input.type = 'text';
                input.id = `cedulaProfesor${i}`;
                input.name = `cedulaProfesor${i}`;
                input.classList.add('form-control', 'mr-2', 'w-25'); // Agrega la clase 'w-25' para reducir el ancho
                input.placeholder = `Ingrese cédula del profesor ${i}`;

                // Contenedor para mostrar los datos del profesor
                const datosContainer = document.createElement('div');
                datosContainer.id = `datos_${i}`;
                datosContainer.classList.add('text-muted'); // Estilo de texto

                // Añadir evento para buscar los datos cuando se introduce el documento
                input.addEventListener('input', () => buscarDatos(input, i));

                // Añadir los elementos al contenedor
                fieldContainer.appendChild(label);
                fieldContainer.appendChild(input);
                fieldContainer.appendChild(datosContainer);
                contenedorDocumentos.appendChild(fieldContainer);
            }
        });
        // Función para buscar datos
        function buscarDatos(input, index) {
            const documento = input.value.trim();
            if (documento === '') return;

            console.log(`Buscando datos para el documento: ${documento}`);
            const datosContainer = document.getElementById(`datos_${index}`);
            datosContainer.textContent = 'Cargando...'; // Mostrar indicador de carga

            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta del servidor: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data); // Mostrar datos en consola para depuración
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
                    console.error('Error en la solicitud fetch:', error);
                    datosContainer.textContent = 'Error al cargar los datos';
                });
        }
    </script>
    </body>
    </html>
