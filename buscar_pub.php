<?php
// API Key de Scopus
$apiKey = '803bbe28a496ac467be562f4f18d3d91';

// Título del artículo a buscar
$titulo = 'Virtual reality games for cognitive rehabilitation';
// ISSN del artículo a buscar
$issn = '13594338'; // Cambia esto al ISSN que necesites

// URL base de la API de Scopus
$apiUrl = 'https://api.elsevier.com/content/search/scopus';

// Parámetros de búsqueda (buscando por título y ISSN)
$queryParams = [
    'query' => 'title(' . urlencode($titulo) . ') AND issn(' . $issn . ')',
    'apiKey' => $apiKey,
    'httpAccept' => 'application/json' // Formato de respuesta
];

// Crear la URL completa con los parámetros
$url = $apiUrl . '?' . http_build_query($queryParams);

// Iniciar la solicitud HTTP utilizando cURL
$ch = curl_init();

// Configurar las opciones de cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Configuración del proxy
curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.unicauca.edu.co:3128');

// Ejecutar la solicitud
$response = curl_exec($ch);

// Verificar si ocurrió algún error
if (curl_errno($ch)) {
    echo 'Error al realizar la solicitud: ' . curl_error($ch);
} else {
    // Decodificar la respuesta JSON
    $data = json_decode($response, true);
    
    // Mostrar la respuesta completa para inspeccionar la estructura
    echo '<h2>Respuesta completa de Scopus:</h2>';
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

// Cerrar la conexión cURL
curl_close($ch);
?>
