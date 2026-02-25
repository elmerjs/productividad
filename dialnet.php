<?php
// Título del artículo que quieres buscar
$titulo = "Análisis bibliométrico";
$tituloEscapado = urlencode($titulo);

// URL base de la API de ScienceDirect
$url = "https://api.elsevier.com/content/search/sciencedirect?query=$tituloEscapado";

// Iniciar cURL
$ch = curl_init($url);

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-ELS-APIKey: YOUR_API_KEY' // Reemplaza con tu clave API
]);

// Configuración del proxy
curl_setopt($ch, CURLOPT_PROXY, "http://proxy.unicauca.edu.co:3128"); // Dirección del proxy
curl_setopt($ch, CURLOPT_PROXYPORT, 3128); // Puerto del proxy

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);

// Verifica si ocurrió un error
if (curl_errno($ch)) {
    echo 'Error en cURL: ' . curl_error($ch);
}

// Cerrar cURL
curl_close($ch);

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Verificar y procesar los datos obtenidos
if (isset($data['search-results']['entry']) && !empty($data['search-results']['entry'])) {
    echo "<h2>Información del artículo:</h2><br>";
    
    foreach ($data['search-results']['entry'] as $articulo) {
        $titulo = $articulo['dc:title'] ?? 'No disponible';
        $anio = $articulo['prism:coverDate'] ?? 'No disponible';
        $paginas = $articulo['prism:pageRange'] ?? 'No disponible';
        $editor = $articulo['dc:creator'] ?? 'No disponible';
        $doi = $articulo['prism:doi'] ?? 'No disponible';
        $urlArticle = $articulo['link'][0]['@href'] ?? 'No disponible';

        // Mostrar información formateada
        echo "<strong>Título:</strong> $titulo<br>";
        echo "<strong>Año:</strong> $anio<br>";
        echo "<strong>Páginas:</strong> $paginas<br>";
        echo "<strong>Autor(es):</strong> $editor<br>";
        echo "<strong>DOI:</strong> <a href='https://doi.org/$doi' target='_blank'>$doi</a><br>";
        echo "<strong>Enlace al artículo:</strong> <a href='" . htmlspecialchars($urlArticle) . "' target='_blank'>" . htmlspecialchars($urlArticle) . "</a><br><br>";
    }
} else {
    echo "No se encontraron resultados para el artículo solicitado.";
}
?>
