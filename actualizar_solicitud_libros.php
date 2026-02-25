<?php
require 'conn.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_libro = $_POST['id_solicitud']; // ID del trabajo científico

    // Variables del formulario
     $identificador = $_POST['identificador'] ?? null;
    $numero_oficio = $_POST['oficio'] ?? null;
    $producto = $_POST['producto'] ?? null;
    $tipo = $_POST['tipo'] ?? null;
    $isbn = $_POST['isbn'] ?? null;
    $mes_ano = $_POST['mes_ano'] ?? null;
    $editorial = $_POST['editorial'] ?? null;
    $tiraje = $_POST['tiraje'] ?? null;
    $evaluador1 = $_POST['evaluador1'] ?? null;
    $evaluador2 = $_POST['evaluador2'] ?? null;
    $puntaje = $_POST['puntaje'] ?? null;
$tipo_productividad = "puntos"; // Valor fijo
   $profesor_documento = $_POST['profesor_documento'];

    $conn->begin_transaction();

    try {
        

        // Actualización de la tabla `trabajos_cientificos`
       $stmt = $conn->prepare("
    UPDATE libros
    SET 
        identificador = ?, 
        numero_oficio = ?, 
        producto = ?, 
        tipo_libro = ?, 
        isbn = ?, 
        mes_ano_edicion = ?, 
        nombre_editorial = ?, 
        tiraje = ?, 
        evaluacion_1 = ?, 
        evaluacion_2 = ?, 
        puntaje_final = ?, 
        tipo_productividad = ?
    WHERE id_libro = ?
");
        
// Asociación de parámetros
$stmt->bind_param(
    'sssssssdddssi', 
    $identificador, 
    $numero_oficio, 
    $producto, 
    $tipo, 
    $isbn, 
    $mes_ano, 
    $editorial, 
    $tiraje, 
    $evaluador1, 
    $evaluador2, 
    $puntaje, 
    $tipo_productividad, 
    $id_libro
);

        $stmt->execute();

        // Eliminar relaciones actuales en `trabajo_profesor`
        $stmt = $conn->prepare("DELETE FROM libro_profesor WHERE id_libro = ?");
        $stmt->bind_param('i', $id_libro);
        $stmt->execute();
        
        // Insertar las nuevas relaciones en `trabajo_profesor`
        $stmt = $conn->prepare("INSERT INTO libro_profesor (id_libro, id_profesor) VALUES (?, ?)");
        foreach ($profesor_documento as $profesor_id) {
            $stmt->bind_param('is', $id_libro, $profesor_id);
            $stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();

        // Mostrar mensaje de éxito
        echo "<script>
            alert('libro actualizado correctamente.$numero_oficio.$id_libro');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        // Rollback de la transacción si ocurre un error
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar el libro: " . addslashes($e->getMessage()) . "');
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
