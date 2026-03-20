<?php
// Incluir archivo de conexión a la base de datos
include 'conn.php';

// Verificar que el formulario haya sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Capturar datos del formulario
    $identificador_solicitud = $_POST['identificador_solicitud'];
    $numero_profesores       = $_POST['numero_profesores'];
    $nombre_completo         = $_POST['nombre_completo'];
    $departamento            = $_POST['departamento'];
    $facultad                = $_POST['facultad'];
    $titulo_articulo         = $_POST['titulo_articulo'];
    $issn                    = $_POST['issn'];
    $eissn                   = $_POST['eissn'];
    $nombre_revista          = $_POST['nombre_revista'];
    $ano_publicacion         = $_POST['ano_publicacion'];
    $doi                     = $_POST['doi'];
    
    // Checkboxes (Booleanos)
    $est_scimago = isset($_POST['est_scimago']) ? 1 : 0;
    $est_doaj    = isset($_POST['est_doaj']) ? 1 : 0;
    $est_scopus  = isset($_POST['est_scopus']) ? 1 : 0;
    $est_miar    = isset($_POST['est_miar']) ? 1 : 0;
    $est_core    = isset($_POST['est_core']) ? 1 : 0;
    $mdpi_pred   = isset($_POST['mdpi_pred']) ? 1 : 0;

    $numero_autores     = $_POST['numero_autores'];
    $tipo_articulo      = $_POST['tipo_articulo'];
    $tipo_revista       = $_POST['tipo_revista'];
    $volumen            = $_POST['volumen'];
    $numero_r           = $_POST['numero_r'];
    $tipo_publindex     = $_POST['tipo_publindex'];
    $puntaje            = $_POST['puntaje'];
    $inputTrdFac        = $_POST['inputTrdFac'];
    $fk_id_articulo     = $_POST['fk_id_articulo']; 
    
    $fecha_solicitud    = date("Y-m-d");
    $tipo_productividad = "puntos";
    $vigencia           = date("Y", strtotime($fecha_solicitud));

    // 2. Consulta de inserción principal (29 parámetros)
    $sql = "INSERT INTO solicitud (
                identificador_solicitud, numero_profesores, nombre_completo, departamento, 
                facultad, titulo_articulo, issn, eissn, nombre_revista,
                ano_publicacion, doi, est_scimago, est_doaj, est_scopus, 
                est_miar, est_core, mdpi_pred, numero_autores, tipo_articulo, 
                tipo_revista, volumen, numero_r, tipo_publindex, puntaje, 
                numero_oficio, fecha_solicitud, fk_id_articulo, 
                tipo_productividad, vigencia_sol 
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            "sisssssssssiiiiiiisssssdssisi", 
            $identificador_solicitud, $numero_profesores, $nombre_completo, $departamento, 
            $facultad, $titulo_articulo, $issn, $eissn, $nombre_revista,
            $ano_publicacion, $doi, $est_scimago, $est_doaj, $est_scopus, 
            $est_miar, $est_core, $mdpi_pred, $numero_autores, $tipo_articulo, 
            $tipo_revista, $volumen, $numero_r, $tipo_publindex, $puntaje, 
            $inputTrdFac, $fecha_solicitud, $fk_id_articulo, $tipo_productividad, $vigencia  
        );

        if ($stmt->execute()) {
            $id_solicitud = $conn->insert_id;

            // 3. ACTUALIZAR EL CATÁLOGO DE ARTÍCULOS (Si aplica)
            if (!empty($fk_id_articulo)) {
                $sql_update_articulo = "UPDATE articulo SET mdpi_pred = ? WHERE id_articulo = ?";
                if ($stmt_art = $conn->prepare($sql_update_articulo)) {
                    $stmt_art->bind_param("ii", $mdpi_pred, $fk_id_articulo);
                    $stmt_art->execute();
                    $stmt_art->close();
                }
            }

            // 4. INSERTAR PROFESORES VINCULADOS
            $sql_profesor = "INSERT INTO solicitud_profesor (fk_id_solicitud, fk_id_profesor) VALUES (?, ?)";
            if ($stmt_profesor = $conn->prepare($sql_profesor)) {
                for ($i = 1; $i <= $numero_profesores; $i++) {
                    $fk_id_profesor = $_POST["documento_$i"] ?? null;
                    if ($fk_id_profesor) {
                        $stmt_profesor->bind_param("is", $id_solicitud, $fk_id_profesor);
                        $stmt_profesor->execute();
                    }
                }
                $stmt_profesor->close();
            }
            
            // ÉXITO: Redirigir al index para mostrar SweetAlert2
            $conn->close();
            header("Location: index.php?status=success");
            exit;

        } else {
            // ERROR DE EJECUCIÓN
            $error = urlencode($stmt->error);
            header("Location: index.php?status=error&msg=$error");
            exit;
        }
        $stmt->close();
    } else {
        // ERROR DE PREPARACIÓN
        $error = urlencode($conn->error);
        header("Location: index.php?status=error&msg=$error");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>