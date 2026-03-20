<?php
require 'conn.php';

if(isset($_GET['id'])) {
    $libro_id = $_GET['id'];

    $query = "SELECT * FROM libros WHERE id_libro = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $libro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $libro = $result->fetch_assoc();

    if (!$libro) {
        echo "No se encontraron datos.";
        exit();
    }
}

$sql_profesores_completo = "
    SELECT tercero.documento_tercero, tercero.nombre_completo 
    FROM libro_profesor p 
    JOIN tercero ON tercero.documento_tercero = p.id_profesor
    WHERE p.id_libro = ?";
$stmt_profesores_completo = $conn->prepare($sql_profesores_completo);
$stmt_profesores_completo->bind_param("i", $libro_id);
$stmt_profesores_completo->execute();
$result_profesores_completo = $stmt_profesores_completo->get_result();
$profesores_completos = [];
while ($row = $result_profesores_completo->fetch_assoc()) {
    $profesores_completos[] = ['documento' => $row['documento_tercero'], 'nombre' => $row['nombre_completo']];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Solicitud Libros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; color: #334155; }
        .form-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        label { font-weight: 600; color: #475569; font-size: 0.9rem; margin-bottom: 5px; }
        .profesor-row { display: flex; gap: 10px; margin-bottom: 8px; }
        .remove-row { background-color: #fee2e2; color: #ef4444; border: none; padding: 0 15px; border-radius: 6px; }
        .remove-row:hover { background-color: #fecaca; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="form-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-edit me-2 text-primary"></i>Editar Solicitud Libros</h4>
            <span class="badge bg-secondary">ID: <?= $libro_id ?></span>
        </div>

        <form action="actualizar_solicitud_libros.php" method="post">
            <input type="hidden" name="id_solicitud" value="<?= $libro_id ?>">

            <div class="mb-4">
                <label class="form-label">Profesores solicitantes:</label>
                <div id="profesores-container" class="p-3 border rounded bg-light">
                    <?php foreach ($profesores_completos as $profesor): ?>
                        <div class="profesor-row">
                            <input type="text" name="profesor_documento[]" value="<?= htmlspecialchars($profesor['documento']) ?>" class="form-control form-control-sm" placeholder="Cédula" required>
                            <input type="text" value="<?= htmlspecialchars($profesor['nombre']) ?>" class="form-control form-control-sm" placeholder="Nombre" readonly>
                            <button type="button" class="remove-row btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-profesor" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="fas fa-plus me-1"></i> Agregar Profesor
                </button>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Identificador:</label>
                    <input type="text" class="form-control" name="identificador" value="<?= $libro['identificador'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label>Oficio:</label>
                    <input type="text" class="form-control" name="oficio" value="<?= $libro['numero_oficio'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label>Producto:</label>
                    <input type="text" class="form-control" name="producto" value="<?= $libro['producto'] ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-2">
                    <label>Tipo:</label>
                    <select class="form-select" id="tipo_libro" name="tipo">
                        <option value="INVESTIGACION" <?= $libro['tipo_libro'] == 'INVESTIGACION' ? 'selected' : '' ?>>INVESTIGACIÓN</option>
                        <option value="TEXTO" <?= $libro['tipo_libro'] == 'TEXTO' ? 'selected' : '' ?>>TEXTO</option>
                        <option value="ENSAYO" <?= $libro['tipo_libro'] == 'ENSAYO' ? 'selected' : '' ?>>ENSAYO</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>ISBN:</label>
                    <input type="text" class="form-control" name="isbn" value="<?= $libro['isbn'] ?>" required>
                </div>
                <div class="col-md-2">
                    <label>Mes/Año Edición:</label>
                    <input type="month" class="form-control" name="mes_ano" value="<?= $libro['mes_ano_edicion'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label>Editorial:</label>
                    <input type="text" class="form-control" name="editorial" value="<?= $libro['nombre_editorial'] ?>" required>
                </div>
                <div class="col-md-2">
                    <label>Tiraje:</label>
                    <input type="text" class="form-control" name="tiraje" value="<?= $libro['tiraje'] ?>" required>
                </div>
            </div>

            <div class="row mb-4 align-items-end">
                <div class="col-md-2">
                    <label>Autores:</label>
                    <input type="number" class="form-control" id="autores" name="autores" value="<?= $libro['autores'] ?>" required>
                </div>
                <div class="col-md-2">
                    <label>Eval. 1:</label>
                    <input type="text" class="form-control eval-input" id="evaluacion1" name="evaluador1" value="<?= $libro['evaluacion_1'] ?>" required>
                </div>
                <div class="col-md-2">
                    <label>Eval. 2:</label>
                    <input type="text" class="form-control eval-input" id="evaluacion2" name="evaluador2" value="<?= $libro['evaluacion_2'] ?>" required>
                </div>

                <?php $tiene_eval3 = !empty($libro['evaluacion_3']); ?>
                <div class="col-md-2" id="grupo_eval3" style="<?= $tiene_eval3 ? '' : 'display:none;' ?>">
                    <label>Eval. 3:</label>
                    <input type="text" class="form-control eval-input" id="evaluacion3" name="evaluador3" value="<?= $libro['evaluacion_3'] ?>">
                </div>
                
                <?php if(!$tiene_eval3): ?>
                <div class="col-md-2" id="col_btn_eval3">
                    <button type="button" class="btn btn-outline-info btn-sm w-100" onclick="activarEval3()">+ Evaluador</button>
                </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="text-primary">Puntaje Final:</label>
                    <input type="text" class="form-control fw-bold border-primary" id="puntaje_f" name="puntaje" value="<?= $libro['puntaje_final'] ?>" readonly>
                </div>
            </div>

            <div class="bg-light p-3 rounded mb-4">
                <label class="small text-muted">Cálculo detallado:</label>
                <input type="text" id="puntaje_detalle" class="form-control form-control-sm text-muted" readonly style="background:transparent; border:none;">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Actualizar Cambios
                </button>
                <button type="button" class="btn btn-light border px-4" onclick="window.history.back();">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function activarEval3() {
    document.getElementById('grupo_eval3').style.display = 'block';
    if(document.getElementById('col_btn_eval3')) document.getElementById('col_btn_eval3').style.display = 'none';
    calcularPuntaje();
}

// Lógica de cálculo (reutilizada del modal para consistencia)
function redondearHaciaAbajo(valor) { return Math.floor(valor * 100) / 100; }

function calcularPuntaje() {
    const autores = parseInt(document.getElementById('autores').value);
    const eval1 = parseFloat(document.getElementById('evaluacion1').value);
    const eval2 = parseFloat(document.getElementById('evaluacion2').value);
    const eval3Input = document.getElementById('evaluacion3');
    const eval3 = parseFloat(eval3Input.value);
    const tipoLibro = document.getElementById('tipo_libro').value;

    const usarEval3 = document.getElementById('grupo_eval3').style.display !== 'none' && !isNaN(eval3);

    if (!isNaN(eval1) && !isNaN(eval2) && !isNaN(autores) && autores > 0) {
        let suma = eval1 + eval2;
        let divisor = 2;
        if (usarEval3) { suma += eval3; divisor = 3; }

        const promedio = (suma / divisor).toFixed(2);
        const porcentaje = (promedio / 100).toFixed(4);

        let multiplicador = (tipoLibro === 'INVESTIGACION') ? 20 : 15;
        const puntajeBase = porcentaje * multiplicador;
        
        let puntajeFinal;
        if (autores <= 3) puntajeFinal = puntajeBase;
        else if (autores <= 5) puntajeFinal = puntajeBase / 2;
        else puntajeFinal = puntajeBase / (autores / 2);

        document.getElementById('puntaje_f').value = redondearHaciaAbajo(puntajeFinal).toFixed(2);
        document.getElementById('puntaje_detalle').value = `Promedio: ${promedio}% | Base: ${puntajeBase.toFixed(2)} | Divisor Autores: ${autores > 3 ? (autores > 5 ? autores/2 : 2) : 1}`;
    }
}

// Eventos para recalcular
document.querySelectorAll('.eval-input, #autores, #tipo_libro').forEach(el => {
    el.addEventListener('input', calcularPuntaje);
});

// Lógica de agregar/quitar profesores (reparada)
document.getElementById("add-profesor").addEventListener("click", function () {
    const container = document.getElementById("profesores-container");
    const div = document.createElement("div");
    div.className = "profesor-row";
    div.innerHTML = `
        <input type="text" name="profesor_documento[]" class="form-control form-control-sm" placeholder="Cédula" required>
        <input type="text" class="form-control form-control-sm" placeholder="Nombre" readonly>
        <button type="button" class="remove-row btn-sm"><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(div);

    const inputCedula = div.querySelector("input[name='profesor_documento[]']");
    const inputNombre = div.querySelector("input[readonly]");

    inputCedula.addEventListener("blur", function () {
        if (this.value) {
            fetch('obtener_datos_profesor.php?documento=' + this.value)
                .then(r => r.json())
                .then(data => { inputNombre.value = data.nombre_completo || "No encontrado"; });
        }
    });

    div.querySelector(".remove-row").addEventListener("click", () => div.remove());
});

document.querySelectorAll(".remove-row").forEach(btn => {
    btn.addEventListener("click", function() { this.closest(".profesor-row").remove(); });
});

// Ejecutar cálculo inicial
window.onload = calcularPuntaje;
</script>

</body>
</html>