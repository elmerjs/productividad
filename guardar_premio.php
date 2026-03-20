<?php
// Incluir archivo de conexión a la base de datos
include 'conn.php';

// Verificar que el formulario haya sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Capturar y formatear datos del formulario
    $identificadorBase    = $_POST['identificador_base'] ?? '';
    $numeroEnvio          = $_POST['numero_envio'] ?? '';
    $identificadorCompleto = $identificadorBase . '_' . $numeroEnvio;

    $numeroOficio      = $_POST['numeroOficio'] ?? '';
    $fechaSolicitud    = $_POST['fecha_solicitud'] ?? '';
    $nombreEvento      = $_POST['nombre_evento'] ?? '';
    $ambito            = $_POST['ambito'] ?? '';
    $categoriaPremio   = $_POST['categoria_premio'] ?? '';
    $nivelGanado       = $_POST['nivel_ganado'] ?? '';
    $lugarFecha        = $_POST['lugar_fecha'] ?? '';
    $numeroProfesores  = intval($_POST['numero_profesores'] ?? 0);
    $autores           = $_POST['autores'] ?? '';
    $puntos            = $_POST['puntos'] ?? 0;
    $estado            = "ac"; 
    $tipo_productividad = "puntos";

    // 2. Consulta de inserción principal (13 parámetros)
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
                estado, 
                tipo_productividad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Vincular parámetros: s=string, i=integer, d=double/float
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
            $estado,
            $tipo_productividad
        );

        if ($stmt->execute()) {
            $idPremio = $conn->insert_id;

            // 3. Inserción de relación con Profesores (Optimizado)
            if ($numeroProfesores > 0) {
                $sqlProfesor = "INSERT INTO premios_profesor (id_premio, id_profesor) VALUES (?, ?)";
                if ($stmtProfesor = $conn->prepare($sqlProfesor)) {
                    for ($i = 1; $i <= $numeroProfesores; $i++) {
                        $idProfesor = $_POST["cedula_$i"] ?? '';
                        if (!empty($idProfesor)) {
                            $stmtProfesor->bind_param("is", $idPremio, $idProfesor);
                            $stmtProfesor->execute();
                        }
                    }
                    $stmtProfesor->close();
                }
            }

            // ÉXITO: Redirección profesional al index
            $conn->close();
            header("Location: index.php?status=success");
            exit();

        } else {
            // ERROR DE EJECUCIÓN: Redirigir con mensaje de error
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
    // Acceso no permitido
    header("Location: index.php");
    exit();
}
?>