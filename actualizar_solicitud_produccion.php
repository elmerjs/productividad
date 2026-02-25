<?php
require 'conn.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_solicitud = $_POST['id_solicitud'] ?? null;

    // Variables del formulario
    $identificador = $_POST['identificador'] ?? null;
    $numero_oficio = $_POST['oficio'] ?? null;
    $productop = $_POST['productop'] ?? null;
    $numero_profesores = $_POST['numero_profesores'] ?? null;
    $puntaje = $_POST['puntaje'] ?? null;
    $profesor_documento = $_POST['profesor_documento'] ?? [];

    $tipo_productividad = "producción"; // Valor fijo

    $conn->begin_transaction();

    try {
        // Actualización de la tabla `produccion_t_s`
        $stmt = $conn->prepare("
            UPDATE produccion_t_s
            SET 
                identificador = ?,  
                numero_oficio = ?,  
                productop = ?, 
                numero_profesores = ?, 
                puntaje = ?, 
                tipo_productividad = ?
            WHERE id_produccion = ?
        ");

        // Asociación de parámetros
        $stmt->bind_param(
            "sssidsi", 
            $identificador, 
            $numero_oficio, 
            $productop, 
            $numero_profesores, 
            $puntaje, 
            $tipo_productividad, 
            $id_solicitud
        );

        $stmt->execute();

        // Eliminar relaciones actuales en `produccionp_profesor`
        $stmt = $conn->prepare("DELETE FROM produccionp_profesor WHERE id_produccion = ?");
        $stmt->bind_param('i', $id_solicitud);
        $stmt->execute();

        // Insertar las nuevas relaciones en `produccionp_profesor`
        $stmt = $conn->prepare("INSERT INTO produccionp_profesor (id_produccion, id_profesor) VALUES (?, ?)");
        foreach ($profesor_documento as $profesor_id) {
            $stmt->bind_param('is', $id_solicitud, $profesor_id);
            $stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();

        // Mostrar mensaje de éxito
        echo "<script>
            alert('Producción actualizada correctamente.');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        // Rollback de la transacción si ocurre un error
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar producción: " . addslashes($e->getMessage()) . "');
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
