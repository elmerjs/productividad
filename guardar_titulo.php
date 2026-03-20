<?php
// Incluir el archivo de conexión
include 'conn.php';

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Recoger y formatear identificadores
    $identificador_base = $_POST['identificador_base'] ?? '';
    $numero_envio       = $_POST['numero_envio'] ?? '';
    $identificador      = $identificador_base . '_' . $numero_envio;

    // 2. Recoger datos del formulario
    $inputTrdFac        = $_POST['inputTrdFac'] ?? '';
    $documento_profesor = $_POST['documento_profesor'] ?? '';
    $producto           = $_POST['producto'] ?? '';
    $impacto            = $_POST['impacto'] ?? ''; // NACIONAL o EXTERIOR
    $tipo_estudio       = $_POST['tipo_estudio'] ?? '';
    $institucion        = $_POST['institucion'] ?? '';
    $fecha_terminacion  = $_POST['fecha_terminacion'] ?? '';
    
    // Manejo de nulos para campos opcionales
    $resolucion_convalidacion = !empty($_POST['resolucion_convalidacion']) ? $_POST['resolucion_convalidacion'] : NULL;
    $no_acta                  = !empty($_POST['no_acta']) ? $_POST['no_acta'] : NULL;
    
    $puntaje            = $_POST['puntaje'] ?? 0;
    $tipo_productividad = "puntos";
    $fecha_solicitud    = date('Y-m-d');

    // 3. Preparar consulta principal (13 parámetros)
    $sql = "INSERT INTO titulos (
                identificador, numero_oficio, documento_profesor, titulo_obtenido, 
                tipo, tipo_estudio, institucion, fecha_terminacion, 
                resolucion_convalidacion, no_acta, puntaje, 
                tipo_productividad, fecha_solicitud
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            "sssssssssssss", 
            $identificador, $inputTrdFac, $documento_profesor, $producto, 
            $impacto, $tipo_estudio, $institucion, $fecha_terminacion, 
            $resolucion_convalidacion, $no_acta, $puntaje, 
            $tipo_productividad, $fecha_solicitud
        );

        if ($stmt->execute()) {
            $id_titulo = $conn->insert_id;

            // 4. Registrar relación en titulo_profesor
            $sql_tp = "INSERT INTO titulo_profesor (id_titulo, fk_tercero) VALUES (?, ?)";
            if ($stmt_tp = $conn->prepare($sql_tp)) {
                $stmt_tp->bind_param("is", $id_titulo, $documento_profesor);
                $stmt_tp->execute();
                $stmt_tp->close();
            }

            // ÉXITO: Redirección profesional
            $conn->close();
            header("Location: index.php?status=success");
            exit();

        } else {
            // ERROR DE EJECUCIÓN
            $error = urlencode($stmt->error);
            header("Location: index.php?status=error&msg=$error");
            exit();
        }
        $stmt->close();
    } else {
        // ERROR DE PREPARACIÓN
        $error = urlencode($conn->error);
        header("Location: index.php?status=error&msg=$error");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>