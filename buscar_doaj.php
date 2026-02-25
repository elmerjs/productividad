<?php
// Título del artículo
$tituloArticulo = 'Disrupted water governance in the shadows: Revealing the role of hidden actors in the Upper Cauca River Basin in Colombia';

// Escapar el título para evitar problemas de JSON
$tituloEscapado = addslashes($tituloArticulo); // Escapar comillas dobles

// Crear la estructura de la consulta en JSON manualmente
$queryJson = '{"query":{"query_string":{"query":"' . $tituloEscapado . '","default_operator":"AND","default_field":"bibjson.title"}},"track_total_hits":true}';

// Crear la URL de búsqueda de DOAJ
$urlDoaj = 'https://doaj.org/search/articles';
$queryParamsDoaj = [
    'ref' => 'homepage-box',
    'source' => $queryJson // Usar el JSON construido
];
$urlDoajFull = $urlDoaj . '?' . http_build_query($queryParamsDoaj);

// Mostrar el enlace
echo '<a href="' . htmlspecialchars($urlDoajFull) . '" target="_blank">Buscar artículo en DOAJ</a>';
?>
