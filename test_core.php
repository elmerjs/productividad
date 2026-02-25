<?php

$apiKeyc = "JLOvPD53AXrqN1fRYV4lwMc7BIaiZp8H";
$tituloArticulo ="Evaluación del daño oxidativo y por metilación del ADN de pintores expuestos ocupacionalmente a solventes";// $nombre_articulo;

// Codificar el título para la URL
$tituloCodificado = urlencode('title:"' . $tituloArticulo . '"');

// Construir la URL de búsqueda en CORE con búsqueda parcial, limitado a 1 resultado
$url = "https://api.core.ac.uk/v3/search/works?apiKey=$apiKeyc&q=$tituloCodificado&limit=1";

// Inicializar cURL
$ch = curl_init();

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Para seguir redirecciones
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128'); // Proxy (si es necesario)
curl_setopt($ch, CURLOPT_VERBOSE, true); // Para depuración

// Ejecutar la solicitud
$response = curl_exec($ch);

// Manejar errores de cURL
if (curl_errno($ch)) {
    echo 'Error de cURL: ' . curl_error($ch) . "\n";
} else {
    // Decodificar la respuesta JSON
    $data = json_decode($response, true);

    // Verificar si la decodificación fue exitosa
    if ($data === null) {
        echo "Error al decodificar la respuesta JSON.\n";
    } else {
        // Verificar si hay resultados
        if (isset($data['results']) && count($data['results']) > 0) {
            echo "✅ Resultado encontrado:\n";

            $article = $data['results'][0]; // Obtener el primer resultado (y único)

            // Guardar los datos en variables
            $core_titulo = $article['title'] ?? 'No disponible';

            // DOI
            $doi = "No disponible";
            if (isset($article['identifiers']) && is_array($article['identifiers'])) {
                foreach ($article['identifiers'] as $identifier) {
                    if (isset($identifier['type']) && $identifier['type'] === 'DOI' && isset($identifier['identifier'])) {
                        $doi = $identifier['identifier'];
                        break;
                    }
                }
            }
            $core_doi = $doi;

            // Autores
            $autores = [];
            if (isset($article['authors']) && is_array($article['authors']) && count($article['authors']) > 0) {
                $autores = array_column($article['authors'], 'name');
            }
            $core_profesores = implode(", ", $autores);
            $core_num_profesores = count($autores);

            // Revista/Publicado por
            $core_revista = 'No disponible';
            $core_issn = 'No disponible';
            if (isset($article['journals']) && !empty($article['journals'])) {
                $revista = $article['journals'][0]; // Tomamos la primera revista si hay varias
                if (isset($revista['identifier'])) {
                    $journalIdentifier = $revista['identifier'];
                    $journalUrl = "https://api.core.ac.uk/v3/journals/" . $journalIdentifier . "?apiKey=" . $apiKeyc;

                    $chJournal = curl_init();
                    curl_setopt($chJournal, CURLOPT_URL, $journalUrl);
                    curl_setopt($chJournal, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chJournal, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                    curl_setopt($chJournal, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($chJournal, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128');

                    $journalResponse = curl_exec($chJournal);

                    if (!curl_errno($chJournal)) {
                        $journalData = json_decode($journalResponse, true);
                        if ($journalData !== null) {
                            $core_revista = $journalData['title'] ?? 'No disponible';
                            if (isset($journalData['identifiers'])) {
                                foreach ($journalData['identifiers'] as $identifier) {
                                    if (is_array($identifier) && isset($identifier['type']) && $identifier['type'] === 'issn') {
                                        $core_issn = $identifier['identifier'];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    curl_close($chJournal);
                } else if (isset($revista['title'])) {
                    $core_revista = $revista['title'];
                    if (isset($revista['identifiers'])) {
                        foreach ($revista['identifiers'] as $identifier) {
                            if (strpos($identifier, 'issn:') !== false) {
                                $core_issn = str_replace('issn:', '', $identifier);
                                break;
                            }
                        }
                    }
                }
            }

            // Año de publicación
            $core_anio_publicacion = $article['yearPublished'] ?? 'No disponible';

            // Mostrar resultados organizados con saltos de línea
            echo "Título: $core_titulo\n";
            echo "DOI: $core_doi\n";
            echo "Autores: $core_num_profesores autores: $core_profesores\n";
            echo "Revista/Publicado por: $core_revista\n";
            echo "ISSN: $core_issn\n";
            echo "Año de publicación: $core_anio_publicacion\n";
            
        } else {
            echo "❌ No se encontraron resultados para: \"$tituloArticulo\".\n";
        }
    }
}

// Cerrar cURL
curl_close($ch);

?>
