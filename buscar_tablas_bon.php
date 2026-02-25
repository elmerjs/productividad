<?php
include 'conn.php';

if (isset($_POST['identificador'])) {
    $identificador = $_POST['identificador'];
 $tablas = [
    "creacion_bon" => "identificador_completo",
    "direccion_tesis" => "identificador",
    "ponencias_bon" => "identificador_completo",
    "posdoctoral" => "identificador",
    "publicacion_bon" => "identificador_completo",
    "resena_bon" => "identificador_completo",
    "trabajos_cientificos_bon" => "identificador",
    "traduccion_bon" => "identificador"
];

    $tablasEncontradas = [];

    foreach ($tablas as $tabla => $campo) {
        $query = "SELECT COUNT(*) as total FROM $tabla WHERE $campo = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $identificador);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['total'] > 0) {
            $tablasEncontradas[$tabla] = $campo;
        }
    }

    if (count($tablasEncontradas) > 0) {
    echo "<h3>El identificador <strong>$identificador</strong> se encuentra en:</h3>";
    echo "<ul class='tabla-lista'>";
    foreach ($tablasEncontradas as $tabla => $campo) {
        echo "<li>
                <a class='tabla-link' onclick=\"cargarRegistros('$tabla', '$campo', '$identificador')\"> 
                    ▶ $tabla
                </a>
                <div id='registros-$tabla' class='tabla-registros'></div>
              </li>";
    }
    echo "</ul>";
} else {
        echo "<p>No se encontraron registros para este identificador.</p>";
    }
}
?>
