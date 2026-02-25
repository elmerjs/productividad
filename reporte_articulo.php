<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('conn.php'); // Conexión a la base de datos

// Obtener el identificador de solicitud desde la URL si está presente
$id_articulo = isset($_GET['id_solicitud_articulo']) ? $conn->real_escape_string($_GET['id_solicitud_articulo']) : null;

// Construcción de la consulta
$sql = "
    SELECT 
        f.nombre_fac_min AS FACULTAD,
        d.depto_nom_propio AS DEPARTAMENTO,
        GROUP_CONCAT(DISTINCT t.documento_tercero ORDER BY t.documento_tercero SEPARATOR '; ') AS CEDULA,
        GROUP_CONCAT(DISTINCT t.nombre_completo ORDER BY t.documento_tercero SEPARATOR '; ') AS NOMBRES,
        GROUP_CONCAT(
            DISTINCT CONCAT(t.nombre_completo, ' c.c ', t.documento_tercero)
            ORDER BY t.documento_tercero
            SEPARATOR '\n'
        ) AS DETALLES_PROFESORES,
        s.id_solicitud_articulo, s.identificador_solicitud,
        s.titulo_articulo AS NOMBRE_DEL_PRODUCTO,
        s.tipo_articulo AS TIPO_DE_ARTICULO,
        s.tipo_revista AS TIPO_REVISTA,
        s.nombre_revista AS NOMBRE_REVISTA,
        s.issn,
        s.eissn,
        s.ano_publicacion,
        s.doi,
        s.volumen,
        s.numero_r,
        s.tipo_publindex,
        s.puntaje,
        s.numero_oficio,
        s.estado_solicitud,
        s.est_scimago,
        s.est_doaj,
        s.est_scopus,
        s.est_miar
    FROM 
        solicitud s
    JOIN 
        solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
    JOIN 
        tercero t ON sp.fk_id_profesor = t.documento_tercero
    JOIN 
        deparmanentos d ON t.fk_depto = d.PK_DEPTO
    JOIN 
        facultad f ON d.FK_FAC = f.PK_FAC
    LEFT JOIN 
        articulo a ON s.fk_id_articulo = a.id_articulo
    WHERE 
        1 = 1
";

// Agregar condición si se proporciona un identificador de solicitud
if (!empty($id_articulo)) {
    $sql .= " AND s.id_solicitud_articulo = '$id_articulo'";
}

// Agrupar resultados por ID de solicitud
$sql .= " GROUP BY s.id_solicitud_articulo";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

if ($result->num_rows == 0) {
    die("No se encontraron datos para el ID de solicitud proporcionado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Artículos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        /* Estilo para quitar la sombra de la fila vacía */
        #tabla_docentes thead tr:nth-child(2) th {
            box-shadow: none;
            border-bottom: 1px solid #dee2e6; /* Agregar un borde inferior */
        }
    </style>
</head>
<body>
    <div>
        <!-- Modal -->
        <div class="modal fade" id="modalReporte" tabindex="-1" aria-labelledby="modalReporteLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalReporteLabel">Reporte de Artículos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="history.back();"></button>
                    </div>
                    <div class="modal-body">
                        <table>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>Identificador:</strong> <?php echo htmlspecialchars($row['identificador_solicitud']); ?> - 
                                        <strong>Número de Oficio:</strong> <?php echo htmlspecialchars($row['numero_oficio']); ?> - 
                                        <strong>Estado:</strong> <?php echo ($row['estado_solicitud']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Facultad:</strong> <?php echo htmlspecialchars($row['FACULTAD']); ?> - 
                                        <strong>Departamento:</strong> <?php echo htmlspecialchars($row['DEPARTAMENTO']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cédulas:</strong> <?php echo htmlspecialchars($row['CEDULA']); ?> - 
                                        <strong>Detalles Profesores:</strong> <pre><?php echo htmlspecialchars($row['DETALLES_PROFESORES']); ?></pre></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nombre del Producto:</strong> <?php echo htmlspecialchars($row['NOMBRE_DEL_PRODUCTO']); ?> - 
                                        <strong>Tipo de Artículo:</strong> <?php echo htmlspecialchars($row['TIPO_DE_ARTICULO']); ?> - 
                                        <strong>Volumen:</strong> <?php echo htmlspecialchars($row['volumen']); ?> - 
                                        <strong>Número Revista:</strong> <?php echo htmlspecialchars($row['numero_r']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipo Revista:</strong> <?php echo htmlspecialchars($row['TIPO_REVISTA']); ?> - 
                                        <strong>Nombre Revista:</strong> <?php echo htmlspecialchars($row['NOMBRE_REVISTA']); ?> - 
                                        <strong>ISSN:</strong> <?php echo htmlspecialchars($row['issn']); ?> - 
                                        <strong>EISSN:</strong> <?php echo ($row['eissn']); ?> - 
                                        <strong>Año de Publicación:</strong> <?php echo ($row['ano_publicacion']); ?> - 
                                        <strong>DOI:</strong> <?php echo ($row['doi']); ?> - 
                                        <strong>Tipo Publindex:</strong> <?php echo ($row['tipo_publindex']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Estado Scimago:</strong> <?php echo ($row['est_scimago']); ?> - 
                                        <strong>Estado DOAJ:</strong> <?php echo ($row['est_doaj']); ?> - 
                                        <strong>Estado Scopus:</strong> <?php echo ($row['est_scopus']); ?> - 
                                        <strong>Estado MIAR:</strong> <?php echo ($row['est_miar']); ?> - 
                                        <strong>Puntaje:</strong> <?php echo ($row['puntaje']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="history.back();">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Código para abrir el modal automáticamente si es llamado desde otro PHP
        var myModal = new bootstrap.Modal(document.getElementById('modalReporte'));
        myModal.show();
    </script>
</body>
</html>
