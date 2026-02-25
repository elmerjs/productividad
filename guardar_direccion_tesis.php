<?php
// Incluir el archivo de conexión
include 'conn.php';

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recoger los datos del formulario
    $identificador_base = $_POST['identificador_base'];
    $numero_envio = $_POST['numero_envio'];

    // Unir identificador_base y numero_envio con un "_"
    $identificador = $identificador_base . '_' . $numero_envio;

    // Recoger los demás datos del formulario
    $inputTrdFac = $_POST['inputTrdFac'];
    $documento_profesor = $_POST['documento_profesor'];
    $producto = $_POST['producto'];
    $tipo_direccion = $_POST['tipo_direccion'];
    $nombre_estudiante = $_POST['nombre_estudiante'];
    $fecha_sustentacion = $_POST['fecha_sustentacion'];
    $fecha_terminacion = $_POST['fecha_terminacion'];
    $resolucion = $_POST['resolucion'];
    $puntaje = $_POST['puntaje'];
    // Valor constante para tipo_productividad
    $tipo_productividad = "bonificacion";

    // Preparar y ejecutar la consulta de inserción para la tabla titulos
   $sql = "INSERT INTO direccion_tesis (identificador, numero_oficio, documento_profesor, titulo_obtenido, tipo, nombre_estudiante, fecha_sustentacion, fecha_terminacion, resolucion, puntaje, tipo_productividad)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

// Asegúrate de que el tipo de cada parámetro coincida con el tipo de datos en la tabla
$stmt->bind_param("sssssssssds", $identificador, $inputTrdFac, $documento_profesor, $producto, $tipo_direccion, $nombre_estudiante, $fecha_sustentacion, $fecha_terminacion, $resolucion, $puntaje, $tipo_productividad);

if ($stmt->execute()) {
    // Obtener el id del último registro insertado
    $id_titulo = $conn->insert_id;

    // Preparar y ejecutar la consulta para titulo_profesor
    $sql_titulo_profesor = "INSERT INTO direccion_t_profesor (id_titulo, fk_tercero) VALUES (?, ?)";
    $stmt_titulo_profesor = $conn->prepare($sql_titulo_profesor);
    $stmt_titulo_profesor->bind_param("is", $id_titulo, $documento_profesor); // Asegúrate de que el tipo coincida

    if ($stmt_titulo_profesor->execute()) {
        echo "Título y relación con el profesor guardados con éxito.";
    } else {
        echo "Error al guardar en titulo_profesor: " . $stmt_titulo_profesor->error;
    }

    // Cerrar la segunda consulta
    $stmt_titulo_profesor->close();
} else {
    echo "Error al guardar el título: " . $stmt->error;
}

// Cerrar la conexión
$stmt->close();
$conn->close();
}
?>
