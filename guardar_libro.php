<?php
// Incluir archivo de conexión a la base de datos
// Incluir archivo de conexión a la base de datos
include 'conn.php';

// Verificar que el formulario haya sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar datos del formulario
    $identificador = $_POST['identificador_base'];
    $envio = $_POST['numero_envio'];
    $identificador_completo = $identificador . '_' . $envio;

    $numero_oficio = $_POST['numeroOficio'];
    $fecha_solicitud = $_POST['fecha_solicitud'];
    $tipo_libro = $_POST['tipo_libro'];
    $producto = $_POST['producto'];
    $isbn = $_POST['isbn'];
    $mes_ano_edicion = $_POST['mes_anio_edicion'];
    $nombre_editorial = $_POST['nombre_editorial'];
    $tiraje = $_POST['tiraje'];
    $numero_profesores = $_POST['numero_profesores'];
    $autores = $_POST['autores'];
    $evaluacion_1 = $_POST['evaluacion1'];
    $evaluacion_2 = $_POST['evaluacion2'];
    $puntaje = $_POST['puntaje'];
    $puntaje_final = $_POST['puntaje_f'];
    $estado = "ac"; // Valor por defecto
    $tipo_productividad = "puntos";

    // Crear la consulta de inserción para la tabla `libros`
    $sql = "INSERT INTO libros (
                identificador,
                numero_oficio,
                fecha_solicitud,
                tipo_libro,
                producto,
                isbn,
                mes_ano_edicion,
                nombre_editorial,
                tiraje,
                numero_profesores,
                autores,
                evaluacion_1,
                evaluacion_2,
                calculo,
                puntaje_final,
                estado, tipo_productividad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,  ?,?, ?, ?, ?, ?, ?)";

    // Preparar la consulta para evitar inyecciones SQL
    if ($stmt = $conn->prepare($sql)) {
        // Vincular parámetros
        $stmt->bind_param(
            "ssssssssiiissssss",
            $identificador_completo,
            $numero_oficio,
            $fecha_solicitud,
            $tipo_libro,
            $producto,
            $isbn,
            $mes_ano_edicion,
            $nombre_editorial,
            $tiraje,
            $numero_profesores,
            $autores,
            $evaluacion_1,
            $evaluacion_2, $puntaje,
            $puntaje_final,
            $estado,$tipo_productividad
        );

        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Obtener el último ID insertado para el libro
            $id_libro = $conn->insert_id;

            // Insertar datos en la tabla `libro_profesor`
            for ($i = 1; $i <= $numero_profesores; $i++) {
                $id_profesor = $_POST["cedulaProfesor$i"];
                
                // Verificar que la cédula no esté vacía
                if (!empty($id_profesor)) {
                    // Crear la consulta de inserción para `libro_profesor`
                    $sql_profesor = "INSERT INTO libro_profesor (id_libro, id_profesor) VALUES (?, ?)";

                    if ($stmt_profesor = $conn->prepare($sql_profesor)) {
                        // Vincular parámetros para `libro_profesor`
                        $stmt_profesor->bind_param("is", $id_libro, $id_profesor);

                        // Ejecutar la consulta
                        if (!$stmt_profesor->execute()) {
                            echo "Error al insertar profesor $i: " . $stmt_profesor->error;
                        }

                        // Cerrar la declaración del profesor
                        $stmt_profesor->close();
                    } else {
                        echo "Error preparando la consulta para profesor $i: " . $conn->error;
                    }
                }
            }

            echo "Libro y profesores guardados exitosamente.";
        } else {
            echo "Error al guardar el libro: " . $stmt->error;
        }
        echo '<br><a href="menu_ini.php">Volver al menú</a>';

        // Cerrar la declaración
        $stmt->close();
    } else {
        echo "Error preparando la consulta: " . $conn->error;
    }

    // Cerrar la conexión a la base de datos
    $conn->close();
} else {
    echo "Método de solicitud no permitido.";
}

?>
