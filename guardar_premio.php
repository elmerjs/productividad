<?php
// Incluir archivo de conexión a la base de datos
include 'conn.php';

// Verificar que el formulario haya sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar datos del formulario
    $identificadorBase = $_POST['identificador_base'];
    $numeroEnvio = $_POST['numero_envio'];
    $identificadorCompleto = $identificadorBase . '_' . $numeroEnvio;

    $numeroOficio = $_POST['numeroOficio'];
    $fechaSolicitud = $_POST['fecha_solicitud'];
    $nombreEvento = $_POST['nombre_evento'];
    $ambito = $_POST['ambito'];
    $categoriaPremio = $_POST['categoria_premio'];
    $nivelGanado = $_POST['nivel_ganado'];
    $lugarFecha = $_POST['lugar_fecha'];
    $numeroProfesores = $_POST['numero_profesores'];
    $autores = $_POST['autores'];
    $puntos = $_POST['puntos'];
    $estado = "ac"; // Valor por defecto
    $tipo_productividad = "puntos";

    // Crear la consulta de inserción para la tabla `premios`
    $sql = "INSERT INTO premios (
                identificador,
                numero_oficio,
                fecha_solicitud,
                nombre_evento,
                ambito,
                categoria_premio,
                nivel_ganado,
                lugar_fecha,
                numero_profesores,
                autores,
                puntos,
                estado, tipo_productividad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Preparar la consulta para evitar inyecciones SQL
    if ($stmt = $conn->prepare($sql)) {
        // Vincular parámetros
        $stmt->bind_param(
            "ssssssssisdss",
            $identificadorCompleto,
            $numeroOficio,
            $fechaSolicitud,
            $nombreEvento,
            $ambito,
            $categoriaPremio,
            $nivelGanado,
            $lugarFecha,
            $numeroProfesores,
            $autores,
            $puntos,
            $estado,$tipo_productividad
        );

        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Obtener el último ID insertado para el premio
            $idPremio = $conn->insert_id;

            // Insertar datos en la tabla `premios_profesor`
            for ($i = 1; $i <= $numeroProfesores; $i++) {
                $idProfesor = $_POST["cedula_$i"];
                
                // Verificar que la cédula no esté vacía
                if (!empty($idProfesor)) {
                    // Crear la consulta de inserción para `premios_profesor`
                    $sqlProfesor = "INSERT INTO premios_profesor (id_premio, id_profesor) VALUES (?, ?)";

                    if ($stmtProfesor = $conn->prepare($sqlProfesor)) {
                        // Vincular parámetros para `premios_profesor`
                        $stmtProfesor->bind_param("is", $idPremio, $idProfesor);

                        // Ejecutar la consulta
                        if (!$stmtProfesor->execute()) {
                            echo "Error al insertar profesor $i: " . $stmtProfesor->error;
                        }

                        // Cerrar la declaración del profesor
                        $stmtProfesor->close();
                    } else {
                        echo "Error preparando la consulta para profesor $i: " . $conn->error;
                    }
                }
            }

            echo "Premio y profesores guardados exitosamente.";
        } else {
            echo "Error al guardar el premio: " . $stmt->error;
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
