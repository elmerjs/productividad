<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dirección tesis</title>
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
        .datos-container {
    margin-top: 10px; /* Espacio superior */
    font-size: 14px;  /* Tamaño de la fuente */
    color: #333;      /* Color del texto */
    background-color: #f9f9f9; /* Fondo ligero para destacar */
    padding: 10px;    /* Espaciado interno */
    border: 1px solid #ddd; /* Borde suave */
    border-radius: 5px; /* Bordes redondeados */
    width: 100%;      /* Asegura que ocupe todo el ancho disponible */
    box-sizing: border-box; /* Para incluir padding en el ancho total */
    white-space: normal; /* Permite que el texto se expanda a varias líneas */
    word-wrap: break-word; /* Rompe el texto si es demasiado largo */
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Dirección de tesis</h1>
        <!-- Formulario -->
        <form action="guardar_direccion_tesis.php" method="post">
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

            <!-- Documento Profesor -->
            <div class="row mb-3">
                <div class="col-md-3">
           <!-- Campo de entrada para el documento del profesor -->
<label for="documento_profesor">Documento Profesor:</label>
<input type="text" id="documento_profesor" name="documento_profesor" class="form-control" oninput="buscarDatos(this)" required>
                  </div>       </div>
             <div class="row mb-3">
                    <div class="col-md-12">
 <label for="datos_profesor">Datos profesor:</label>
<!-- Contenedor para mostrar los datos del profesor -->
<div id="datos_profesor" class="datos-container"></div>
                </div> </div>
     

            <!-- Contenedor para documentos -->

            <!-- Campos adicionales -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="producto">Producto</label>
                    <input type="text" class="form-control" name="producto" id="producto" placeholder="Ingrese el nombre del título">
                </div>

                <div class="col-md-4">
                    <label for="tipo_direccion">Tipo dirección:</label>
                    <select id="tipo_direccion" name="tipo_direccion" class="form-control" onchange="actualizarPuntaje()">
    <option value="maestria">Maestría</option>
    <option value="doctorado">Doctorado</option>
    <option value="posdoctorales">Posdoctorales</option>
</select>
                </div>

              <div class="col-md-4">
    <label for="nombre_estudiante">Nombre Estudiante</label>
    <input type="text" id="nombre_estudiante" name="nombre_estudiante" class="form-control" required>
</div>
            </div>

            <!-- Institución Educativa, Fecha de Terminación, Resolución de Convalidación -->
            <div class="row mb-3">
              <div class="col-md-4">
    <label for="fecha_sustentacion">Fecha de Sustentación:</label>
    <input type="date" class="form-control" name="fecha_sustentacion" id="fecha_sustentacion" required>
</div>

                <div class="col-md-4">
                    <label for="fecha_terminacion">Fecha de Terminación:</label>
                    <input type="date" class="form-control" name="fecha_terminacion" id="fecha_terminacion" required>
                </div>

                <div class="col-md-4">
                    <label for="resolucion">Resolución:</label>
                    <input type="text" class="form-control" name="resolucion" id="resolucion" placeholder="Ingrese la resolución  de trabajo de grado">
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
                    <a href="menu_ini.php" class="btn btn-secondary">Volver</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts de Bootstrap y lógica para puntaje -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
       function actualizarPuntaje() {
    var tipoDireccion = document.getElementById("tipo_direccion").value;
    var puntaje = 0;

    switch(tipoDireccion) {
        case "maestria":
            puntaje = 36;
            break;
        case "doctorado":
            puntaje = 72;
            break;
        case "posdoctorales":
            puntaje = 120;
            break;
        default:
            puntaje = 0;
    }

    document.getElementById("puntaje").value = puntaje;
}
        // Llamar la función para establecer el puntaje inicial al cargar la página
        actualizarPuntaje();

        // Función para buscar los datos del profesor
function buscarDatos(input) {
    const documento = input.value.trim(); // Obtener el valor del input y quitar espacios al inicio y final
    if (documento === '') return; // Si el campo está vacío, no hacer nada.

    console.log(`Buscando datos para el documento: ${documento}`);
    const datosContainer = document.getElementById('datos_profesor'); // Contenedor para mostrar los datos del profesor
    datosContainer.textContent = 'Cargando...'; // Mostrar indicador de carga

    // Hacer la solicitud al servidor
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
                datosContainer.textContent = data.error; // Mostrar el error si no se encuentra el profesor
            } else {
                // Mostrar los datos del profesor en el contenedor junto al campo de documento
                datosContainer.textContent = `${data.nombre_completo}, Depto: ${data.nombre_depto}, Fac.: ${data.nombre_fac}`;

                // Prellenar el campo "Número de oficio" si existe el número de oficio en los datos
                if (data.numero_oficio) {
                    const numeroOficioInput = document.getElementById('inputTrdFac');
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
