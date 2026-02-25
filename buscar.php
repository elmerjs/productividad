<?php
// Conexión a la base de datos
$servername = "localhost"; // Cambia esto según tu configuración
$username = "root"; // Cambia esto por tu usuario de base de datos
$password = ""; // Cambia esto por tu contraseña de base de datos
$dbname = "productividad"; // Cambia esto por el nombre de tu base de datos

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
  //  $doc_profesor = trim($_POST['doc_profesor']);
    $issn = urlencode(trim($_POST['issn']));
    $nombre_articulo = trim($_POST['nombre_articulo']);

    
    
    // Consulta para verificar si el artículo ya existe
    $sql = "SELECT COUNT(*) FROM articulo WHERE issn = ? AND nombre_articulo LIKE ?";
    $nombre_articulo_like = '%' . $nombre_articulo . '%'; // Agregar comodines para la búsqueda LIKE
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $issn, $nombre_articulo_like);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    // Verificar si el artículo ya existe
    if ($count > 0) {
        echo "Existe en la base de datos un artículo similar.<br>";

        // Consulta para obtener el nombre del artículo, ISSN y profesores relacionados
        $sqlDetalles = "
            SELECT a.nombre_articulo, a.issn, sp.fk_id_profesor
            FROM articulo a
            JOIN solicitud s ON a.id_articulo = s.fk_id_articulo
            JOIN solicitud_profesor sp ON sp.fk_id_solicitud = s.id_solicitud_articulo
            WHERE a.issn = ? AND a.nombre_articulo LIKE ?
        ";
        $stmtDetalles = $conn->prepare($sqlDetalles);
        $stmtDetalles->bind_param("ss", $issn, $nombre_articulo_like);
        $stmtDetalles->execute();
        $stmtDetalles->bind_result($nombreArticuloBd, $issnBd, $fkIdProfesor);
        
        // Variables para mostrar una vez
        $articuloEncontrado = false;

        // Mostrar detalles del artículo solo una vez
        while ($stmtDetalles->fetch()) {
            if (!$articuloEncontrado) {
                echo "<p><strong>Nombre Artículo:</strong> " . htmlspecialchars($nombreArticuloBd) . "<br>";
                echo "<strong>ISSN:</strong> " . htmlspecialchars($issnBd) . "</p>";
                $articuloEncontrado = true; // Marcar que ya se mostró el artículo
            }
            echo "<strong>Profesor ID:</strong> " . htmlspecialchars($fkIdProfesor) . "<br>";
        }

        $stmtDetalles->close();
    } else {
        echo "Artículo nuevo.";
    }
    
    
    // URL para buscar en SCImago
    $urlScimago = "https://www.scimagojr.com/journalsearch.php?q=" . $issn;

    // URL para buscar en MIAR
    $urlMiar = "https://miar.ub.edu/issn/" . $issn;

    // Inicializar cURL para SCImago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlScimago);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.unicauca.edu.co:3128'); // Proxy, si es necesario
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL (opcional)

    // Ejecutar la sesión cURL para SCImago
    $contenidoScimago = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Error al obtener la página SCImago: " . curl_error($ch);
        curl_close($ch);
        exit();
    }
    curl_close($ch);

    // Procesar el contenido HTML de SCImago
    $dom = new DOMDocument();
    @$dom->loadHTML($contenidoScimago);
    $xpath = new DOMXPath($dom);
    $resultadosScimago = $xpath->query("//a[contains(@href, 'journalsearch.php')]");

    // Inicializar variables
    $nombreRevistaScimago = null;
    $pais = 'No disponible';
    $editorial = 'No disponible';

    // Mostrar resultados de SCImago
   echo "<h2>Datos suministrados:</h2>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
    //echo "<strong>Docente Profesor:</strong> " . htmlspecialchars($doc_profesor) . "<br>";
    echo "<strong>Artículo Consultado:</strong> " . htmlspecialchars($nombre_articulo) . "<br>";
    echo "<strong>ISSN Consultado:</strong> " . htmlspecialchars($issn) . "<br>";
    echo "</div>";
    
  
    echo "<h2>Resultados de la búsqueda en SCImago:</h2>";
    
    if ($resultadosScimago->length > 0) {
           foreach ($resultadosScimago as $resultado) {
            $nombreRevistaScimago = $xpath->query(".//span[@class='jrnlname']", $resultado)->item(0)->nodeValue;
            $textoCompleto = trim($resultado->textContent); 
            $info = explode("\n", $textoCompleto);
            $pais = isset($info[0]) ? trim($info[0]) : 'No disponible';
            $editorial = isset($info[1]) ? trim($info[1]) : 'No disponible';

            // Mostrar el resultado de forma ordenada
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<strong>ISSN:</strong> " . htmlspecialchars($issn) . "<br>";
            echo "<strong>Título:</strong> " . htmlspecialchars($nombreRevistaScimago) . "<br>";
            echo "<strong>País:</strong> " . htmlspecialchars($pais) . "<br>";
            echo "<strong>Editorial:</strong> " . htmlspecialchars($editorial) . "<br>";
            echo "</div>";

            break; // Solo queremos el primer resultado
        }

    } else {
        echo "No se encontraron resultados en SCImago.";
    }

    // Inicializar cURL para MIAR
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlMiar);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.unicauca.edu.co:3128'); // Proxy, si es necesario
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL (opcional)

    // Ejecutar la sesión cURL para MIAR
    $contenidoMiar = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Error al obtener la página MIAR: " . curl_error($ch);
        curl_close($ch);
        exit();
    }
    curl_close($ch);
    
    // Procesar el contenido HTML de MIAR
    $domMiar = new DOMDocument();
    @$domMiar->loadHTML($contenidoMiar);
    $xpathMiar = new DOMXPath($domMiar);
    
    // Buscar el div que contiene el título de la revista
    $resultadosMiar = $xpathMiar->query("//*[@id='divtxt_Revista_0']");

    // Inicializar variable
    $nombreRevistaMiar = null;

    // Mostrar resultados de MIAR
    echo "<h2>Resultados de la búsqueda en MIAR:</h2>";
    
    if ($resultadosMiar->length > 0) {
        foreach ($resultadosMiar as $resultado) {
                // Obtener el texto completo y eliminar espacios adicionales
                $nombreRevistaMiar = trim($resultado->textContent);

                // Mostrar el título de la revista
              echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";   
            echo "<p><strong>Título MIAR:</strong> " . htmlspecialchars($nombreRevistaMiar) . "</p></div>";
                break; // Solo queremos el primer resultado
            }
    } else {
        echo "No se encontraron resultados en MIAR.";
    }

$apiKey = '803bbe28a496ac467be562f4f18d3d91';
$titulo = $nombre_articulo; // Utilizar el título del formulario 
$titulo = str_replace([':', '(', ')', '–', ','], ' ', $titulo); // Reemplaza ":", paréntesis y comas por espacios
$titulo = preg_replace('/\s+/', ' ', $titulo); // Reemplaza múltiples espacios por uno solo
$titulo = trim($titulo); // Eliminar espacios en blanco al inicio y al final
$titulo = urlencode($titulo); // Codificar el título
$issn = urlencode($issn); // Asegúrate de que el ISSN también esté correctamente codificado
$urlScopus = 'https://api.elsevier.com/content/search/scopus';

// Función para buscar en Scopus
function buscarEnScopus($query) {
    global $apiKey, $urlScopus;
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
    curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.unicauca.edu.co:3128'); // Descomenta si necesitas usar el proxy

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

// Intentar búsqueda con ISSN
$queryConIssn = 'title(' . $titulo . ') AND issn(' . $issn . ')';
$data = buscarEnScopus($queryConIssn);

echo "<h2>Resultados de la búsqueda en Scopus:</h2>";
if (isset($data['search-results']['entry']) && !empty($data['search-results']['entry'])) {
    // Solo queremos el primer resultado
    $entry = $data['search-results']['entry'][0]; 

    echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
    echo "<strong>Título:</strong> " . (isset($entry['dc:title']) ? htmlspecialchars($entry['dc:title']) : 'No disponible') . "<br>";
    
    // Manejo de autores
    $autores = isset($entry['dc:creator']) ? (is_array($entry['dc:creator']) ? implode(', ', $entry['dc:creator']) : $entry['dc:creator']) : 'No disponible';
    echo "<strong>Autores:</strong> " . htmlspecialchars($autores) . "<br>";
    
    // Manejo de fecha de publicación
    echo "<strong>Fecha de publicación:</strong> " . (isset($entry['prism:coverDate']) ? htmlspecialchars($entry['prism:coverDate']) : 'No disponible') . "<br>";
    
    // Manejo de DOI
    echo "<strong>DOI:</strong> " . (isset($entry['prism:doi']) ? htmlspecialchars($entry['prism:doi']) : 'No disponible') . "<br>";
    
    // Manejo de ISSN
    echo "<strong>ISSN:</strong> " . (isset($entry['prism:issn']) ? htmlspecialchars($entry['prism:issn']) : 'No disponible') . "<br>";
    
    // Manejo de eISSN
    echo "<strong>eISSN:</strong> " . (isset($entry['prism:eIssn']) ? htmlspecialchars($entry['prism:eIssn']) : 'No disponible') . "<br>";
    
    // Obtener el volumen y el número (issue)
    $volume = isset($entry['prism:volume']) ? htmlspecialchars($entry['prism:volume']) : 'No disponible';
    $issue = isset($entry['prism:issueIdentifier']) ? htmlspecialchars($entry['prism:issueIdentifier']) : 'No disponible';
    echo "<strong>Volumen:</strong> " . $volume . "<br>";
    echo "<strong>Número:</strong> " . $issue . "<br>";
        $scopus_revista = isset($entry['prism:publicationName']) ? htmlspecialchars($entry['prism:publicationName']) : 'No disponible';
    echo "<strong>Revista:</strong> " . (isset($entry['prism:publicationName']) ? htmlspecialchars($entry['prism:publicationName']) : 'No disponible') . "<br>";
    
    $tipo_documento = isset($entry['subtypeDescription']) ? htmlspecialchars($entry['subtypeDescription']) : 'No disponible';
    echo "<strong>Tipo de Documento:</strong> " . $tipo_documento . "<br>";
    
    
    echo "</div>"; // Cerrar el div
} else {
    // Si no se encuentran resultados, intentar búsqueda solo con el título
    $querySoloTitulo = 'title(' . $titulo . ')';
    $data = buscarEnScopus($querySoloTitulo);
    
    if (isset($data['search-results']['entry']) && !empty($data['search-results']['entry'])) {
        // Procesar resultados
        $entry = $data['search-results']['entry'][0]; 

        echo "<h2>Resultados de la búsqueda en Scopus (solo título):</h2>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
        echo "<strong>Título:</strong> " . (isset($entry['dc:title']) ? htmlspecialchars($entry['dc:title']) : 'No disponible') . "<br>";
        
        // Manejo de autores
        $autores = isset($entry['dc:creator']) ? (is_array($entry['dc:creator']) ? implode(', ', $entry['dc:creator']) : $entry['dc:creator']) : 'No disponible';
        echo "<strong>Autores:</strong> " . htmlspecialchars($autores) . "<br>";
        
        // Manejo de fecha de publicación
        echo "<strong>Fecha de publicación:</strong> " . (isset($entry['prism:coverDate']) ? htmlspecialchars($entry['prism:coverDate']) : 'No disponible') . "<br>";
        
        // Manejo de DOI
        echo "<strong>DOI:</strong> " . (isset($entry['prism:doi']) ? htmlspecialchars($entry['prism:doi']) : 'No disponible') . "<br>";
        
        // Manejo de ISSN
        echo "<strong>ISSN:</strong> " . (isset($entry['prism:issn']) ? htmlspecialchars($entry['prism:issn']) : 'No disponible') . "<br>";
        
        // Manejo de eISSN
        echo "<strong>eISSN:</strong> " . (isset($entry['prism:eIssn']) ? htmlspecialchars($entry['prism:eIssn']) : 'No disponible') . "<br>";
        
        // Obtener el volumen y el número (issue)
        $volume = isset($entry['prism:volume']) ? htmlspecialchars($entry['prism:volume']) : 'No disponible';
        $issue = isset($entry['prism:issueIdentifier']) ? htmlspecialchars($entry['prism:issueIdentifier']) : 'No disponible';
        echo "<strong>Volumen:</strong> " . $volume . "<br>";
        echo "<strong>Número:</strong> " . $issue . "<br>";
        echo "<strong>Revista:</strong> " . (isset($entry['prism:publicationName']) ? htmlspecialchars($entry['prism:publicationName']) : 'No disponible') . "<br>";
        $tipo_documento = isset($entry['subtypeDescription']) ? htmlspecialchars($entry['subtypeDescription']) : 'No disponible';
    echo "<strong>Tipo de Documento:</strong> " . $tipo_documento . "<br>";
        echo "</div>"; // Cerrar el div
    } else {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
        echo "No se encontraron resultados en Scopus.";
        echo "</div>"; // Cerrar el div
    }
}

// Definir la URL base para la API de DOAJ
// Definir la URL base para la API de DOAJ

//$tituloEscapado = urlencode($nombre_articulo); // Escapar el título para la URL
echo "<h2>Resultados búsqueda en DOAJ</h2>";
$tituloArticulo=$nombre_articulo;
// Título del artículo
//$tituloArticulo = 'Disrupted water governance in the shadows: Revealing the role of hidden actors in the Upper Cauca River Basin in Colombia';

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
    //echo "<h2>Resultados de la búsqueda en DOAJ:</h2><br>";
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
                $doaj_issn = $issns[0]; // Suponemos que el primer elemento es el ISSN
            }
        }
        
        // Acceder al eISSN desde el identificador
        if (isset($result['bibjson']['identifier'])) {
            foreach ($result['bibjson']['identifier'] as $id) {
                if ($id['type'] === 'eissn') {
                    $doaj_eissn = $id['id'];
                }
            }
        }
        
        // DOI
        $doi = 'No disponible';
        if (isset($result['bibjson']['identifier'])) {
            foreach ($result['bibjson']['identifier'] as $id) {
                if ($id['type'] === 'doi') {
                    $doaj_doi = $id['id'];
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
        $doaj_volume = $result['bibjson']['journal']['volume'] ?? 'No disponible';
        $doaj_number = $result['bibjson']['journal']['number'] ?? 'No disponible';

        // Enlace al artículo en DOAJ
        $doiUrl = 'https://doi.org/' . $doi; // Enlace directo al DOI, si está disponible
        $doajLink = 'https://doaj.org/article/' . $result['id']; // Enlace directo al artículo en DOAJ

        // Mostrar la información de forma ordenada
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
        echo "<strong>Título:</strong> $title<br>";
        echo "<strong>Revista:</strong> $journalTitle<br>"; // Nombre de la revista añadido aquí
        echo "<strong>Autores:</strong> $authorsList<br>";
        echo "<strong>Fecha de publicación:</strong> $publicationDate<br>";
        echo "<strong>DOI:</strong> $doaj_doi<br>";
        echo "<strong>ISSN:</strong> $doaj_issn<br>";
        echo "<strong>eISSN:</strong> $doaj_eissn<br>";
        echo "<strong>Volumen:</strong> $doaj_volume<br>";
        echo "<strong>Número:</strong> $doaj_number<br>";
        echo "<strong>Enlace DOI:</strong> <a href='" . htmlspecialchars($doiUrl) . "' target='_blank'>" . htmlspecialchars($doiUrl) . "</a><br>";
        echo "<strong>Enlace DOAJ:</strong> <a href='" . htmlspecialchars($doajLink) . "' target='_blank'>" . htmlspecialchars($doajLink) . "</a>";
        echo "</div>";
    }
} else {
    echo "No se encontraron artículos que coincidan con la búsqueda en DOAJ.";
}
    //issn  publindex  para revistas ancioanels
//$issn = '0123-3432'; // Cambia esto al ISSN que necesites

// URL de la API de Publindex con un límite de 500 registros y el filtro por ISSN
$url = "https://www.datos.gov.co/resource/mwmn-inyg.json?txt_issn_p=$issn&\$limit=500";
 echo "<h2>Resultados de la búsqueda en Publindex - Revistas Nacionales:</h2>";

// Iniciar cURL para la primera consulta
$ch = curl_init($url);

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128'); // Cambia esto a tu proxy si es necesario

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);

// Cerrar cURL
curl_close($ch);

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Verificar si hay resultados
if (!empty($data)) {
    // Extraer el último año
    $ultimoAno = 0; // Inicializa la variable para almacenar el último año
    foreach ($data as $item) {
        $nroAno = intval($item['nro_ano']); // Asegúrate de que el año sea un entero
        if ($nroAno > $ultimoAno) {
            $ultimoAno = $nroAno; // Actualiza el último año si se encuentra uno más reciente
        }
    }

    // Verifica si se encontró un año válido
   if ($ultimoAno > 0) {
    // Ahora consulta nuevamente usando el último año encontrado
    $urlUltimoAno = "https://www.datos.gov.co/resource/mwmn-inyg.json?txt_issn_p=$issn&nro_ano=$ultimoAno&\$limit=10";

    // Iniciar cURL para la segunda consulta
    $ch = curl_init($urlUltimoAno);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128'); // Cambia esto a tu proxy si es necesario
    $responseUltimoAno = curl_exec($ch);
    curl_close($ch);

    // Decodificar la respuesta JSON para el último año
    $dataUltimoAno = json_decode($responseUltimoAno, true);

    // Mostrar los resultados
    echo "IBN Publindex I - $ultimoAno\n"; // Muestra el último año encontrado
   foreach ($dataUltimoAno as $item) {
    // Obtener los datos, asegurando que se manejen los casos no disponibles
    $tipoClasificacion = ($item['id_clas_rev'] ?? 'No disponible');
    $issnImpreso = ($item['txt_issn_p'] ?? 'No disponible');
    $issnDigital = ($item['txt_issn_l'] ?? 'No disponible');
    $institucion = ($item['nme_inst_edit_1'] ?? 'No disponible');
    $nombreRevista = ($item['nme_revista_in'] ?? 'No disponible');

    // Mostrar la información de forma ordenada
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
    echo "<strong>Nombre de la Revista:</strong> " . htmlspecialchars($nombreRevista) . "<br>";
    echo "<strong>ISSN:</strong> " . htmlspecialchars($issnImpreso) . ", " . htmlspecialchars($issnDigital) . "<br>";
    echo "<strong>Clasificación:</strong> " . htmlspecialchars($tipoClasificacion) . "<br>";
    echo "<strong>Institución:</strong> " . htmlspecialchars($institucion) . "<br>";
    echo "</div>";
}
}
 else {
        echo "No se encontraron años válidos para publindex nal en el ISSN: $issn.";
    }
} else {
    echo "No se encontraron registros para  publindex nal el ISSN: $issn.";
}


    // Insertar datos en la base de datos
    $stmt = $conn->prepare("INSERT INTO articulo (
        issn,
        nombre_articulo, 
        nombre_revista_scimago, 
        issn_scopus, 
        titulo_scopus, 
        pais_scimago, 
        editorial_scimago,
        nombre_revista_miar, 
        autores_scopus, 
        fecha_publicacion_scopus, 
        doi_scopus, 
        eissn_scopus,
        volumen_scopus,
        issue_scopus, 
        scopus_revista,
        doaj_articulo,
        doaj_revista, 
        doaj_issn, 
        doaj_eissn,
        doaj_doi, 
        doaj_volumen, 
        doaj_numero,
        doaj_enlace_doaj,
    	publindex_año,
        publindex_revista,
        publindex_issn,
        publindex_eissn,	
        publindex_editor,
        publindex_clasific


    ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Usar bind_param para enlazar las variables
    $stmt->bind_param("sssssssssssssssssssssssssssss",  $issn, $nombre_articulo, $nombreRevistaScimago, $issn, $nombre_articulo, $pais, $editorial, $nombreRevistaMiar, $autores, $entry['prism:coverDate'], $doi, $eissn, $volume, $issue,$scopus_revista,$title,$journalTitle,$doaj_issn,$doaj_eissn,$doaj_doi,$doaj_volume,$doaj_number,$doajLink,$ultimoAno,$nombreRevista,$issnImpreso,$issnDigital,$institucion,$tipoClasificacion);

    // Ejecutar la consulta y verificar si se insertó correctamente
    if ($stmt->execute()) {
        echo "Artículo insertado exitosamente.";
    } else {
        echo "Error al insertar el artículo: " . $stmt->error;
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
    
    
}
?>

<!-- Formulario para que el usuario ingrese más datos -->
<h3>Ingrese información adicional:</h3>
<form method="post" action="tu_proxima_accion.php">
    <label for="numero_oficio">Número de oficio:</label>
    <input type="text" id="numero_oficio" name="numero_oficio" required><br>

    <label for="identificador_solicitud">Identificador solicitud:</label>
    <input type="text" id="identificador_solicitud" name="identificador_solicitud" required><br>

    <label for="numero_profesores">Número de profesores:</label>
    <input type="number" id="numero_profesores" name="numero_profesores" required><br>

    <label for="puntaje">Puntaje:</label>
    <input type="number" id="puntaje" name="puntaje" required><br>

    <input type="submit" value="Enviar">
</form>
