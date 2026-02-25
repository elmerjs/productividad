<?php
require 'conn.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_trabajo_fk = $_POST['id_solicitud']; // ID del trabajo científico

    // Variables del formulario
    $identificador = $_POST['identificador'];
    $numero_oficio = $_POST['oficio'];
    $producto = $_POST['producto'];
    $difusion = $_POST['difusion'];
    $finalidad = $_POST['finalidad'];
    $area = $_POST['area'];
    $evaluador1 = $_POST['evaluador1'];
    $evaluador2 = $_POST['evaluador2'];
    $puntaje = $_POST['puntaje'];
    $tipo_productividad = $_POST['tipo_productividad'];
    $profesor_documento = $_POST['profesor_documento'];

    $conn->begin_transaction();

    try {
        

        // Actualización de la tabla `trabajos_cientificos`
        $stmt = $conn->prepare("
            UPDATE trabajos_cientificos
            SET 
                identificador = ?, 
                numero_oficio = ?, 
                producto = ?, 
                difusion = ?, 
                finalidad = ?, 
                area = ?, 
                evaluador1 = ?, 
                evaluador2 = ?, 
                puntaje = ?, 
                tipo_productividad = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            'sssssssddds',
            $identificador, $numero_oficio, $producto, $difusion, $finalidad, $area, 
            $evaluador1, $evaluador2, $puntaje, $tipo_productividad, $id_trabajo_fk
        );
        $stmt->execute();

        // Eliminar relaciones actuales en `trabajo_profesor`
        $stmt = $conn->prepare("DELETE FROM trabajo_profesor WHERE id_trabajo_cientifico = ?");
        $stmt->bind_param('i', $id_trabajo_fk);
        $stmt->execute();
        
        // Insertar las nuevas relaciones en `trabajo_profesor`
        $stmt = $conn->prepare("INSERT INTO trabajo_profesor (id_trabajo_cientifico, profesor_id) VALUES (?, ?)");
        foreach ($profesor_documento as $profesor_id) {
            $stmt->bind_param('is', $id_trabajo_fk, $profesor_id);
            $stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();

        // Mostrar mensaje de éxito
        echo "<script>
            alert('Trabajo científico actualizado correctamente.$numero_oficio.$id_trabajo_fk');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        // Rollback de la transacción si ocurre un error
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar el trabajo científico: " . addslashes($e->getMessage()) . "');
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
