<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Registro de Libros</title>
</head>
<body>
    <div class="container mt-5">
        <div class="modal-content p-4">
            <h5 class="modal-title mb-3">Registro de Libros</h5>
            <form action="guardar_libro.php" method="post">
                
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
                        <label for="tipo_libro">Tipo de Libro</label>
                <select id="tipo_libro" name="tipo_libro" class="form-control">
                    <option value="INVESTIGACION">INVESTIGACIÓN</option>
                    <option value="TEXTO">TEXTO</option>
                    <option value="ENSAYO">ENSAYO</option>
                </select>
                                    </div>
                    <div class="form-group col-md-4">
                        <label for="producto">Producto</label>
                        <input type="text" id="producto" name="producto" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn" class="form-control">
                    </div>
                </div>

                <!-- Fila 3: Mes y Año de Edición - Nombre de la Editorial - Tiraje -->
                <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="mes_anio_edicion">Mes y Año de Edición</label>
<input type="month" id="mes_anio_edicion" name="mes_anio_edicion" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="nombre_editorial">Nombre de la Editorial</label>
                        <input type="text" id="nombre_editorial" name="nombre_editorial" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="tiraje">Tiraje</label>
                        <input type="text" id="tiraje" name="tiraje" class="form-control">
                    </div>
                </div>

              

                <!-- Fila 5: Autores - Evaluación 1 - Evaluación 2 - Puntaje -->
                  <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="autores">Autores</label>
                        <input type="text" id="autores" name="autores" class="form-control" min="1" placeholder="Cantidad de autores">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="evaluacion1">Evaluación 1</label>
                       
                        <input type="text" id="evaluacion1" name="evaluacion1" class="form-control" pattern="^\d+(\.\d{1,2})?$" placeholder="Ej: 8.75">

                    </div>
                    <div class="form-group col-md-3">
                        <label for="evaluacion2">Evaluación 2</label>
    <input type="text" id="evaluacion2" name="evaluacion2" class="form-control" pattern="^\d+(\.\d{1,2})?$" placeholder="Ej: 9.50">
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
    const tipoLibroInput = document.getElementById('tipo_libro');
    const puntajeInput = document.getElementById('puntaje');
    const puntajeFInput = document.getElementById('puntaje_f');

    // Función para redondear hacia abajo a dos decimales
    function redondearHaciaAbajo(valor) {
        return Math.floor(valor * 100) / 100;
    }

    // Función para calcular el puntaje
    function calcularPuntaje() {
        const autores = parseInt(autoresInput.value);
        const eval1 = parseFloat(evaluacion1Input.value);
        const eval2 = parseFloat(evaluacion2Input.value);
        const tipoLibro = tipoLibroInput.value.trim().toUpperCase(); // Convertir a mayúsculas para comparar

        if (!isNaN(eval1) && !isNaN(eval2) && !isNaN(autores) && autores > 0) {
            // Calcular el promedio de las evaluaciones
            const suma = eval1 + eval2;
            const promedio = (suma / 2).toFixed(2); // Promedio con dos decimales
            const porcentaje = (promedio / 100).toFixed(4); // Convertir a porcentaje decimal

            // Determinar el multiplicador según el tipo de libro
            let multiplicador = 0;
            if (tipoLibro === 'TEXTO') {
                multiplicador = 15;
            } else if (tipoLibro === 'INVESTIGACION') {
                multiplicador = 20;
            } else if (tipoLibro === 'ENSAYO') {
                multiplicador = 15;
            }

            // Calcular el puntaje base
            if (multiplicador > 0) {
                const puntajeBase = porcentaje * multiplicador;
                let puntajeFinal;
                let detalleAutores = '';

                // Aplicar las reglas según el número de autores y preparar el detalle
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

                const puntajeRedondeado = redondearHaciaAbajo(puntajeFinal).toFixed(2); // Redondear hacia abajo

                // Mostrar el proceso de cálculo completo con detalle
                puntajeInput.value = `(${eval1} + ${eval2}) / 2 = ${promedio}% * ${multiplicador} = ${puntajeBase.toFixed(2)} ${detalleAutores}`;
                
                puntajeFInput.value = puntajeRedondeado;
            } else {
                puntajeInput.value = 'Tipo de libro no válido';
                puntajeFInput.value = '';
            }
        } else {
            puntajeInput.value = ''; // Limpiar el campo si los datos no son válidos
            puntajeFInput.value = '';
        }
    }

    // Añadir eventos para calcular el puntaje cuando cambian los valores
    autoresInput.addEventListener('input', calcularPuntaje);
    evaluacion1Input.addEventListener('input', calcularPuntaje);
    evaluacion2Input.addEventListener('input', calcularPuntaje);
    tipoLibroInput.addEventListener('input', calcularPuntaje);
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
