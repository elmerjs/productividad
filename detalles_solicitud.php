<?php
include_once('conn.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $query = $conn->prepare("
        SELECT s.*, t.nombre_completo, d.depto_nom_propio
        FROM solicitud s
        JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
        JOIN tercero t ON sp.fk_id_profesor = t.documento_tercero
        JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
        WHERE s.id_solicitud_articulo = ?
    ");
    $query->bind_param('i', $id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $details = $result->fetch_assoc();
        echo json_encode(['detalle' => $details]);
    } else {
        echo json_encode(['detalle' => 'No se encontraron detalles.']);
    }
} else {
    echo json_encode(['detalle' => 'ID inválido.']);
}
?>
