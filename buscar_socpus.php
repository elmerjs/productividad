<?php
// Configuración de la API de Scopus
$issn = "20397283";
$nombre_articulo = "A Comparison of Severely Injured Patients after Suicide Attempts and Violent Crimes—A Retrospective Study of a Level 1 Trauma Center";
$apiKey = '803bbe28a496ac467be562f4f18d3d91';
$titulo = $nombre_articulo; // Utilizar el título del formulario

// Función para limpiar y codificar el título
function limpiarTitulo($titulo) {
    $titulo = str_replace([':', '(', ')', '–', ','], ' ', $titulo); // Reemplaza caracteres por espacios
    $titulo = preg_replace('/\s+/', ' ', $titulo); // Reemplaza múltiples espacios por uno solo
    $titulo = trim($titulo); // Eliminar espacios en blanco al inicio y al final
    return urlencode($titulo); // Codificar el título
}

// Función para buscar en Scopus
function buscarEnScopus($query) {
    global $apiKey;
    $urlScopus = 'https://api.elsevier.com/content/search/scopus';
    $queryParams = [
        'query' => $query,
        'apiKey' => $apiKey,
        'httpAccept' => 'application/json'
    ];
    // Crear la URL completa con los parámetros
    $url = $urlScopus . '?' . http_build_query($queryParams);
    // Iniciar la solicitud HTTP utilizando cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.unicauca.edu.co:3128'); // Descomentar si necesitas usar el proxy
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    // Verificar si ocurrió algún error
    if (curl_errno($ch)) {
        echo 'Error al realizar la solicitud a Scopus: ' . curl_error($ch);
        return null; // Retornar null en caso de error
    }
    // Cerrar cURL
    curl_close($ch);
    // Decodificar la respuesta JSON
    return json_decode($response, true);
}

// Limpiar y codificar el título y el ISSN
$titulo = limpiarTitulo($titulo);
$issn = urlencode($issn); // Asegúrate de que el ISSN también esté correctamente codificado

// Intentar búsqueda con ISSN y título
$queryConIssn = 'title(' . $titulo . ') AND issn(' . $issn . ')';
$data = buscarEnScopus($queryConIssn);

// Función para mostrar resultados
function mostrarResultados($data) {
    if (isset($data['search-results']['entry']) && !empty($data['search-results']['entry'])) {
        echo "<h2>Resultados de la búsqueda en Scopus:</h2>";
        echo "<table border='1'>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>URL del Autor</th>
                        <th>Author ID</th>
                        <th>ORCID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Iniciales</th>
                        <th>Afiliación</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($data['search-results']['entry'] as $entry) {
            if (isset($entry['author']) && is_array($entry['author'])) {
                foreach ($entry['author'] as $author) {
                    echo "<tr>";
                    echo "<td>" . (isset($author['authname']) ? htmlspecialchars($author['authname']) : 'N/A') . "</td>";
                    echo "<td>" . (isset($author['author-url']) ? "<a href='" . htmlspecialchars($author['author-url']) . "'>Link</a>" : 'N/A') . "</td>";
                    echo "<td>" . (isset($author['authid']) ? htmlspecialchars($author['authid']) : 'N/A') . "</td>";
                    echo "<td>" . (isset($author['orcid']) ? htmlspecialchars($author['orcid']) : 'N/A') . "</td>";
                    echo "<td>" . (isset($author['given-name']) ? htmlspecialchars($author['given-name']) : 'N/A') . "</td>";
                    echo "<td>" . (isset($author['surname']) ? htmlspecialchars($author['surname']) : 'N/A') . "</td>";
                    echo "<td>" . (isset($author['initials']) ? htmlspecialchars($author['initials']) : 'N/A') . "</td>";
                    
                    // Mostrar todas las afiliaciones
                    if (isset($entry['affil']) && is_array($entry['affil'])) {
                        $affiliations = [];
                        foreach ($entry['affil'] as $affil) {
                            $affiliations[] = htmlspecialchars($affil['affilname']);
                        }
                        echo "<td>" . implode(", ", $affiliations) . "</td>";
                    } else {
                        echo "<td>N/A</td>";
                    }
                    
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8'>No hay autores disponibles</td></tr>";
            }
        }
        echo "</tbody></table>";
    } else {
        echo "<h2>No se encontraron resultados.</h2>";
    }
}

// Mostrar resultados de la búsqueda
mostrarResultados($data);

// Si no se encuentran resultados, intentar búsqueda solo con el título
if (!isset($data['search-results']['entry']) || empty($data['search-results']['entry'])) {
    $querySoloTitulo = 'title(' . $titulo . ')';
    $data = buscarEnScopus($querySoloTitulo);
    echo "<h2>Resultados de la búsqueda en Scopus (solo título):</h2>";
    mostrarResultados($data);
}
?>
