<?php
// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$dbname = "productividad";

$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

// ID de la solicitud (se obtiene, por ejemplo, de un parámetro GET)
$id_solicitud = $_GET['id_solicitud'] ?? 1;
//$id_solicitud = 416;
// Consulta para obtener los profesores con cédula y nombre
$sql_profesores_completo = "
    SELECT tercero.documento_tercero, tercero.nombre_completo 
    FROM solicitud_profesor p 
    JOIN tercero ON tercero.documento_tercero = p.fk_id_profesor
    WHERE p.fk_id_solicitud = ?";
$stmt_profesores_completo = $conn->prepare($sql_profesores_completo);
$stmt_profesores_completo->bind_param("i", $id_solicitud);
$stmt_profesores_completo->execute();
$result_profesores_completo = $stmt_profesores_completo->get_result();
$profesores_completos = [];
while ($row = $result_profesores_completo->fetch_assoc()) {
    $profesores_completos[] = ['documento' => $row['documento_tercero'], 'nombre' => $row['nombre_completo']];
}

// Consulta para obtener la información de la solicitud
$sql = "SELECT * FROM solicitud WHERE id_solicitud_articulo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$result = $stmt->get_result();
$solicitud = $result->fetch_assoc();

// Consulta para obtener solo los identificadores de los profesores (sin nombres)
$sql_profesores_ids = "
    SELECT p.fk_id_profesor 
    FROM solicitud_profesor p 
    WHERE p.fk_id_solicitud = ?";
$stmt_profesores_ids = $conn->prepare($sql_profesores_ids);
$stmt_profesores_ids->bind_param("i", $id_solicitud);
$stmt_profesores_ids->execute();
$result_profesores_ids = $stmt_profesores_ids->get_result();
$profesores_ids = [];
while ($row = $result_profesores_ids->fetch_assoc()) {
    $profesores_ids[] = $row['fk_id_profesor'];
}

// Cerrar las consultas
$stmt->close();
$stmt_profesores_completo->close();
$stmt_profesores_ids->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Actualización</title>
    <style>
           .datos-container {
            margin-top: 5px;
            font-style: italic;
            color: #555;
        }
        .accordion-bodyb {
            
    max-width: auto;
    padding: 14px;
    background-color: #e0ffe0; /* Color de fondo (opcional) */
    border: 1px solid #4CAF50; /* Borde verde (opcional) */
    color: #4CAF50; /* Color de texto verde (opcional) */
    border-radius: 5px; /* Bordes redondeados (opcional) */
    margin-top: 0px; /* Espacio superior para separación */
    margin-left: 10px; /* Desplazamiento hacia la derecha */
}
        .status-alert {
        color: red;
        font-weight: bold;

    }
      .status-box {
        display: inline-block;
        padding: 8px 15px;
        background-color: #f7f9fc;   /* Fondo suave */
        border-radius: 10px;         /* Esquinas redondeadas */
        box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1); /* Sombra ligera */
        margin-right: 10px;          /* Espaciado entre cajas */
        font-family: Arial, sans-serif;
        font-size: 14px;
    }
          .not-found {
        color: red !important;       /* Color rojo para el texto "N/A" */
        font-weight: bold;            /* Negrita para el texto de error */
              
    }
        
   .parent-container {
    display: inline-flex; /* Permite que los elementos se alineen en una línea */
    justify-content: flex-start; /* Alinea los elementos a la izquierda */
  
}

       .container {
    margin: 0; /* Elimina márgenes alrededor del contenedor */
    padding: 10px; /* Elimina el relleno dentro del contenedor */
    width: 100vw; /* Ocupa todo el ancho de la ventana */
    max-width: 100%; /* Permite que el ancho máximo sea 100% de la pantalla */
    overflow: auto; /* Permite desplazamiento en caso de que el contenido sea demasiado alto */
}
             .wrapper { display: flex; }
        .box { 
              flex: 1;
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            margin: 10px; }
        .left { background-color: ghostwhite; }
        .right { background-color: floralwhite; }
        
      .custom-container {
    width: 100%;
    max-width: none; /* Para asegurar que no se limite el ancho */
    margin: 0 auto;
}
body {
    padding-bottom: 20px; /* Opcional: Añade un espacio de relleno al final si el formulario está demasiado cerca del borde */
}
         .alerta-select {
        background-color: #ffecb3; /* Color amarillo claro */
    }
        .alerta-input {
        background-color: #ffecb3; /* Color amarillo claro */
    }
       
    </style>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="margin: 10px;"> <!-- Contenedor con margen -->
    <div class="container mt-0 pb-4 custom-container">
      
    <form method="post" action="actualizar_solicitud.php" style="border: 2px solid #bbb; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
                  <h3 style="text-align: center; font-weight: bold; color: #333; margin-bottom: 20px;">Actualizar solicitud Artículos</h3>
<div class="col-md-3">
    <label for="numero_profesores" class="form-label fw-bold">Profesores solicitantes:</label>
    <div id="profesores-container" class="form-control form-control-sm" style="padding: 0;">
        <?php foreach ($profesores_completos as $profesor): ?>
            <div class="profesor-row mb-1 d-flex align-items-center">
                <!-- Campo editable para la cédula -->
                <input 
                    type="text" 
                    name="profesor_documento[]" 
                    value="<?= htmlspecialchars($profesor['documento']) ?>" 
                    class="form-control form-control-sm mb-1 me-2" 
                    placeholder="Cédula del profesor" 
                    required 
                >
                <!-- Campo informativo para el nombre (solo lectura) -->
                <input 
                    type="text" 
                    value="<?= htmlspecialchars($profesor['nombre']) ?>" 
                    class="form-control form-control-sm me-2" 
                    placeholder="Nombre del profesor" 
                    readonly 
                >
                <!-- Botón para eliminar -->
                <button type="button" class="btn btn-danger btn-sm remove-row">Eliminar</button>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Botón para agregar un nuevo profesor -->
    <button type="button" id="add-profesor" class="btn btn-primary btn-sm mt-2">Agregar Profesor</button>
</div>

        
        <!-- Primera fila -->
<div class="row mb-3 align-items-center">
    <div class="col-md-3">
        <label for="identificador" class="form-label fw-bold">Identificador:</label>
        <input type="text" id="identificador" name="identificador" value="<?= htmlspecialchars($solicitud['identificador_solicitud']) ?>" class="form-control form-control-sm" readonly>
                <input type="hidden" name="id_solicitud" value="<?= htmlspecialchars($solicitud['id_solicitud_articulo']) ?>">

    </div>


    <div class="col-md-3">
        <label for="numero_oficio" class="form-label fw-bold">Número de Oficio:</label>
        <input type="text" id="numero_oficio" name="numero_oficio" value="<?= htmlspecialchars($solicitud['numero_oficio']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold">Estado:</label>
        <div>
            <label><input type="checkbox" name="est_scimago" <?= $solicitud['est_scimago'] ? 'checked' : '' ?>> Scimago</label>
            <label><input type="checkbox" name="est_doaj" <?= $solicitud['est_doaj'] ? 'checked' : '' ?>> DOAJ</label>
            <label><input type="checkbox" name="est_scopus" <?= $solicitud['est_scopus'] ? 'checked' : '' ?>> Scopus</label>
            <label><input type="checkbox" name="est_miar" <?= $solicitud['est_miar'] ? 'checked' : '' ?>> MIAR</label>
        </div>
    </div>
</div>

<!-- Segunda fila -->
<div class="row align-items-center mb-3">
    <div class="col-md-4">
        <label for="titulo_articulo" class="form-label fw-bold">Título del Artículo:</label>
        <input type="text" id="titulo_articulo" name="titulo_articulo" value="<?= htmlspecialchars($solicitud['titulo_articulo']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-1">
        <label for="volumen" class="form-label fw-bold">Volumen:</label>
        <input type="text" id="volumen" name="volumen" value="<?= htmlspecialchars($solicitud['volumen']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-1">
        <label for="numero_r" class="form-label fw-bold">Número:</label>
        <input type="text" id="numero_r" name="numero_r" value="<?= htmlspecialchars($solicitud['numero_r']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-1">
        <label for="ano_publicacion" class="form-label fw-bold">Año:</label>
        <input type="text" id="ano_publicacion" name="ano_publicacion" value="<?= htmlspecialchars($solicitud['ano_publicacion']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-1">
        <label for="numero_autores" class="form-label fw-bold"># Autores:</label>
        <input type="text" id="numero_autores" name="numero_autores" value="<?= htmlspecialchars($solicitud['numero_autores']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-2">
        <label for="tipo_articulo" class="form-label fw-bold">Tipo artículo:</label>
        <select id="tipo_articulo" name="tipo_articulo" class="form-control form-control-sm">
            <option value="">Seleccione un tipo</option>
            <option value="FULL PAPER" <?= $solicitud['tipo_articulo'] == 'FULL PAPER' ? 'selected' : '' ?>>FULL PAPER</option>
            <option value="ARTICULO CORTO" <?= $solicitud['tipo_articulo'] == 'ARTICULO CORTO' ? 'selected' : '' ?>>ARTICULO CORTO</option>
            <option value="EDITORIALES" <?= $solicitud['tipo_articulo'] == 'EDITORIALES' ? 'selected' : '' ?>>EDITORIALES</option>
            <option value="REVISION DE TEMA" <?= $solicitud['tipo_articulo'] == 'REVISION DE TEMA' ? 'selected' : '' ?>>REVISION DE TEMA</option>
            <option value="REPORTE DE CASO" <?= $solicitud['tipo_articulo'] == 'REPORTE DE CASO' ? 'selected' : '' ?>>REPORTE DE CASO</option>
        </select>
    </div>
    <div class="col-sm-2">
        <label for="doi" class="form-label fw-bold">DOI:</label>
        <input type="text" id="doi" name="doi" value="<?= htmlspecialchars($solicitud['doi']) ?>" class="form-control form-control-sm">
    </div>
</div>

<!-- Tercera fila -->
<div class="row align-items-center mb-4">
    <div class="col-sm-4">
        <label for="nombre_revista" class="form-label fw-bold">Nombre de la Revista:</label>
        <input type="text" id="nombre_revista" name="nombre_revista" value="<?= htmlspecialchars($solicitud['nombre_revista']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-1">
        <label for="issn" class="form-label fw-bold">ISSN:</label>
        <input type="text" id="issn" name="issn" value="<?= htmlspecialchars($solicitud['issn']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-1">
        <label for="eissn" class="form-label fw-bold">eISSN:</label>
        <input type="text" id="eissn" name="eissn" value="<?= htmlspecialchars($solicitud['eissn']) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-sm-2">
        <label for="tipo_publindex" class="form-label fw-bold">Tipo de Publindex:</label>
        <select id="tipo_publindex" name="tipo_publindex" class="form-control form-control-sm">
            <option value="">Seleccione un tipo</option>
            <option value="A1" <?= $solicitud['tipo_publindex'] == 'A1' ? 'selected' : '' ?>>A1</option>
            <option value="A2" <?= $solicitud['tipo_publindex'] == 'A2' ? 'selected' : '' ?>>A2</option>
            <option value="B" <?= $solicitud['tipo_publindex'] == 'B' ? 'selected' : '' ?>>B</option>
            <option value="C" <?= $solicitud['tipo_publindex'] == 'C' ? 'selected' : '' ?>>C</option>
        </select>
    </div>
    <div class="col-sm-2">
        <label for="tipo_revista" class="form-label fw-bold">Tipo de Revista:</label>
        <select id="tipo_revista" name="tipo_revista" class="form-control form-control-sm">
            <option value="">Seleccione un tipo</option>
            <option value="INTERNACIONAL" <?= $solicitud['tipo_revista'] == 'INTERNACIONAL' ? 'selected' : '' ?>>INTERNACIONAL</option>
            <option value="NACIONAL" <?= $solicitud['tipo_revista'] == 'NACIONAL' ? 'selected' : '' ?>>NACIONAL</option>
        </select>
    </div>
    <div class="col-sm-2">
        <label for="puntaje" class="form-label fw-bold">Puntaje:</label>
        <input type="text" id="puntaje" name="puntaje" value="<?= htmlspecialchars($solicitud['puntaje']) ?>" class="form-control form-control-sm">
    </div>
</div>


        <!-- Botón de enviar -->
        <button type="submit">Actualizar Solicitud</button>
        <button type="button" onclick="window.history.back();">Regresar</button>

    </form></div></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
    
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const container = document.getElementById("profesores-container");
        const addButton = document.getElementById("add-profesor");

        // Plantilla para una nueva fila de profesor
        const newProfesorTemplate = `
            <div class="profesor-row mb-1 d-flex align-items-center">
                <input 
                    type="text" 
                    name="profesor_documento[]" 
                    value="" 
                    class="form-control form-control-sm mb-1 me-2" 
                    placeholder="Cédula del profesor" 
                    required 
                >
                <input 
                    type="text" 
                    value="" 
                    class="form-control form-control-sm me-2" 
                    placeholder="Nombre del profesor" 
                    readonly 
                >
                <button type="button" class="btn btn-danger btn-sm remove-row">Eliminar</button>
            </div>
        `;

        // Agregar nueva fila de profesor
        addButton.addEventListener("click", function () {
            const newProfesorRow = document.createElement("div");
            newProfesorRow.innerHTML = newProfesorTemplate;
            newProfesorRow.classList.add("profesor-row");
            container.appendChild(newProfesorRow);

            // Asignar evento de eliminación al nuevo botón
            newProfesorRow.querySelector(".remove-row").addEventListener("click", function () {
                newProfesorRow.remove();
            });
        });

        // Asignar evento de eliminación a las filas existentes
        container.querySelectorAll(".remove-row").forEach(button => {
            button.addEventListener("click", function () {
                button.parentElement.remove();
            });
        });
    });
</script>
</html>

