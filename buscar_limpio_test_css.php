<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Prueba de Bootstrap</title>
</head>
<body>
    <div class="container">
        <h1 class="text-primary">Hola, Bootstrap</h1>
        <button class="btn btn-success">Botón de prueba</button>
        
        <form>
            <div class="mb-3">
                <label for="tipo_articulo" class="form-label">Tipo de Artículo</label>
                <select id="tipo_articulo" class="form-select">
                    <option value="FULL PAPER">FULL PAPER</option>
                    <option value="REVISION DE TEMA">REVISION DE TEMA</option>
                    <option value="EDITORIALES">EDITORIALES</option>
                    <option value="ARTÍCULO CORTO">ARTÍCULO CORTO</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="tipo_publindex" class="form-label">Tipo de Publindex</label>
                <select id="tipo_publindex" class="form-select">
                    <option value="A1">A1</option>
                    <option value="A2">A2</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="numero_autores" class="form-label">Número de Autores</label>
                <input type="number" id="numero_autores" class="form-control" min="1" value="1">
            </div>
            <div class="mb-3">
                <label for="puntaje" class="form-label">Puntaje</label>
                <input type="text" id="puntaje" class="form-control" readonly>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
