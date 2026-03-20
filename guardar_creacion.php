<?php
// Incluir archivo de conexión
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Identificación y Profesores
    $numero_profesores      = intval($_POST['numero_profesores'] ?? 0);
    $identificador_base     = $_POST['identificador_base'] ?? '';
    $numero_envio           = $_POST['numero_envio'] ?? '';
    $identificador_completo = $identificador_base . '_' . $numero_envio;
    
    // 2. Datos de la Obra y Evento
    $numeroOficio     = $_POST['numeroOficio'] ?? '';
    $fecha_solicitud  = $_POST['fecha_solicitud'] ?? '';
    $tipo_producto    = $_POST['tipo_producto'] ?? '';
    $impacto          = $_POST['impacto'] ?? '';
    $producto         = $_POST['producto'] ?? '';
    $nombre_evento    = $_POST['nombre_evento'] ?? '';
    $evento           = $_POST['evento'] ?? '';
    $fecha_evento     = $_POST['fecha_evento'] ?? NULL;
    $fecha_evento_f   = $_POST['fecha_evento_f'] ?? NULL; // Nueva fecha fin
    $lugar_evento     = $_POST['lugar_evento'] ?? '';
    
    // 3. Evaluación y Puntaje
    $autores        = $_POST['autores'] ?? 0;
    $evaluacion1    = $_POST['evaluacion1'] ?? NULL;
    $evaluacion2    = $_POST['evaluacion2'] ?? NULL;
    $evaluacion3    = !empty($_POST['evaluacion3']) ? $_POST['evaluacion3'] : NULL; // Nuevo Evaluador 3
    $puntaje_detalle = $_POST['puntaje'] ?? ''; // Texto del cálculo
    $puntaje_final   = $_POST['puntaje_f'] ?? 0; // Valor numérico
    $tipo_productividad = "puntos";

    // Iniciar transacción para seguridad de datos
    $conn->begin_transaction();

    try {
        // SQL con 18 parámetros (incluyendo evaluacion3 y fecha_evento_f)
        $sql = "INSERT INTO creacion (
                    identificador_completo, numeroOficio, fecha_solicitud, tipo_producto, 
                    impacto, producto, nombre_evento, evento, fecha_evento, fecha_evento_f, 
                    lugar_evento, autores, evaluacion1, evaluacion2, evaluacion3, 
                    puntaje, puntaje_final, tipo_productividad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        // s = string, i = integer, d = double/decimal
        $stmt->bind_param(
            "sssssssssssidsssss", 
            $identificador_completo, $numeroOficio, $fecha_solicitud, $tipo_producto, 
            $impacto, $producto, $nombre_evento, $evento, $fecha_evento, $fecha_evento_f, 
            $lugar_evento, $autores, $evaluacion1, $evaluacion2, $evaluacion3, 
            $puntaje_detalle, $puntaje_final, $tipo_productividad
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al insertar obra: " . $stmt->error);
        }

        $id_creacion = $conn->insert_id;

        // 4. Inserción de Profesores Solicitantes
        if ($numero_profesores > 0) {
            $sql_prof = "INSERT INTO creacion_profesor (id_creacion, documento_profesor) VALUES (?, ?)";
            $stmt_prof = $conn->prepare($sql_prof);

            for ($i = 1; $i <= $numero_profesores; $i++) {
                $cedula = $_POST["cedulaProfesor$i"] ?? '';
                if (!empty($cedula)) {
                    $stmt_prof->bind_param("is", $id_creacion, $cedula);
                    $stmt_prof_res = $stmt_prof->execute();
                    if (!$stmt_prof_res) {
                        throw new Exception("Error en profesor $i: " . $stmt_prof->error);
                    }
                }
            }
            $stmt_prof->close();
        }

        // Confirmar todo si no hubo errores
        $conn->commit();
        
        // Redirección exitosa profesional
        $conn->close();
        header("Location: index.php?status=success");
        exit();

    } catch (Exception $e) {
        // Revertir cambios si algo falló
        $conn->rollback();
        $error_msg = urlencode($e->getMessage());
        header("Location: index.php?status=error&msg=$error_msg");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>