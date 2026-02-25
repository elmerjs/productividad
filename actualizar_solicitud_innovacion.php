<?php
require 'conn.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_solicitud = $_POST['id_solicitud'] ?? null;

    // Variables del formulario
    $identificador = $_POST['identificador'] ?? null;
    $numero_oficio = $_POST['oficio'] ?? null;
    $producto = $_POST['producto'] ?? null;
    $impacto = $_POST['impacto'] ?? null;
    $numero_profesores = $_POST['numero_profesores'] ?? null;
    
    $puntaje = $_POST['puntaje'] ?? null;
    $tipo_productividad = "puntos"; // Valor fijo
   $profesor_documento = $_POST['profesor_documento'];


    $conn->begin_transaction();

    try {
        

        // Actualización de la tabla `trabajos_cientificos`
       $stmt = $conn->prepare("
   UPDATE innovacion
SET 
    identificador = ?,  
    numero_oficio = ?,  
   impacto = ?, 
    producto = ?, 
   
    numero_profesores = ?, 
    puntaje = ?, 
    tipo_productividad = ?
WHERE id_innovacion = ?
");
        
// Asociación de parámetros
$stmt->bind_param(
     "ssssidsi", 
    $identificador, 
    $numero_oficio, 
    $impacto, 
    $producto, 
    $numero_profesores, 
    $puntaje, 
    $tipo_productividad, 
    $id_solicitud
);

        $stmt->execute();

        // Eliminar relaciones actuales en `trabajo_profesor`
        $stmt = $conn->prepare("DELETE FROM innovacion_profesor WHERE id_innovacion = ?");
        $stmt->bind_param('i', $id_solicitud);
        $stmt->execute();
        
        // Insertar las nuevas relaciones en `trabajo_profesor`
        $stmt = $conn->prepare("INSERT INTO innovacion_profesor (id_innovacion, id_profesor) VALUES (?, ?)");
        foreach ($profesor_documento as $profesor_id) {
            $stmt->bind_param('is', $id_solicitud, $profesor_id);
            $stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();

        // Mostrar m    ensaje de éxito
        echo "<script>
            alert('innovacion  actualizada correctamente.$numero_oficio.$id_solicitud');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        // Rollback de la transacción si ocurre un error
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar obra: " . addslashes($e->getMessage()) . "');
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
