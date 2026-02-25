<?php
include 'conn.php';

if (isset($_POST['tabla'], $_POST['campo'], $_POST['identificador'])) {
    $tabla = $_POST['tabla'];
    $campo = $_POST['campo'];
    $identificador = $_POST['identificador'];

    $query = "SELECT * FROM $tabla WHERE $campo = ? LIMIT 10"; // Opcional: Limitar resultados
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $identificador);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table class='tabla-datos'>";
        echo "<tr>";

        // Obtener todas las columnas
        $fields = $result->fetch_fields();

        foreach ($fields as $field) {
            echo "<th class='columna-pequena'>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";

        // Mostrar todas las columnas en tamaño pequeño
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td class='columna-pequena'>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay registros en esta tabla.</p>";
    }
}
?>
