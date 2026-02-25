<?php
require 'conn.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_traduccion = $_POST['id_solicitud']; // ID del trabajo científico

    // Variables del formulario
     $identificador = $_POST['identificador'] ?? null;
    $numero_oficio = $_POST['oficio'] ?? null;
    $producto = $_POST['producto'] ?? null;
   
    $numero_profesores= $_POST['numero_profesores'] ?? null;
      $tipo_traduccion= $_POST['tipo_traduccion'] ?? null;

    $puntaje = $_POST['puntaje'] ?? null;
$tipo_productividad = "puntos"; // Valor fijo
   $profesor_documento = $_POST['profesor_documento'];

    $conn->begin_transaction();

    try {
        

        // Actualización de la tabla `trabajos_cientificos`
       $stmt = $conn->prepare("
    UPDATE traduccion_libros
    SET 
        identificador = ?, 
        numero_oficio = ?, 
        producto = ?, 
       numero_profesores = ?, 
        tipo_traduccion = ?, 
        puntaje = ?, 
        tipo_productividad = ?
    WHERE id_traduccion = ?
");
        
// Asociación de parámetros
$stmt->bind_param(
    'sssisdsi', 
    $identificador, 
    $numero_oficio, 
    $producto, 
      $numero_profesores, 
    $tipo_traduccion, 
    $puntaje, 
    $tipo_productividad, 
    $id_traduccion
);

        $stmt->execute();

        // Eliminar relaciones actuales en `trabajo_profesor`
        $stmt = $conn->prepare("DELETE FROM traduccion_profesor WHERE id_traduccion = ?");
        $stmt->bind_param('i', $id_traduccion);
        $stmt->execute();
        
        // Insertar las nuevas relaciones en `trabajo_profesor`
        $stmt = $conn->prepare("INSERT INTO traduccion_profesor (id_traduccion, id_profesor) VALUES (?, ?)");
        foreach ($profesor_documento as $profesor_id) {
            $stmt->bind_param('is', $id_traduccion, $profesor_id);
            $stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();

        // Mostrar m    ensaje de éxito
        echo "<script>
            alert('traduccion  actualizado correctamente.$numero_oficio.$id_traduccion');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        // Rollback de la transacción si ocurre un error
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar traduccion: " . addslashes($e->getMessage()) . "');
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
