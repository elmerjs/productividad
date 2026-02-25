<?php
require 'conn.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'conn.php';
    $id_solicitud_fk = $_POST['id_solicitud'];

    // Variables del formulario
    $identificador = $_POST['identificador'];
    $numero_oficio = $_POST['numero_oficio'];
    $titulo_articulo = $_POST['titulo_articulo'];
    $volumen = $_POST['volumen'];
    $numero_r = $_POST['numero_r'];
    $ano_publicacion = $_POST['ano_publicacion'];
    $numero_autores = $_POST['numero_autores'];
    $tipo_articulo = $_POST['tipo_articulo'];
    $doi = $_POST['doi'];
    $nombre_revista = $_POST['nombre_revista'];
    $issn = $_POST['issn'];
    $eissn = $_POST['eissn'];
    $tipo_publindex = $_POST['tipo_publindex'];
    $tipo_revista = $_POST['tipo_revista'];
    $puntaje = $_POST['puntaje'];
    $est_scimago = isset($_POST['est_scimago']) ? 1 : 0;
    $est_doaj = isset($_POST['est_doaj']) ? 1 : 0;
    $est_scopus = isset($_POST['est_scopus']) ? 1 : 0;
    $est_miar = isset($_POST['est_miar']) ? 1 : 0;
    $profesor_documento = $_POST['profesor_documento'];

    $conn->begin_transaction();

    try {
        // Actualización de la tabla `solicitud`
        $stmt = $conn->prepare("
            UPDATE solicitud
            SET numero_oficio = ?, titulo_articulo = ?, volumen = ?, numero_r = ?, 
                ano_publicacion = ?, numero_autores = ?, tipo_articulo = ?, doi = ?, 
                nombre_revista = ?, issn = ?, eissn = ?, tipo_publindex = ?, 
                tipo_revista = ?, puntaje = ?, est_scimago = ?, est_doaj = ?, 
                est_scopus = ?, est_miar = ?
            WHERE id_solicitud_articulo = ?
        ");
        $stmt->bind_param(
            'ssssiisssssssdddiss',
            $numero_oficio, $titulo_articulo, $volumen, $numero_r,
            $ano_publicacion, $numero_autores, $tipo_articulo, $doi,
            $nombre_revista, $issn, $eissn, $tipo_publindex,
            $tipo_revista, $puntaje, $est_scimago, $est_doaj,
            $est_scopus, $est_miar, $id_solicitud_fk
        );
        $stmt->execute();

        $id_solicitud = $id_solicitud_fk;

        // Eliminar relaciones actuales y añadir las nuevas
        $stmt = $conn->prepare("DELETE FROM solicitud_profesor WHERE fk_id_solicitud = ?");
        $stmt->bind_param('i', $id_solicitud);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO solicitud_profesor (fk_id_solicitud, fk_id_profesor) VALUES (?, ?)");
        foreach ($profesor_documento as $profesor_id) {
            $stmt->bind_param('is', $id_solicitud, $profesor_id);
            $stmt->execute();
        }

        $conn->commit();

        // Mostrar mensaje de éxito
        echo "<script>
            alert('Solicitud actualizada correctamente.');
            window.history.go(-2);
        </script>";
    } catch (Exception $e) {
        $conn->rollback();

        // Mostrar mensaje de error
        echo "<script>
            alert('Error al actualizar la solicitud: " . addslashes($e->getMessage()) . "');
            window.history.go(-2);
        </script>";
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo "<script>
        alert('Método no permitido.');
        window.history.back();
    </script>";
}
