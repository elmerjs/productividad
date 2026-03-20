<?php
/**
 * Registro de Libros y Profesores
 * Procesa la inserción con soporte para hasta 3 evaluadores.
 */
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Capturar y formatear identificador
    $identificador = $_POST['identificador_base'] ?? '';
    $envio = $_POST['numero_envio'] ?? '';
    $identificador_completo = $identificador . '_' . $envio;

    // 2. Capturar datos generales del libro
    $numero_oficio      = $_POST['numeroOficio'] ?? '';
    $fecha_solicitud    = $_POST['fecha_solicitud'] ?? '';
    $tipo_libro         = $_POST['tipo_libro'] ?? '';
    $producto           = $_POST['producto'] ?? '';
    $isbn               = $_POST['isbn'] ?? '';
    $mes_ano_edicion    = $_POST['mes_anio_edicion'] ?? '';
    $nombre_editorial   = $_POST['nombre_editorial'] ?? '';
    $tiraje             = $_POST['tiraje'] ?? '';
    $numero_profesores  = intval($_POST['numero_profesores'] ?? 0);
    $autores            = $_POST['autores'] ?? '';
    
    // 3. Evaluaciones (Soporte para el 3er evaluador opcional)
    $evaluacion_1 = $_POST['evaluacion1'] ?? null;
    $evaluacion_2 = $_POST['evaluacion2'] ?? null;
    $evaluacion_3 = !empty($_POST['evaluacion3']) ? $_POST['evaluacion3'] : null; 
    
    // 4. Cálculos y Metadatos
    $puntaje            = $_POST['puntaje'] ?? '';
    $puntaje_final      = $_POST['puntaje_f'] ?? '';
    $estado             = "ac"; 
    $tipo_productividad = "puntos";

    // 5. Preparar Consulta SQL (18 parámetros)
    $sql = "INSERT INTO libros (
                identificador, numero_oficio, fecha_solicitud, tipo_libro, 
                producto, isbn, mes_ano_edicion, nombre_editorial, 
                tiraje, numero_profesores, autores, evaluacion_1, 
                evaluacion_2, evaluacion_3, calculo, puntaje_final, 
                estado, tipo_productividad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Vinculación de 18 parámetros (s = string, i = integer)
        $stmt->bind_param(
            "ssssssssiiisssssss", 
            $identificador_completo, $numero_oficio, $fecha_solicitud, $tipo_libro,
            $producto, $isbn, $mes_ano_edicion, $nombre_editorial,
            $tiraje, $numero_profesores, $autores, $evaluacion_1,
            $evaluacion_2, $evaluacion_3, $puntaje, $puntaje_final,
            $estado, $tipo_productividad
        );

        if ($stmt->execute()) {
            $id_libro = $conn->insert_id;

            // 6. Inserción de la relación con Profesores
            if ($numero_profesores > 0) {
                $sql_profesor = "INSERT INTO libro_profesor (id_libro, id_profesor) VALUES (?, ?)";
                $stmt_profesor = $conn->prepare($sql_profesor);

                for ($i = 1; $i <= $numero_profesores; $i++) {
                    $cedula = $_POST["cedulaProfesor$i"] ?? '';
                    if (!empty($cedula)) {
                        $stmt_profesor->bind_param("is", $id_libro, $cedula);
                        $stmt_profesor->execute();
                    }
                }
                $stmt_profesor->close();
            }

            // ÉXITO: Redirigir con bandera de éxito
            $conn->close();
            header("Location: index.php?status=success");
            exit();

        } else {
            // ERROR EN EJECUCIÓN: Redirigir con mensaje de error
            $error_msg = urlencode($stmt->error);
            $conn->close();
            header("Location: index.php?status=error&msg=$error_msg");
            exit();
        }
    } else {
        // ERROR EN PREPARACIÓN
        $error_msg = urlencode($conn->error);
        $conn->close();
        header("Location: index.php?status=error&msg=$error_msg");
        exit();
    }
} else {
    // Acceso no autorizado
    header("Location: index.php");
    exit();
}