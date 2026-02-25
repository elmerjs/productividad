<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Títulos</title>
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
        .btn-primary, .btn-secondary {
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Títulos</h1>
        <form action="guardar_titulo.php" method="post">
            <?php
            // Generar identificador basado en el año y mes
            $identificador_base = date('Y_m');
            ?>

            <div class="row mb-3">
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

                <div class="col-md-6">
                    <label for="inputTrdFac">Número de oficio:</label>
                    <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="documento_profesor">Documento Profesor:</label>
                    <input type="text" id="documento_profesor" name="documento_profesor" class="form-control" oninput="buscarDatos(this)" required>
                </div>       
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="datos_profesor">Datos profesor:</label>
                    <div id="datos_profesor" class="datos-container"></div>
                </div> 
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="producto">Título obtenido</label>
                    <input type="text" class="form-control" name="producto" id="producto" placeholder="Ingrese el nombre del título">
                </div>

                <div class="col-md-4">
                    <label for="impacto">Tipo</label>
                    <select id="impacto" name="impacto" class="form-control" onchange="toggleResolucionConvalidacion()">
                        <option value="EXTERIOR">EXTERIOR</option>
                        <option value="NACIONAL" selected>NACIONAL</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="tipo_estudio">Tipo de Estudio</label>
                    <select id="tipo_estudio" name="tipo_estudio" class="form-control" onchange="actualizarPuntaje()">
                        <option value="DOCTORADO">Doctorado</option>
                        <option value="MAESTRIA">Maestría</option>
                        <option value="ESPECIALIZACION">Especialización</option>
                        <option value="ESPECIALIZACION_MEDICA">Especialización Médica</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="institucion">Institución Educativa:</label>
                    <input type="text" class="form-control" name="institucion" id="institucion" placeholder="Ingrese la institución educativa">
                </div>

                <div class="col-md-4">
                    <label for="fecha_terminacion">Fecha de Terminación:</label>
                    <input type="date" class="form-control" name="fecha_terminacion" id="fecha_terminacion" required>
                </div>

                <div class="col-md-4" id="campo_resolucion_convalidacion" style="display: none;">
                    <label for="resolucion_convalidacion">Resolución de Convalidación:</label>
                    <input type="text" class="form-control" name="resolucion_convalidacion" id="resolucion_convalidacion" placeholder="Ingrese la resolución">
                </div>

                <div class="col-md-4" id="campo_no_acta">
                    <label for="no_acta">N° Acta y Folio:</label>
                    <input type="text" class="form-control" name="no_acta" id="no_acta" placeholder="Ej. 43, FOLIO 1315...">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="puntaje">Puntaje Total</label>
                    <input type="number" class="form-control" id="puntaje" name="puntaje" step="0.01" min="0" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-right mt-3">
                    <button type="submit" class="btn btn-primary">Enviar</button>
                    <a href="index.php" class="btn btn-secondary">Volver</a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function actualizarPuntaje() {
            var tipoEstudio = document.getElementById("tipo_estudio").value;
            var puntaje = 0;

            switch(tipoEstudio) {
                case "DOCTORADO":
                    puntaje = 80;
                    break;
                case "MAESTRIA":
                    puntaje = 40;
                    break;
                case "ESPECIALIZACION":
                    puntaje = 20;
                    break;
                case "ESPECIALIZACION_MEDICA":
                    puntaje = 15;
                    break;
                default:
                    puntaje = 0;
            }

            document.getElementById("puntaje").value = puntaje;
        }

        actualizarPuntaje();

        function buscarDatos(input) {
            const documento = input.value.trim(); 
            if (documento === '') return; 

            console.log(`Buscando datos para el documento: ${documento}`);
            const datosContainer = document.getElementById('datos_profesor'); 
            datosContainer.textContent = 'Cargando...'; 

            fetch(`obtener_datos_profesor.php?documento=${documento}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta del servidor: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        datosContainer.textContent = data.error; 
                    } else {
                        datosContainer.textContent = `${data.nombre_completo}, Depto: ${data.nombre_depto}, Fac.: ${data.nombre_fac}`;
                        if (data.numero_oficio) {
                            const numeroOficioInput = document.getElementById('inputTrdFac');
                            numeroOficioInput.value = data.numero_oficio;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud fetch:', error);
                    datosContainer.textContent = 'Error al cargar los datos';
                });
        }

        // Lógica de visibilidad (N° Acta vs Convalidación)
        function toggleResolucionConvalidacion() {
            var tipo = document.getElementById('impacto').value; 
            var campoResolucion = document.getElementById('campo_resolucion_convalidacion'); 
            var campoNoActa = document.getElementById('campo_no_acta');

            if (tipo === 'NACIONAL') {
                campoResolucion.style.display = 'none';
                campoNoActa.style.display = 'block';
            } else {
                campoResolucion.style.display = 'block';
                campoNoActa.style.display = 'none';
            }
        }

        // Ejecutar al cargar la página para estabilizar el diseño
        toggleResolucionConvalidacion();
    </script>
</body>
</html>