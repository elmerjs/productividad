<?php
/**
 * eliminar_solicitud.php
 * Realiza un borrado lógico actualizando el estado a 'an' (anulado).
 */

include_once('conn.php');

if (isset($_GET['id_solicitud']) && !empty($_GET['id_solicitud'])) {
    
    $id_solicitud = intval($_GET['id_solicitud']); //
    $motivo = isset($_GET['motivo']) ? trim($_GET['motivo']) : 'No especificado'; //

    // Consulta para actualizar el estado y la observación en lugar de borrar
    $sql = "UPDATE solicitud 
            SET estado_solicitud = 'an', 
                obs_solicitud = ? 
            WHERE id_solicitud_articulo = ?"; //

    if ($stmt = $conn->prepare($sql)) {
        // "si" indica que el primer parámetro es string (motivo) y el segundo entero (id)
        $stmt->bind_param("si", $motivo, $id_solicitud);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            // Redirigir con un nuevo estado para SweetAlert2
            header("Location: index.php?status=success_anular");
            exit();
        } else {
            $error = urlencode($stmt->error);
            header("Location: index.php?status=error&msg=$error");
            exit();
        }
    } else {
        $error = urlencode($conn->error);
        header("Location: index.php?status=error&msg=$error");
        exit();
    }

} else {
    header("Location: index.php");
    exit();
}
?>