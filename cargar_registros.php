<?php
include 'conn.php';

if (isset($_POST['tabla'], $_POST['campo'], $_POST['identificador'])) {
    $tabla = $_POST['tabla'];
    $campo = $_POST['campo'];
    $identificador = $_POST['identificador'];

    $query = "SELECT * FROM $tabla WHERE $campo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $identificador);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table class='tabla-datos'>";
        echo "<thead><tr>";

        // Columns to display for 'solicitud' table
        $columnasSolicitud = [
            "numero_oficio", "titulo_articulo", "numero_autores",
            "tipo_articulo", "nombre_revista", "tipo_revista",
            "tipo_publindex", "puntaje", "estado_solicitud"
        ];
        
        // Columns to display for 'libros' table
        $columnasLibros = [
            "numero_oficio", "tipo_libro", "producto",
            "nombre_editorial", "tiraje", "autores",
            "puntaje_final", "estado"
        ];

        // Columns to display for 'creaciones' table
        $columnasCreaciones = [
            "numeroOficio", "tipo_producto", "impacto", "producto",
            "nombre_evento", "evento", "fecha_evento", "fecha_evento_f",
            "lugar_evento", "autores", "puntaje_final", "estado_creacion"
        ];
        
        // Define which column list to use based on the table
        $columnasMostrar = [];
        if ($tabla === "solicitud") {
            $columnasMostrar = $columnasSolicitud;
        } elseif ($tabla === "libros") {
            $columnasMostrar = $columnasLibros;
        } elseif ($tabla === "creaciones") {
            $columnasMostrar = $columnasCreaciones;
        }

        // get headers
        $fields = $result->fetch_fields();
        $isFirstColumn = true;
        foreach ($fields as $field) {
            $columna = $field->name;

            // Exclusion rules
            if ($columna === "identificador" || $columna === "identificador_completo" || $columna === "tipo_productividad") {
                continue;
            }
            if (strpos($columna, "id_") === 0 || $columna === "id") {
                continue;
            }
            if (!empty($columnasMostrar) && !in_array($columna, $columnasMostrar)) {
                continue;
            }

            // Apply a wider class to the first column
            $thClass = $isFirstColumn ? " class='primera-columna-ancha'" : "";
            echo "<th" . $thClass . ">" . htmlspecialchars($columna) . "</th>";
            $isFirstColumn = false;
        }
        echo "</tr></thead><tbody>";

        // Reset cursor to read rows
        $result->data_seek(0);

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            $isFirstColumn = true;
            foreach ($row as $columna => $valor) {
                if ($columna === "identificador" || $columna === "identificador_completo" || $columna === "tipo_productividad") {
                    continue;
                }
                if (strpos($columna, "id_") === 0 || $columna === "id") {
                    continue;
                }
                if (!empty($columnasMostrar) && !in_array($columna, $columnasMostrar)) {
                    continue;
                }

                // Apply a wider class to the first cell
                $tdClass = $isFirstColumn ? " class='primera-columna-ancha'" : "";
                echo "<td" . $tdClass . ">" . htmlspecialchars($valor) . "</td>";
                $isFirstColumn = false;
            }
            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p>No se encontraron registros.</p>";
    }
}
?>

<style>
.primera-columna-ancha {
    min-width: 150px !important;
    white-space: nowrap !important;
}
</style>