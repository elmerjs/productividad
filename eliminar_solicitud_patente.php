<?php
require 'conn.php';

if (isset($_GET['id_solicitud']) && isset($_GET['motivo'])) {
    $id_solicitud = $_GET['id_solicitud'];
    $motivo = $_GET['motivo'];

    try {
        // Actualizar estado_solicitud y obs_solicitud con el motivo
        $stmt = $conn->prepare("UPDATE patentes SET estado = 'an', obs_patente = ? WHERE id_patente = ?");
        $stmt->bind_param('si', $motivo, $id_solicitud);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>
                alert('Solicitud anulada correctamente con motivo: " . htmlspecialchars($motivo) . "');
                window.history.go(-1);
                setTimeout(() => location.reload(), 500);
            </script>";
        } else {
            echo "<script>
                alert('No se encontró la solicitud o no se pudo anular.$id_solicitud.movito: .$motivo');
                window.history.go(-1);
                setTimeout(() => location.reload(), 500);
            </script>";
        }

        $stmt->close();
    } catch (Exception $e) {
        echo "<script>
            alert('Error al anular la solicitud: " . addslashes($e->getMessage()) . "');
            window.history.go(-1);
            setTimeout(() => location.reload(), 500);
        </script>";
    } finally {
        $conn->close();
    }
} else {
    echo "<script>
        alert('ID de solicitud o motivo no proporcionado.');
        window.history.go(-1);
        setTimeout(() => location.reload(), 500);
    </script>";
}
?>
