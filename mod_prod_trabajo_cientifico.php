<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Menú de Reconocimientos Académicos</title>
    <style>
    :root {
        --color-dark: #202124; /* Color similar al negro suave de Google */
        --color-primary: #4285f4; /* Azul característico de Google */
        --color-light: #ffffff; /* Blanco */
        --color-border: #dadce0; /* Color de borde gris suave */
        --color-hover: #f1f3f4; /* Fondo de hover */
        --borderRadius: 8px;
        --font-face: 'Roboto', Arial, sans-serif;
    }

    body {
        background-color: #f8f9fa;
        font-family: var(--font-face);
    }

    .container {
        margin-top: 50px;
        padding: 30px;
        border-radius: var(--borderRadius);
        background: var(--color-light);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--color-border);
    }

    h2 {
        color: var(--color-dark);
        margin-bottom: 30px;
    }

    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--borderRadius);
        padding: 10px 15px;
        font-size: 1rem;
        background-color: var(--color-light);
        color: var(--color-dark);
        border: 1px solid var(--color-border);
        text-decoration: none;
        transition: background-color 0.3s, box-shadow 0.3s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn:hover {
        background-color: var(--color-hover);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .btn i {
        margin-right: 8px;
        color: var(--color-primary);
    }

    .btn-group-custom {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .category h5 {
        color: var(--color-dark);
        margin-bottom: 20px;
    }
</style>
</head>
<body>
<!-- Modal de Trabajo Científico -->
<div class="modal fade" id="modalTrabajoCientifico" tabindex="-1" role="dialog" aria-labelledby="modalTrabajoCientificoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrabajoCientificoLabel">Trabajo de Carácter Científico</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario -->
                <form action="guardar_trabajo.php" method="post">
                    <?php
                    $identificador_base = date('Y_m');
                    ?>
                    
                    <div class="row mb-3">
                        <!-- Identificador -->
                        <div class="col-md-6">
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
                        
                        <!-- Número de Oficio -->
                        <div class="col-md-6">
                            <label for="inputTrdFac" class="form-label fw-bold">Número de oficio:</label>
                            <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control" required>
                        </div>
                    </div>

                    <!-- Número de Profesores -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="numero_profesores" class="form-label">Número de Profesores:</label>
                            <input type="number" id="numero_profesores" min="1" class="form-control" placeholder="Ingrese el número de profesores">
                        </div>
                    </div>

                    <!-- Contenedor para documentos -->
                    <div id="contenedor_documentos" class="mb-3"></div>

                    <!-- Campos adicionales -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="producto" class="form-label">Nombre del producto</label>
                            <input type="text" class="form-control" name="producto" id="producto" placeholder="Ingrese el nombre del producto">
                        </div>
                        <div class="col-md-4">
                            <label for="difusion" class="form-label">Difusión</label>
                            <input type="text" class="form-control" name="difusion" id="difusion" placeholder="Ingrese la difusión">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="finalidad" class="form-label">Finalidad</label>
                            <input type="text" class="form-control" name="finalidad" id="finalidad" placeholder="Ingrese la finalidad">
                        </div>
                        <div class="col-md-6">
                            <label for="area" class="form-label">Área disciplinar</label>
                            <input type="text" class="form-control" name="area" id="area" placeholder="Ingrese el área disciplinar">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="evaluador1" class="form-label">Evaluador 1 (puntaje)</label>
                            <input type="number" class="form-control" name="evaluador1" id="evaluador1" step="0.1" placeholder="Ingrese puntaje de evaluador 1">
                        </div>
                        <div class="col-md-6">
                            <label for="evaluador2" class="form-label">Evaluador 2 (puntaje)</label>
                            <input type="number" class="form-control" name="evaluador2" id="evaluador2" step="0.1" placeholder="Ingrese puntaje de evaluador 2">
                        </div>
                        <div class="col-md-12">
                            <label for="promedio" class="fw-bold">Promedio de Evaluadores:</label>
                            <input type="text" class="form-control" id="promedio" readonly placeholder="El promedio se mostrará aquí">
                        </div>
                    </div>

                    <!-- Campo de Puntaje -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="puntaje" class="form-label">Puntaje Total</label>
                            <input type="text" class="form-control" id="puntaje" name="puntaje">
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row mb-3">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary mt-3">Enviar</button>
                            <button type="button" class="btn btn-secondary mt-3" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    </body>
      <!-- Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

</html>


<!--calcular promedio -->
<script>
    const evaluador1Input = document.getElementById('evaluador1');
    const evaluador2Input = document.getElementById('evaluador2');
    const promedioInput = document.getElementById('promedio');

    function calcularPromedio() {
        const eval1 = parseFloat(evaluador1Input.value);
        const eval2 = parseFloat(evaluador2Input.value);

        if (!isNaN(eval1) && !isNaN(eval2)) {
            const suma = eval1 + eval2;
            const promedio = (suma / 2).toFixed(2);
            promedioInput.value = `(${eval1}) + (${eval2}) = ${suma} / 2 = ${promedio}%`;
        } else {
            promedioInput.value = ''; // Limpia el campo si los datos no son válidos
        }
    }

    evaluador1Input.addEventListener('input', calcularPromedio);
    evaluador2Input.addEventListener('input', calcularPromedio);
</script>
<script>
    function generarCamposCedula() {
        const numProfesores = document.getElementById('numProfesores').value;
        const contenedor = document.getElementById('contenedorCedulas');
        contenedor.innerHTML = ''; // Limpiar el contenedor antes de generar nuevos campos

        for (let i = 1; i <= numProfesores; i++) {
            const div = document.createElement('div');
            div.classList.add('form-group');
            div.innerHTML = `
                <label for="cedulaProfesor${i}">Cédula del Profesor ${i}</label>
                <input type="text" class="form-control" name="cedulaProfesor${i}" id="cedulaProfesor${i}" placeholder="Ingrese cédula del profesor ${i}">
            `;
            contenedor.appendChild(div);
        }
    }
</script>
    
    <!--//script para profesores-->
    
<script>
    const numeroProfesoresInput = document.getElementById('numero_profesores');
    const contenedorDocumentos = document.getElementById('contenedor_documentos');
    const numeroOficioInput = document.getElementById('inputTrdFac'); // Campo del número de oficio

    numeroProfesoresInput.addEventListener('input', () => {
        contenedorDocumentos.innerHTML = ''; // Limpiar el contenedor cada vez que se cambie el número

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
</script>