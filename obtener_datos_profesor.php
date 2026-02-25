<?php
include 'conn.php';

header('Content-Type: application/json'); // Asegúrate de que la cabecera es JSON

if (isset($_GET['documento'])) {
    $documento = $_GET['documento'];

    $query = "SELECT tercero.nombre_completo, deparmanentos.depto_nom_propio as nombre_depto, facultad.nombre_fac_min as nombre_fac, 
                         facultad.trd_fac AS numero_oficio
              FROM tercero
              JOIN deparmanentos ON deparmanentos.PK_DEPTO = tercero.fk_depto
              JOIN facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
              WHERE tercero.documento_tercero = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode($data);
        } else {
            echo json_encode(["error" => "No se encontraron datos para el documento especificado"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["error" => "Error en la consulta SQL"]);
    }
} else {
    echo json_encode(["error" => "Documento no especificado"]);
}
?>