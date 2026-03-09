<?php
// Incluir archivo de conexión a la base de datos
include 'conn.php';

// Verificar que el formulario haya sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar datos del formulario usando `$_POST`
    $identificador_solicitud = $_POST['identificador_solicitud'];
    $numero_profesores = $_POST['numero_profesores'];
    $nombre_completo = $_POST['nombre_completo'];
    $departamento = $_POST['departamento'];
    $facultad = $_POST['facultad'];
    $titulo_articulo = $_POST['titulo_articulo'];
    $issn = $_POST['issn'];
    $eissn = $_POST['eissn'];
    $nombre_revista = $_POST['nombre_revista'];
    $ano_publicacion = $_POST['ano_publicacion'];
    $doi = $_POST['doi'];
    
    // Checkboxes (Booleanos)
    $est_scimago = isset($_POST['est_scimago']) ? 1 : 0;
    $est_doaj = isset($_POST['est_doaj']) ? 1 : 0;
    $est_scopus = isset($_POST['est_scopus']) ? 1 : 0;
    $est_miar = isset($_POST['est_miar']) ? 1 : 0;
    $est_core = isset($_POST['est_core']) ? 1 : 0;
    
    // NUEVO CAMPO: MDPI / Predadora
    $mdpi_pred = isset($_POST['mdpi_pred']) ? 1 : 0;

    $numero_autores = $_POST['numero_autores'];
    $tipo_articulo = $_POST['tipo_articulo'];
    $tipo_revista = $_POST['tipo_revista'];
    $volumen = $_POST['volumen'];
    $numero_r = $_POST['numero_r'];
    $tipo_publindex = $_POST['tipo_publindex'];
    $puntaje = $_POST['puntaje'];
    $inputTrdFac = $_POST['inputTrdFac'];
    $fk_id_articulo = $_POST['fk_id_articulo']; // Campo conservado
    
    $fecha_solicitud = date("Y-m-d");
    $tipo_productividad = "puntos";
    $vigencia = date("Y", strtotime($fecha_solicitud)); // Extraer solo el año

    // Crear la consulta de inserción (Agregando mdpi_pred)
    $sql = "INSERT INTO solicitud (
                identificador_solicitud, 
                numero_profesores, 
                nombre_completo, 
                departamento, 
                facultad, 
                titulo_articulo, 
                issn, 
                eissn, 
                nombre_revista,
                ano_publicacion, 
                doi, 
                est_scimago, 
                est_doaj, 
                est_scopus, 
                est_miar,  
                est_core, 
                mdpi_pred, 
                numero_autores, 
                tipo_articulo, 
                tipo_revista, 
                volumen, 
                numero_r, 
                tipo_publindex, 
                puntaje, 
                numero_oficio, 
                fecha_solicitud,
                fk_id_articulo, 
                tipo_productividad, 
                vigencia_sol 
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Preparar la consulta para evitar inyecciones SQL
    if ($stmt = $conn->prepare($sql)) {
        // Vincular parámetros (29 en total. Ajustados exactamente a los tipos de datos)
        $stmt->bind_param(
            "sisssssssssiiiiiiisssssdssisi", 
            $identificador_solicitud, 
            $numero_profesores, 
            $nombre_completo, 
            $departamento, 
            $facultad, 
            $titulo_articulo, 
            $issn, 
            $eissn,
            $nombre_revista,
            $ano_publicacion, 
            $doi, 
            $est_scimago, 
            $est_doaj, 
            $est_scopus, 
            $est_miar, 
            $est_core, 
            $mdpi_pred, 
            $numero_autores, 
            $tipo_articulo, 
            $tipo_revista, 
            $volumen, 
            $numero_r, 
            $tipo_publindex, 
            $puntaje, 
            $inputTrdFac,
            $fecha_solicitud,
            $fk_id_articulo, 
            $tipo_productividad,
            $vigencia  
        );

        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Obtener el último ID insertado para la solicitud
            $id_solicitud = $conn->insert_id;

            // --- NUEVO: ACTUALIZAR EL CATÁLOGO DE ARTÍCULOS ---
            // Si la solicitud está vinculada a un artículo existente en tu catálogo,
            // actualizamos su estado para que el sistema "recuerde" que es predadora en futuras búsquedas.
            if (!empty($fk_id_articulo)) {
                $sql_update_articulo = "UPDATE articulo SET mdpi_pred = ? WHERE id_articulo = ?";
                if ($stmt_art = $conn->prepare($sql_update_articulo)) {
                    $stmt_art->bind_param("ii", $mdpi_pred, $fk_id_articulo);
                    $stmt_art->execute();
                    $stmt_art->close();
                }
            }
            // --------------------------------------------------

            // Insertar datos en la tabla `solicitud_profesor`
            for ($i = 1; $i <= $numero_profesores; $i++) {
                $fk_id_profesor = $_POST["documento_$i"];
                
                // Crear la consulta de inserción para solicitud_profesor
                $sql_profesor = "INSERT INTO solicitud_profesor (fk_id_solicitud, fk_id_profesor) VALUES (?, ?)";
                
                if ($stmt_profesor = $conn->prepare($sql_profesor)) {
                    // Vincular parámetros para solicitud_profesor
                    $stmt_profesor->bind_param("is", $id_solicitud, $fk_id_profesor);
                    
                    // Ejecutar la consulta
                    if (!$stmt_profesor->execute()) {
                        echo "<script>
                            alert('Error al insertar profesor $i: " . addslashes($stmt_profesor->error) . "');
                            window.history.back();
                        </script>";
                        exit;
                    }
                    // Cerrar la declaración del profesor
                    $stmt_profesor->close();
                } else {
                    echo "<script>
                        alert('Error preparando la consulta para profesor $i: " . addslashes($conn->error) . "');
                        window.history.back();
                    </script>";
                    exit;
                }
            }
            
            echo "<script>
                alert('Solicitud guardada exitosamente.');
                window.history.back();
            </script>";
        } else {
            echo "<script>
                alert('Error: " . addslashes($stmt->error) . "');
                window.history.back();
            </script>";
        }
        // Cerrar la declaración principal
        $stmt->close();
    } else {
        echo "<script>
            alert('Error preparando la consulta: " . addslashes($conn->error) . "');
            window.history.back();
        </script>";
    }

    // Cerrar la conexión a la base de datos
    $conn->close();
} else {
    echo "<script>
        alert('Método de solicitud no permitido.');
       window.history.back();
    </script>";
}
?>