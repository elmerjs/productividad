<?php
// Incluye tu archivo de conexión existente
include 'conn.php';

// Obtén los datos del formulario
$numero_profesores = $_POST['numero_profesores'];
$identificador_base = $_POST['identificador_base'];
$numero_envio = $_POST['numero_envio'];
$identificador_completo = $identificador_base . '_' . $numero_envio;
$numeroOficio = $_POST['numeroOficio'];
$fecha_solicitud = $_POST['fecha_solicitud'];
$tipo_producto = $_POST['tipo_producto'];
$impacto = $_POST['impacto'];
$producto = $_POST['producto'];
$nombre_evento = $_POST['nombre_evento'];
$fecha_evento = $_POST['fecha_evento'];
$lugar_evento = $_POST['lugar_evento'];
$autores = $_POST['autores'];
$evaluacion1 = $_POST['evaluacion1'];
$evaluacion2 = $_POST['evaluacion2'];
$puntaje = $_POST['puntaje']; // Este es el campo de texto
$puntaje_final = $_POST['puntaje_f']; // Calculado y almacenado en la base de datos
    $tipo_productividad = "bonificacion";

// Inicia una transacción para asegurar que todos los datos se guarden correctamente
$conn->begin_transaction();

try {
    // Inserta los datos en la tabla `creacion`
    $sql_creacion = "INSERT INTO creacion_bon (
                        identificador_completo, numeroOficio, fecha_solicitud, tipo_producto, 
                        impacto, producto, nombre_evento,  fecha_evento, lugar_evento, 
                        autores, evaluacion1, evaluacion2, puntaje, puntaje_final, tipo_productividad
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_creacion);
    $stmt->bind_param(
        "ssssssssssidsss",
        $identificador_completo, $numeroOficio, $fecha_solicitud, $tipo_producto, 
        $impacto, $producto, $nombre_evento,  $fecha_evento, $lugar_evento, 
        $autores, $evaluacion1, $evaluacion2, $puntaje, $puntaje_final,$tipo_productividad
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar en `creacion`: " . $stmt->error);
    }

    // Obtén el ID insertado para usarlo en `creacion_profesor`
    $id_creacion = $conn->insert_id;

    // Inserta los datos de los profesores en `creacion_profesor`
    for ($i = 1; $i <= $numero_profesores; $i++) {
        // Supongamos que hay campos específicos para cada profesor
        $documento_profesor = $_POST["cedulaProfesor$i"];

        $sql_creacion_profesor = "INSERT INTO creacion_bon_profesor (id_creacion_bon, documento_profesor) 
                                  VALUES (?, ?)";
        $stmt_profesor = $conn->prepare($sql_creacion_profesor);
        $stmt_profesor->bind_param("is", $id_creacion, $documento_profesor);

        if (!$stmt_profesor->execute()) {
            throw new Exception("Error al guardar en `creacion_profesor`: " . $stmt_profesor->error);
        }
    }

    // Si todo fue exitoso, confirma la transacción
    $conn->commit();
    echo "Datos guardados correctamente.";
} catch (Exception $e) {
    // Si hubo un error, revierte la transacción
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

// Cierra las declaraciones y la conexión
$stmt->close();
if (isset($stmt_profesor)) {
    $stmt_profesor->close();
}
$conn->close();
?>
