<?php
// Título del artículo
$tituloArticulo = 'Disrupted water governance in the shadows: Revealing the role of hidden actors in the Upper Cauca River Basin in Colombia';

// Escapar el título para evitar problemas en la consulta
$tituloEscapado = urlencode('bibjson.title:"' . $tituloArticulo . '"'); // Escapar caracteres especiales para la URL

// URL base de la API de DOAJ (CORRECTA) con la consulta añadida
$url = 'https://doaj.org/api/search/articles/' . $tituloEscapado;

// Iniciar cURL
$ch = curl_init($url);

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

// Configuración del proxy Unicauca (proxy.unicauca.edu.co en puerto 3128)
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128');

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);

// Cerrar cURL
curl_close($ch);

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Imprimir la respuesta JSON para verificar su estructura (puedes comentar esta línea después de verificar)
//echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

// Mostrar los valores relevantes si hay resultados
if (isset($data['results']) && count($data['results']) > 0) {
    echo "<h2>Resultados de la búsqueda en DOAJ:</h2><br>";
    foreach ($data['results'] as $result) {
        // Título del artículo
        $title = $result['bibjson']['title'] ?? 'No disponible';
        
        // Título de la revista
        $journalTitle = $result['bibjson']['journal']['title'] ?? 'No disponible';
        
        // ISSN y eISSN
        $issn = 'No disponible';
        $eissn = 'No disponible';
        
        // Acceder al ISSN desde el array issns
        if (isset($result['bibjson']['journal']['issns'])) {
            $issns = $result['bibjson']['journal']['issns'];
            if (count($issns) > 0) {
                $issn = $issns[0]; // Suponemos que el primer elemento es el ISSN
            }
        }
        
        // Acceder al eISSN desde el identificador
        if (isset($result['bibjson']['identifier'])) {
            foreach ($result['bibjson']['identifier'] as $id) {
                if ($id['type'] === 'eissn') {
                    $eissn = $id['id'];
                }
            }
        }
        
        // DOI
        $doi = 'No disponible';
        if (isset($result['bibjson']['identifier'])) {
            foreach ($result['bibjson']['identifier'] as $id) {
                if ($id['type'] === 'doi') {
                    $doi = $id['id'];
                }
            }
        }
        
        // Autores
        $authors = [];
        if (isset($result['bibjson']['author'])) {
            foreach ($result['bibjson']['author'] as $author) {
                $authors[] = $author['name'];
            }
        }
        $authorsList = $authors ? implode(', ', $authors) : 'No disponible';

        // Fecha de publicación
        $publicationDate = $result['bibjson']['year'] ?? 'No disponible';

        // Volumen y número
        $volume = $result['bibjson']['journal']['volume'] ?? 'No disponible';
        $number = $result['bibjson']['journal']['number'] ?? 'No disponible';

        // Enlace al artículo en DOAJ
        $doiUrl = 'https://doi.org/' . $doi; // Enlace directo al DOI, si está disponible
        $doajLink = 'https://doaj.org/article/' . $result['id']; // Enlace directo al artículo en DOAJ

        // Mostrar la información de forma ordenada
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
        echo "<strong>Título:</strong> $title<br>";
        echo "<strong>Autores:</strong> $authorsList<br>";
        echo "<strong>Fecha de publicación:</strong> $publicationDate<br>";
        echo "<strong>DOI:</strong> $doi<br>";
        echo "<strong>ISSN:</strong> $issn<br>";
        echo "<strong>eISSN:</strong> $eissn<br>";
        echo "<strong>Volumen:</strong> $volume<br>";
        echo "<strong>Número:</strong> $number<br>";
        echo "<strong>Enlace DOI:</strong> <a href='" . htmlspecialchars($doiUrl) . "' target='_blank'>" . htmlspecialchars($doiUrl) . "</a><br>";
        echo "<strong>Enlace DOAJ:</strong> <a href='" . htmlspecialchars($doajLink) . "' target='_blank'>" . htmlspecialchars($doajLink) . "</a>";
        echo "</div>";
    }
} else {
    echo "No se encontraron artículos que coincidan con la búsqueda.";
}
?>
