<?php
require 'conn.php';

if(isset($_GET['id'])) {
    $cient_id = $_GET['id'];

    // Consulta para obtener los datos de la comisión basada en el id
    $query = "SELECT * FROM trabajos_cientificos WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cient = $result->fetch_assoc();

    if (!$cient) {
        echo "No se encontraron datos para el ID de comisión proporcionado.";
        exit();
    }
}

// Consulta para obtener los profesores con cédula y nombre
$sql_profesores_completo = "
    SELECT tercero.documento_tercero, tercero.nombre_completo 
    FROM trabajo_profesor p 
    JOIN tercero ON tercero.documento_tercero = p.profesor_id
    WHERE p.id_trabajo_cientifico = ?";
$stmt_profesores_completo = $conn->prepare($sql_profesores_completo);
$stmt_profesores_completo->bind_param("i", $cient_id);
$stmt_profesores_completo->execute();
$result_profesores_completo = $stmt_profesores_completo->get_result();
$profesores_completos = [];
while ($row = $result_profesores_completo->fetch_assoc()) {
    $profesores_completos[] = ['documento' => $row['documento_tercero'], 'nombre' => $row['nombre_completo']];
}

// Cerrar conexiones
$stmt->close();
$stmt_profesores_completo->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Solicitud de Trabajo Cientifico</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .profesor-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .profesor-row input {
    margin-right: 10px;
    background-color: #f0f0f0; /* Cambia el fondo para probar */
    color: #000; /* Asegúrate de que el texto sea visible */
}
        .remove-row {
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .remove-row:hover {
            background-color: #d32f2f;
        }
        .btn-add {
            margin-top: 10px;
        }
        .form-container {
            margin-top: 20px;
        }
        .form-container label {
            font-weight: bold;
        }
        .form-container input {
            margin-bottom: 10px;
        }
        .container {
            padding-top: 30px;
        }
    </style>
</head>
<body>

<div class="container">
    <h4 class="mb-4">Editar Solicitud de Trabajo Cientifico</h4>
    <form action="actualizar_solicitud_cient.php" method="post">
        <input type="hidden" name="comision_id" value="<?= $cient_id ?>">

        <!-- Profesores -->
        <div class="col-md-12 form-container">
            <label for="profesores" class="form-label">Profesores solicitantes:</label>
            <div id="profesores-container" class="form-control">
                <?php foreach ($profesores_completos as $profesor): ?>
                    <div class="profesor-row">
                        <input type="text" name="profesor_documento[]" value="<?= htmlspecialchars($profesor['documento']) ?>" class="form-control form-control-sm mb-2" placeholder="Cédula del profesor" required>
                        <input type="text" value="<?= htmlspecialchars($profesor['nombre']) ?>" class="form-control form-control-sm mb-2" placeholder="Nombre del profesor" readonly>
                        <button type="button" class="remove-row btn btn-sm">Eliminar</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-profesor" class="btn btn-primary btn-sm btn-add">Agregar Profesor</button>
        </div>
<input type="hidden" name="id_solicitud" value="<?= $cient_id ?>">
        <div class="col-md-12 form-container">
            <div id="profesores-container" class="form-control">

        <!-- Otros campos -->
        <div class="row mb-3">
            <div class="col-md-2">
                <label for="identificador" class="form-label">Identificador:</label>
                <input type="text" class="form-control" id="identificador" name="identificador" value="<?= $cient['identificador'] ?>" required>
            </div>
            <div class="col-md-2">
                <label for="oficio" class="form-label">Oficio:</label>
                <input type="text" class="form-control" id="oficio" name="oficio" value="<?= $cient['numero_oficio'] ?>" required>
            </div>
                        <div class="col-md-8">
                <label for="producto" class="form-label">Producto:</label>
                <input type="text" class="form-control" id="producto" name="producto" value="<?= $cient['producto'] ?>" required>
            </div>
        </div>

    

        <div class="row mb-3">
             <div class="col-md-4">
                <label for="difusion" class="form-label">Difusión:</label>
                <input type="text" class="form-control" id="difusion" name="difusion" value="<?= $cient['difusion'] ?>" required>
            </div>
            <div class="col-md-4">
                <label for="finalidad" class="form-label">Finalidad:</label>
                <input type="text" class="form-control" id="finalidad" name="finalidad" value="<?= $cient['finalidad'] ?>" required>
            </div>
            <div class="col-md-4">
                <label for="area" class="form-label">Área:</label>
                <input type="text" class="form-control" id="area" name="area" value="<?= $cient['area'] ?>" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label for="evaluador1" class="form-label">Evaluador 1:</label>
                <input type="text" class="form-control" id="evaluador1" name="evaluador1" value="<?= $cient['evaluador1'] ?>" required>
            </div>
            <div class="col-md-4">
                <label for="evaluador2" class="form-label">Evaluador 2:</label>
                <input type="text" class="form-control" id="evaluador2" name="evaluador2" value="<?= $cient['evaluador2'] ?>" required>
            </div>
             <div class="col-md-4">
                <label for="puntaje" class="form-label">Puntaje:</label>
                <input type="text" class="form-control" id="puntaje" name="puntaje" value="<?= $cient['puntaje'] ?>" required>
            </div>
        </div>

       
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <button type="button" class="btn btn-secondary" onclick="window.history.back();">Regresar</button>
            </div>
                
</div>
    </form>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const container = document.getElementById("profesores-container");
        const addButton = document.getElementById("add-profesor");

        const newProfesorTemplate = `
            <div class="profesor-row">
                <input type="text" name="profesor_documento[]" value="" class="form-control form-control-sm mb-2" placeholder="Cédula del profesor" required>
                <input type="text" value="" class="form-control form-control-sm mb-2" placeholder="Nombre del profesor" readonly>
                <button type="button" class="remove-row btn btn-sm">Eliminar</button>
            </div>
        `;

        // Función para obtener el nombre del profesor basado en la cédula
        function obtenerNombrePorCedula(cedula, inputNombre) {
            // Realizar la llamada AJAX para obtener el nombre usando GET
            fetch('obtener_datos_profesor.php?documento=' + cedula)
                .then(response => response.json()) // Obtener respuesta en formato JSON
                .then(data => {
                    if (data.error) {
                        inputNombre.value = "No se encontró el profesor"; // En caso de error
                    } else {
                        inputNombre.value = data.nombre_completo; // Asignar el nombre al campo
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    inputNombre.value = "Error al buscar"; // Manejo de error
                });
        }

        addButton.addEventListener("click", function () {
            const newProfesorRow = document.createElement("div");
            newProfesorRow.innerHTML = newProfesorTemplate;
            container.appendChild(newProfesorRow);

            // Añadir funcionalidad para eliminar la fila
            newProfesorRow.querySelector(".remove-row").addEventListener("click", function () {
                newProfesorRow.remove();
            });

            // Obtener los campos de cédula y nombre
            const inputCedula = newProfesorRow.querySelector("input[name='profesor_documento[]']");
            const inputNombre = newProfesorRow.querySelector("input[readonly]");

            // Cuando se ingresa la cédula, buscar el nombre automáticamente
            inputCedula.addEventListener("blur", function () {
                const cedula = inputCedula.value;
                if (cedula) {
                    obtenerNombrePorCedula(cedula, inputNombre); // Llama la función para obtener el nombre
                }
            });
        });

        // Agregar eventos de eliminar a las filas ya existentes
        container.querySelectorAll(".remove-row").forEach(button => {
            button.addEventListener("click", function () {
                button.closest(".profesor-row").remove();
            });
        });
    });
</script>

</body>
</html>
