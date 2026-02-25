<?php
// Incluir archivo de conexión a la base de datos
include 'conn.php';

// Verificar que el formulario haya sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar y sanitizar datos del formulario
    $identificador = filter_input(INPUT_POST, 'identificador_base', FILTER_SANITIZE_STRING);
    $envio = filter_input(INPUT_POST, 'numero_envio', FILTER_SANITIZE_NUMBER_INT);
    $identificador_completo = $identificador . '_' . $envio;

    $numero_oficio = filter_input(INPUT_POST, 'inputTrdFac', FILTER_SANITIZE_STRING);
    $producto = filter_input(INPUT_POST, 'producto', FILTER_SANITIZE_STRING);
$numero_profesores = isset($_POST['hidden_numero_profesores']) ? $_POST['hidden_numero_profesores'] : null;
    $puntaje = filter_input(INPUT_POST, 'puntaje', FILTER_VALIDATE_FLOAT);
    $estado = "ac"; // Valor por defecto
    $fecha_solicitud = date('Y-m-d');
    $tipo_productividad = "puntos";

    // Validar número de profesores
    if ($numero_profesores < 1) {
        echo "Número de profesores inválido.";
        exit;
    }

    // Crear la consulta de inserción para la tabla `patentes`
    $sql = "INSERT INTO patentes (
                identificador,
                numero_oficio,
                fecha_solicitud,
                producto,
                numero_profesores,
                puntaje,
                estado, tipo_productividad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            "ssssdiss",
            $identificador_completo,
            $numero_oficio,
            $fecha_solicitud,
            $producto,
            $numero_profesores,
            $puntaje,
            $estado,$tipo_productividad
        );

        if ($stmt->execute()) {
            $id_patente = $conn->insert_id;
            for ($i = 1; $i <= $numero_profesores; $i++) {
                $id_profesor = trim($_POST["documento_$i"]);
                if (!empty($id_profesor)) {
                    $sql_profesor = "INSERT INTO patente_profesor (id_patente, id_profesor) VALUES (?, ?)";
                    if ($stmt_profesor = $conn->prepare($sql_profesor)) {
                        $stmt_profesor->bind_param("is", $id_patente, $id_profesor);
                        if (!$stmt_profesor->execute()) {
                            error_log("Error al insertar profesor $i: " . $stmt_profesor->error);
                        }
                        $stmt_profesor->close();
                    } else {
                        error_log("Error preparando la consulta para profesor $i: " . $conn->error);
                    }
                }
            }
            echo "Patente y profesores guardados exitosamente.";
        } else {
            error_log("Error al guardar la patente: " . $stmt->error);
            echo "Error al guardar la patente.";
        }
        echo '<br><a href="menu_ini.php">Volver al menú</a>';

        $stmt->close();
    } else {
        error_log("Error preparando la consulta: " . $conn->error);
        echo "Error preparando la consulta.";
    }
    $conn->close();
} else {
    echo "Método de solicitud no permitido.";
}
?>
