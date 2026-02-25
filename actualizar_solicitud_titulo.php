<?php
require 'conn.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_titulo = $_POST['id_solicitud'] ?? null;

    // Variables del formulario
    $identificador = $_POST['identificador'] ?? null;
    $numero_oficio = $_POST['oficio'] ?? null;
    $titulo_obtenido = $_POST['titulo_obtenido'] ?? null;
    $tipo = $_POST['tipo'] ?? null;
    $tipo_estudio = $_POST['tipo_estudio'] ?? null;
    $institucion = $_POST['institucion'] ?? null;
    $fecha_terminacion = $_POST['fecha_terminacion'] ?? null;
    $resolucion_convalidacion = $_POST['convalidacion'] ?? null;
    $puntaje = $_POST['puntaje'] ?? null;
    $tipo_productividad = "puntos"; // Valor fijo
    $profesores_documento = $_POST['profesor_documento'] ?? []; // Lista de profesores

    $conn->begin_transaction();

    try {
        // Actualización de la tabla `titulos`
        $stmt = $conn->prepare("
            UPDATE titulos
            SET 
                identificador = ?,  
                numero_oficio = ?,  
                titulo_obtenido = ?, 
                tipo = ?, 
                tipo_estudio = ?, 
                institucion = ?, 
                fecha_terminacion = ?, 
                resolucion_convalidacion = ?, 
                puntaje = ?, 
                tipo_productividad = ?
            WHERE id_titulo = ?
        ");
        
        // Asociación de parámetros
        $stmt->bind_param(
            "ssssssssdsd", 
            $identificador, 
            $numero_oficio, 
            $titulo_obtenido, 
            $tipo, 
            $tipo_estudio, 
            $institucion, 
            $fecha_terminacion, 
            $resolucion_convalidacion, 
            $puntaje, 
            $tipo_productividad, 
            $id_titulo
        );

        $stmt->execute();

        // Eliminar relaciones actuales en `titulo_profesor`
        $stmt = $conn->prepare("DELETE FROM titulo_profesor WHERE id_titulo = ?");
        $stmt->bind_param('i', $id_titulo);
        $stmt->execute();
        
        // Insertar las nuevas relaciones en `titulo_profesor`
        $stmt = $conn->prepare("INSERT INTO titulo_profesor (id_titulo, fk_tercero) VALUES (?, ?)");
        foreach ($profesores_documento as $profesor_id) {
            $stmt->bind_param('is', $id_titulo, $profesor_id);
            $stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();

        // Mostrar mensaje de éxito
        echo "<script>
            alert('Título actualizado correctamente.');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        // Rollback de la transacción si ocurre un error
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar título: " . addslashes($e->getMessage()) . "');
            window.history.go(-2);
        </script>";
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo "<script>
        alert('Método no permitido.');
        window.history.back();
    </script>";
}
?>
