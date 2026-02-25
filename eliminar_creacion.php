<?php
require 'conn.php'; // Asegúrate de incluir tu conexión a la base de datos

if (isset($_POST['id'])) {
    $id = $conn->real_escape_string($_POST['id']);

    $sql = "UPDATE creacion SET estado_creacion = 'an' WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Solicitud eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la solicitud.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID no recibido.']);
}

$conn->close();
?>
