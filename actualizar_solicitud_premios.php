<?php
require 'conn.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_premio = $_POST['id_solicitud']; // ID del trabajo científico

    // Variables del formulario
     $identificador = $_POST['identificador'] ?? null;
    $numero_oficio = $_POST['oficio'] ?? null;
    $nombre_evento = $_POST['nombre_evento'] ?? null;
    $ambito = $_POST['ambito'] ?? null;
    $categoria = $_POST['categoria'] ?? null;
    $nivel_ganado = $_POST['nivel_ganado'] ?? null;
    $lugar_fecha = $_POST['lugar_fecha'] ?? null;
    $numero_profesores= $_POST['numero_profesores'] ?? null;
    $autores = $_POST['autores'] ?? null;
  
    $puntaje = $_POST['puntaje'] ?? null;
$tipo_productividad = "puntos"; // Valor fijo
   $profesor_documento = $_POST['profesor_documento'];

    $conn->begin_transaction();

    try {
        

        // Actualización de la tabla `trabajos_cientificos`
       $stmt = $conn->prepare("
    UPDATE premios
    SET 
        identificador = ?, 
        numero_oficio = ?, 
        nombre_evento = ?, 
        ambito = ?, 
        categoria_premio = ?, 
        nivel_ganado = ?, 
        lugar_fecha = ?, 
        numero_profesores = ?, 
        autores = ?, 
        puntos = ?, 
     
        tipo_productividad = ?
    WHERE id = ?
");
        
// Asociación de parámetros
$stmt->bind_param(
    'sssssssiidsi', 
    $identificador, 
    $numero_oficio, 
    $nombre_evento, 
    $ambito, 
    $categoria, 
    $nivel_ganado, 
    $lugar_fecha, 
    $numero_profesores, 
    $autores, 
    $puntaje, 
    $tipo_productividad, 
    $id_premio
);

        $stmt->execute();

        // Eliminar relaciones actuales en `trabajo_profesor`
        $stmt = $conn->prepare("DELETE FROM premios_profesor WHERE id_premio = ?");
        $stmt->bind_param('i', $id_premio);
        $stmt->execute();
        
        // Insertar las nuevas relaciones en `trabajo_profesor`
        $stmt = $conn->prepare("INSERT INTO premios_profesor (id_premio, id_profesor) VALUES (?, ?)");
        foreach ($profesor_documento as $profesor_id) {
            $stmt->bind_param('is', $id_premio, $profesor_id);
            $stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();

        // Mostrar mensaje de éxito
        echo "<script>
            alert('premio actualizado correctamente.$numero_oficio.$id_premio');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        // Rollback de la transacción si ocurre un error
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar el premio: " . addslashes($e->getMessage()) . "');
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
