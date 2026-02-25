<?php
// Conexión a la base de datos
include 'conn.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibir los datos del formulario
    $identificador_base = $_POST['identificador_base'];
    $numero_envio = $_POST['numero_envio'];
    $inputTrdFac = $_POST['inputTrdFac'];
    $producto = $_POST['producto'];
    $difusion = $_POST['difusion'];
    $finalidad = $_POST['finalidad'];
    $area = $_POST['area'];
    $evaluador1 = $_POST['evaluador1'];
    $evaluador2 = $_POST['evaluador2'];
    $puntaje = $_POST['puntaje'];
    $puntaje = str_replace(',', '.', $puntaje);
    $puntaje = (float)$puntaje;
$numero_profesores = isset($_POST['hidden_numero_profesores']) ? $_POST['hidden_numero_profesores'] : null;
echo "numero prf:  ". $numero_profesores. " ?? ";
    // Redondear a 2 decimales
    $puntaje = round($puntaje, 2);
    $tipo_productividad = "puntos";

    $fecha_solicitud_tr = date('Y-m-d H:i:s'); // Fecha y hora actual

    // Concatenar identificador_base y numero_envio con un guion bajo
    $identificador_concatenado = $identificador_base . '_' . $numero_envio;

    // Preparar la consulta de inserción
    $sql = "INSERT INTO trabajos_cientificos (
                identificador, 
                numero_oficio, 
                producto, 
                difusion, 
                finalidad, 
                area, 
                evaluador1, 
                evaluador2, 
                puntaje, 
                fecha_solicitud_tr,
                tipo_productividad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Preparar la declaración
    if ($stmt = $conn->prepare($sql)) {
        // Vincular los parámetros
        $stmt->bind_param("ssssssssdss", 
            $identificador_concatenado, 
            $inputTrdFac, 
            $producto, 
            $difusion, 
            $finalidad, 
            $area, 
            $evaluador1, 
            $evaluador2, 
            $puntaje, 
            $fecha_solicitud_tr, 
            $tipo_productividad
        );

        // Ejecutar la consulta
        if ($stmt->execute()) {
            $id_produccion = $conn->insert_id;
            for ($i = 1; $i <= $numero_profesores; $i++) {
                $id_profesor = trim($_POST["documento_$i"]);
                if (!empty($id_profesor)) {
                    $sql_profesor = "INSERT INTO trabajo_profesor (id_trabajo_cientifico, profesor_id) VALUES (?, ?)";
                    if ($stmt_profesor = $conn->prepare($sql_profesor)) {
                        $stmt_profesor->bind_param("is", $id_produccion, $id_profesor);
                        if (!$stmt_profesor->execute()) {
                            error_log("Error al insertar profesor $i: " . $stmt_profesor->error);
                        }
                        $stmt_profesor->close();
                    } else {
                        error_log("Error preparando la consulta para profesor $i: " . $conn->error);
                    }
                }
            }
            echo "trabajo t y profesores guardados exitosamente.";
        } else {
            echo "Error al guardar los datos: " . $stmt->error;
        }

        // Cerrar la declaración
        $stmt->close();
    } else {
        echo "Error al preparar la consulta: " . $conexion->error;
    }

} else {
    echo "Método de solicitud no permitido.";
}
?>
