<?php
require_once ('vendor/autoload.php');
use \Statickidz\GoogleTranslate;

?>
<!DOCTYPE html>
<html lang="es">

    
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

     <script>
       function calcularPuntaje() {
    // Obtener valores de los campos
    const tipoArticulo = document.getElementById("tipo_articulo").value;
    const tipoPublindex = document.getElementById("tipo_publindex").value;
    const numeroAutores = parseInt(document.getElementById("numero_autores").value);

    // Definir los valores base de puntaje por tipo_publindex
    const valoresBase = {
        "A1": 15,
        "A2": 12,
        "B": 8,
        "C": 3
    };

    // Verificar si el tipo_publindex es válido y número de autores es un número
    if (!valoresBase[tipoPublindex] || isNaN(numeroAutores) || numeroAutores <= 0) {
        document.getElementById("puntaje").value = 0;
        return;
    }

    let puntajeBase = valoresBase[tipoPublindex];
    let puntaje = 0;

    // Cálculo del puntaje basado en el tipo de artículo y número de autores
    if (tipoArticulo === "FULL PAPER") {
        if (numeroAutores <= 3) {
            puntaje = puntajeBase;
        } else if (numeroAutores <= 5) {
            puntaje = puntajeBase / 2;
        } else {
            puntaje = puntajeBase /  (numeroAutores/2);
        }
    } else if (tipoArticulo === "ARTICULO CORTO") {
        // Factor 0.6 para "ARTÍCULO CORTO" y aplicar la misma lógica de autores
        let factor = 0.6;
        if (numeroAutores <= 3) {
            puntaje = puntajeBase * factor;
        } else if (numeroAutores <= 5) {
            puntaje = (puntajeBase * factor) / 2;
        } else {
            puntaje = (puntajeBase * factor) / (numeroAutores/2);
        }
    } else if (
        tipoArticulo === "REVISION DE TEMA" ||
        tipoArticulo === "EDITORIALES" ||
        tipoArticulo === "REPORTE DE CASO"
    ) {
        // Factor 0.3 para estos tipos y aplicar la misma lógica de autores
        let factor = 0.3;
        if (numeroAutores <= 3) {
            puntaje = puntajeBase * factor;
        } else if (numeroAutores <= 5) {
            puntaje = (puntajeBase * factor) / 2;
        } else {
            puntaje = (puntajeBase * factor) / (numeroAutores/2);
        }
    }

    // Redondear el puntaje a dos decimales y actualizar el campo de puntaje
    document.getElementById("puntaje").value = puntaje.toFixed(2);
}

        // Añadir eventos para recalcular el puntaje cuando cambian los valores
        window.addEventListener('DOMContentLoaded', (event) => {
            document.getElementById("tipo_articulo").addEventListener("change", calcularPuntaje);
            document.getElementById("tipo_publindex").addEventListener("change", calcularPuntaje);
            document.getElementById("numero_autores").addEventListener("input", calcularPuntaje);

            // Calcular el puntaje al cargar la página por si hay valores prellenados
            calcularPuntaje();
        });
    </script>
    <style>
           .datos-container {
            margin-top: 5px;
            font-style: italic;
            color: #555;
        }
        .accordion-bodyb {
            
    max-width: auto;
    padding: 14px;
    background-color: #e0ffe0; /* Color de fondo (opcional) */
    border: 1px solid #4CAF50; /* Borde verde (opcional) */
    color: #4CAF50; /* Color de texto verde (opcional) */
    border-radius: 5px; /* Bordes redondeados (opcional) */
    margin-top: 0px; /* Espacio superior para separación */
    margin-left: 10px; /* Desplazamiento hacia la derecha */
}
        .status-alert {
        color: red;
        font-weight: bold;

    }
      .status-box {
        display: inline-block;
        padding: 8px 15px;
        background-color: #f7f9fc;   /* Fondo suave */
        border-radius: 10px;         /* Esquinas redondeadas */
        box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1); /* Sombra ligera */
        margin-right: 10px;          /* Espaciado entre cajas */
        font-family: Arial, sans-serif;
        font-size: 14px;
    }
          .not-found {
        color: red !important;       /* Color rojo para el texto "N/A" */
        font-weight: bold;            /* Negrita para el texto de error */
              
    }
        
   .parent-container {
    display: inline-flex; /* Permite que los elementos se alineen en una línea */
    justify-content: flex-start; /* Alinea los elementos a la izquierda */
  
}

       .container {
    margin: 0; /* Elimina márgenes alrededor del contenedor */
    padding: 10px; /* Elimina el relleno dentro del contenedor */
    width: 100vw; /* Ocupa todo el ancho de la ventana */
    max-width: 100%; /* Permite que el ancho máximo sea 100% de la pantalla */
    overflow: auto; /* Permite desplazamiento en caso de que el contenido sea demasiado alto */
}
             .wrapper { display: flex; }
        .box { 
              flex: 1;
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            margin: 10px; }
        .left { background-color: ghostwhite; }
        .right { background-color: floralwhite; }
        
      .custom-container {
    width: 100%;
    max-width: none; /* Para asegurar que no se limite el ancho */
    margin: 0 auto;
}
body {
    padding-bottom: 20px; /* Opcional: Añade un espacio de relleno al final si el formulario está demasiado cerca del borde */
}
         .alerta-select {
        background-color: #ffecb3; /* Color amarillo claro */
    }
        .alerta-input {
        background-color: #ffecb3; /* Color amarillo claro */
    }
    </style>
  
</head>
<body>
    
<div class="container" style="margin: 10px;"> <!-- Contenedor con margen -->
        <h3 class="text-primary mb-4">Artículos Indexados:</h3>

    <?php
     
// Conexión a la base de datos
$servername = "localhost"; // Cambia esto según tu configuración
$username = "root"; // Cambia esto por tu usuario de base de datos
$password = ""; // Cambia esto por tu contraseña de base de datos
$dbname = "productividad"; // Cambia esto por el nombre de tu base de datos
$est_scopus ='';
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
 $issn = urlencode(trim($_POST['issn']));
$nombre_articulo = trim($_POST['nombre_articulo']);
$nombre_articulo_esp = $nombre_articulo;

    
    
    // Intentar la traducción con manejo de errores
    try {
        $source = 'en';
        $target = 'es';
        $text = $nombre_articulo;

        $trans = new GoogleTranslate();
        $result = $trans->translate($source, $target, $text);
        
        // Si llegamos aquí, la traducción fue exitosa
        // Procesar el texto traducido
        $articulos = ["un", "una", "unos", "unas", "el", "la", "los", "las", "de", "por", "en"];
        $result = str_replace([",", ".", ";", ":", "?", "!", "(", ")", "\"", "'"], "", $result);
        $palabras = explode(" ", $result);
        $palabras_filtradas = array_filter($palabras, function($palabra) use ($articulos) {
            return !in_array(strtolower($palabra), $articulos);
        });
        $texto_limpio = implode(" ", $palabras_filtradas);
        $nombre_articulo_esp = $texto_limpio;
        
    } catch (Exception $e) {
        // Si hay error, mantener el nombre original y opcionalmente registrar el error
        error_log("Error en Google Translate: " . $e->getMessage());
        $nombre_articulo_esp = $nombre_articulo; // Usar el original como fallback
    }

    
echo '<div class="wrapper">';
    // División izquierda
    echo '<div class="box left">';
    
    echo "<div style='border: 1px solid #ccc; padding: 10px;'>";
    //echo "<strong>Docente Profesor:</strong> " . htmlspecialchars($doc_profesor) . "<br>";
    echo "<strong>Artículo Consultado:</strong> " . htmlspecialchars($nombre_articulo) . "<br>";
    echo "<strong>ISSN Consultado:</strong> " . htmlspecialchars($issn) . "<br>";
    echo "</div>"; //cierra   grupo de consulta     
    
// Reemplazar caracteres especiales (excepto letras y números) por '%'
$nombre_articulo_modificado = preg_replace('/[^\w\s]/u', '%', $nombre_articulo);

// Limpiar el patrón: reemplazar múltiples '%' consecutivos por uno solo
$nombre_articulo_modificado = preg_replace('/%+/', '%', $nombre_articulo_modificado);

// Agregar '%' al inicio y al final del patrón
$nombre_articulo_like = '%' . $nombre_articulo_modificado . '%';
// Consulta para verificar si el artículo ya existe
$sql = "SELECT COUNT(*) FROM articulo WHERE issn = ? AND nombre_articulo LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $issn, $nombre_articulo_like);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

// Verificar si el artículo ya existe
?>
  
 <?php   
    
     echo '</div>';//cierra izquierdo

    // División derecha
    echo '<div class="box right">';
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
 
  //echo "<h2>Resultados de la búsqueda en SCImago:</h2>";

if ($resultadosScimago->length > 0) {
    $est_scimago=1;
    // Crear variables para mostrar en el modal
    $resultado = $resultadosScimago[0]; // Tomar solo el primer resultado
    $nombreRevistaScimago = $xpath->query(".//span[@class='jrnlname']", $resultado)->item(0)->nodeValue;
    $textoCompleto = trim($resultado->textContent); 
    $info = explode("\n", $textoCompleto);
    $pais = isset($info[0]) ? trim($info[0]) : 'No disponible';
    $editorial = isset($info[1]) ? trim($info[1]) : 'No disponible';

    // Enlace que abre el modal
echo "<div class='status-box'><a href='#' onclick='mostrarModal()'>SCImago: Ok</a></div>";    
    // Modal oculto inicialmente con estilo de Publindex
  echo "
    <div id='modalScimago' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.5); z-index: 1000;'>
        <div style='position:relative; margin: 10% auto; padding: 20px; background:white; width: 600px;'>
            <h5>Detalles de la Revista</h5>
            <div class='modal-body'>
                <div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>
                    <strong>ISSN:</strong> " . htmlspecialchars($issn) . "<br>
                    <strong>Título:</strong> " . htmlspecialchars($nombreRevistaScimago) . "<br>
                    <strong>País:</strong> " . htmlspecialchars($pais) . "<br>
                    <strong>Editorial:</strong> " . htmlspecialchars($editorial) . "<br>
                    <a href='https://www.scimagojr.com/journalsearch.php?q=" . urlencode($issn) . "' target='_blank'>Ver en SCImago</a>
                </div>
            </div>
            <button onclick='cerrarModalsc()'>Cerrar</button>
        </div>
    </div>";

    // Script para mostrar y cerrar el modal
    echo "
    <script>
        function mostrarModal() {
            document.getElementById('modalScimago').style.display = 'block';
        }
        function cerrarModalsc() {
            document.getElementById('modalScimago').style.display = 'none';
        }
    </script>";
} else {
    // Mensaje personalizado al no encontrar resultados
    echo "<div class='status-box'>SCImago: <span class='status-alert'>N/A</span></div>";
    $est_scimago=0;
}
/*MIAR ORIGINAL  SIN SOLUCION AL PROBLMEA DE LA PAGINA QUE NO RESPONDE*/
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
       // exit();
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

//echo "<h2>Resultados de la búsqueda en MIAR:</h2>";

if ($resultadosMiar->length > 0) {
    $est_miar=1;
    // Crear variables para mostrar en el modal
    $resultado = $resultadosMiar[0]; // Tomar solo el primer resultado
    $nombreRevistaMiar = trim($resultado->textContent);

    // Enlace que abre el modal
echo "<div class='status-box'><a href='#' onclick='mostrarModalMiar()'>MIAR: Ok</a></div>";  
    // Modal oculto inicialmente con estilo de Publindex
   echo "
    <div id='modalMiar' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.5); z-index: 1000;'>
        <div style='position:relative; margin: 10% auto; padding: 20px; background:white; width: 600px;'>
            <h5>Detalles de la Revista en MIAR</h5>
            <div class='modal-body'>
                <div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>
                    <strong>Título:</strong> " . htmlspecialchars($nombreRevistaMiar) . "<br>
                    <a href='https://miar.ub.edu/issn/" . urlencode($issn) . "' target='_blank'>Ver en MIAR</a>
                </div>
            </div>
            <button onclick='cerrarModalMiar()'>Cerrar</button>
        </div>
    </div>";

    // Script para mostrar y cerrar el modal
    echo "
    <script>
        function mostrarModalMiar() {
            document.getElementById('modalMiar').style.display = 'block';
        }
        function cerrarModalMiar() {
            document.getElementById('modalMiar').style.display = 'none';
        }
    </script>";
} else {
    // Mensaje personalizado al no encontrar resultados
echo "<div class='status-box'>MIAR: <span class='status-alert'>N/A</span></div>";  
    $est_miar=0;
}

    //aqui solobusca scopus por nombre art:
$apiKey = '803bbe28a496ac467be562f4f18d3d91';
$titulo = $nombre_articulo; // Utilizar el título del formulario 
$titulo = str_replace([':', '(', ')', '–', ','], ' ', $titulo); // Reemplaza ":", paréntesis y comas por espacios
$titulo = preg_replace('/\s+/', ' ', $titulo); // Reemplaza múltiples espacios por uno solo
$titulo = trim($titulo); // Eliminar espacios en blanco al inicio y al final
$titulo = urlencode($titulo); // Codificar el título
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

// Intentar búsqueda solo por título
$querySinIssn = 'title(' . $titulo . ')';
$data = buscarEnScopus($querySinIssn);

// Verificar si hay resultados y si se encuentra el primer resultado
if (isset($data['search-results']['entry']) && !empty($data['search-results']['entry'])) {
   
    // Solo queremos el primer resultado
    $entry = $data['search-results']['entry'][0];

    // Verificar que el campo 'eid' exista en el resultado
    if (isset($entry['eid'])) {
        // Enlace que abre el modal
echo "<div class='status-box'><a href='#' onclick='mostrarModalScopus()'>Scopus: Ok</a></div>"; $est_scopus=1;
        // Modal oculto inicialmente
       echo "
    <div id='modalScopus' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.5); z-index: 1000;'>
        <div style='position:relative; margin: 10% auto; padding: 20px; background:white; width: 80%; max-width: 600px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h3>Detalles del Artículo en Scopus</h3>
                <p><strong>Título:</strong> " . (isset($entry['dc:title']) ? htmlspecialchars($entry['dc:title']) : 'No disponible') . "</p>
                
                <p><strong>Autores:</strong> " . (isset($entry['dc:creator']) ? htmlspecialchars(is_array($entry['dc:creator']) ? implode(', ', $entry['dc:creator']) : $entry['dc:creator']) : 'No disponible') . "</p>
                
                <p><strong>Fecha de publicación:</strong> " . (isset($entry['prism:coverDate']) ? htmlspecialchars($entry['prism:coverDate']) : 'No disponible') . "</p>
                
                <p><strong>DOI:</strong> " . (isset($entry['prism:doi']) ? htmlspecialchars($entry['prism:doi']) : 'No disponible') . "</p>
                
                <p><strong>ISSN:</strong> " . (isset($entry['prism:issn']) ? htmlspecialchars($entry['prism:issn']) : 'No disponible') . "</p>
                
                <p><strong>eISSN:</strong> " . (isset($entry['prism:eIssn']) ? htmlspecialchars($entry['prism:eIssn']) : 'No disponible') . "</p>
                
                <p><strong>Volumen:</strong> " . (isset($entry['prism:volume']) ? htmlspecialchars($entry['prism:volume']) : 'No disponible') . "</p>
                
                <p><strong>Número:</strong> " . (isset($entry['prism:issueIdentifier']) ? htmlspecialchars($entry['prism:issueIdentifier']) : 'No disponible') . "</p>
                
                <p><strong>Revista:</strong> " . (isset($entry['prism:publicationName']) ? htmlspecialchars($entry['prism:publicationName']) : 'No disponible') . "</p>
                
                <p><strong>Tipo de Documento:</strong> " . (isset($entry['subtypeDescription']) ? htmlspecialchars($entry['subtypeDescription']) : 'No disponible') . "</p>
                
                <p><a href='https://www.scopus.com/record/display.uri?eid=" . urlencode($entry['eid']) . "&origin=resultslist' target='_blank'>Ver en Scopus</a></p>
                <button onclick='cerrarModalScopus()'>Cerrar</button>
            </div>
        </div>";

        echo "
        <script>
            function mostrarModalScopus() {
                document.getElementById('modalScopus').style.display = 'block';
            }
            function cerrarModalScopus() {
                document.getElementById('modalScopus').style.display = 'none';
            }
        </script>";
    } else {
echo "<div class='status-box'>Scopus: <span style='color: red;'>N/A</span></div>";    }
} else {
echo "<div class='status-box'>Scopus: <span style='color: red;'>N/A</span></div>";
    $est_scopus=0;
}

// Definir la URL base para la API de DOAJ

$tituloArticulo = $nombre_articulo; // Título del artículo
$tituloEscapado = urlencode('bibjson.title:"' . $tituloArticulo . '"'); // Escapar caracteres especiales para la URL

// URL base de la API de DOAJ
$url = 'https://doaj.org/api/search/articles/' . $tituloEscapado;

// Iniciar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

// Configuración del proxy Unicauca (si es necesario)
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128');

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);
curl_close($ch);

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Verificar si hay resultados
if (isset($data['results']) && count($data['results']) > 0) {
    $est_doaj=1;
    $result = $data['results'][0]; // Tomar el primer resultado

    // Obtener los detalles del artículo
    $title = $result['bibjson']['title'] ?? 'No disponible';
    $journalTitle = $result['bibjson']['journal']['title'] ?? 'No disponible';
    $publicationDate = $result['bibjson']['year'] ?? 'No disponible';

    // Obtener el DOI
    $doaj_doi = 'No disponible';
    if (isset($result['bibjson']['identifier'])) {
        foreach ($result['bibjson']['identifier'] as $id) {
            if ($id['type'] === 'doi') {
                $doaj_doi = $id['id'];
            }
        }
    }

    // Obtener el ISSN y eISSN
    $doaj_issn = 'No disponible';
    $doaj_eissn = 'No disponible';
    if (isset($result['bibjson']['journal']['issns'])) {
        $issns = $result['bibjson']['journal']['issns'];
        if (count($issns) > 0) {
            $doaj_issn = $issns[0]; // Suponemos que el primer elemento es el ISSN
        }
    }
    if (isset($result['bibjson']['identifier'])) {
        foreach ($result['bibjson']['identifier'] as $id) {
            if ($id['type'] === 'eissn') {
                $doaj_eissn = $id['id'];
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
    $numberOfAuthors = count($authors); // Contar el número de autores

    // Volumen y número
    $doaj_volume = $result['bibjson']['journal']['volume'] ?? 'No disponible';
    $doaj_number = $result['bibjson']['journal']['number'] ?? 'No disponible';

    // Enlace al artículo en DOAJ
    $doiUrl = 'https://doi.org/' . $doaj_doi; // Enlace directo al DOI, si está disponible
    $doajLink = 'https://doaj.org/article/' . $result['id']; // Enlace directo al artículo en DOAJ

    // Enlace que abre el modal
echo "<div class='status-box'>
        <a href='#' onclick='mostrarModalDoaj()' class='status-link'>DOAJ: OK</a>
      </div>";

// Modal oculto inicialmente
echo "
    <div id='modalDoaj' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.7); z-index: 1000;'>
        <div style='position:relative; margin: 10% auto; padding: 20px; background:white; border: 2px solid #007bff; border-radius: 10px; width: 600px; max-width: 90%; box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);'>
            <h5 style='color: #007bff;'>Detalles de la Revista DOAJ</h5>
            <p><strong>Título:</strong> " . htmlspecialchars($title) . "</p>
            <p><strong>Revista:</strong> " . htmlspecialchars($journalTitle) . "</p>
            <p><strong>Autores:</strong> " . htmlspecialchars($authorsList) . "</p>
            <p><strong>Número de autores:</strong> " . htmlspecialchars($numberOfAuthors) . "</p>
            <p><strong>Fecha de publicación:</strong> " . htmlspecialchars($publicationDate) . "</p>
            <p><strong>DOI:</strong> " . htmlspecialchars($doaj_doi) . "</p>
            <p><strong>ISSN:</strong> " . htmlspecialchars($doaj_issn) . "</p>
            <p><strong>eISSN:</strong> " . htmlspecialchars($doaj_eissn) . "</p>
            <p><strong>Volumen:</strong> " . htmlspecialchars($doaj_volume) . "</p>
            <p><strong>Número:</strong> " . htmlspecialchars($doaj_number) . "</p>
            <p><strong>Enlace DOI:</strong> <a href='" . htmlspecialchars($doiUrl) . "' target='_blank'>" . htmlspecialchars($doiUrl) . "</a></p>
            <p><strong>Enlace DOAJ:</strong> <a href='" . htmlspecialchars($doajLink) . "' target='_blank'>" . htmlspecialchars($doajLink) . "</a></p>
            <button onclick='cerrarModalDoaj()' style='background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;'>Cerrar</button>
        </div>
    </div>";

// Scripts para manejar el modal
echo "
    <script>
        function mostrarModalDoaj() {
            document.getElementById('modalDoaj').style.display = 'block';
        }
        function cerrarModalDoaj() {
            document.getElementById('modalDoaj').style.display = 'none';
        }
    </script>";

} 
    
    else {
        

$tituloArticulo = $nombre_articulo_esp; // Título del artículo
$tituloEscapado = urlencode($tituloArticulo);

// URL base de la API de DOAJ
$url = 'https://doaj.org/api/search/articles/' . $tituloEscapado;
//echo 'url: '.$url;
// Iniciar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

// Configuración del proxy Unicauca (si es necesario)
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128');

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);
curl_close($ch);

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Verificar si hay resultados
if (isset($data['results']) && count($data['results']) > 0) {
    $est_doaj=1;
    $result = $data['results'][0]; // Tomar el primer resultado

    // Obtener los detalles del artículo
    $title = $result['bibjson']['title'] ?? 'No disponible';
    $journalTitle = $result['bibjson']['journal']['title'] ?? 'No disponible';
    $publicationDate = $result['bibjson']['year'] ?? 'No disponible';

    // Obtener el DOI
    $doaj_doi = 'No disponible';
    if (isset($result['bibjson']['identifier'])) {
        foreach ($result['bibjson']['identifier'] as $id) {
            if ($id['type'] === 'doi') {
                $doaj_doi = $id['id'];
            }
        }
    }

    // Obtener el ISSN y eISSN
    $doaj_issn = 'No disponible';
    $doaj_eissn = 'No disponible';
    if (isset($result['bibjson']['journal']['issns'])) {
        $issns = $result['bibjson']['journal']['issns'];
        if (count($issns) > 0) {
            $doaj_issn = $issns[0]; // Suponemos que el primer elemento es el ISSN
        }
    }
    if (isset($result['bibjson']['identifier'])) {
        foreach ($result['bibjson']['identifier'] as $id) {
            if ($id['type'] === 'eissn') {
                $doaj_eissn = $id['id'];
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
    $numberOfAuthors = count($authors); // Contar el número de autores

    // Volumen y número
    $doaj_volume = $result['bibjson']['journal']['volume'] ?? 'No disponible';
    $doaj_number = $result['bibjson']['journal']['number'] ?? 'No disponible';

    // Enlace al artículo en DOAJ
    $doiUrl = 'https://doi.org/' . $doaj_doi; // Enlace directo al DOI, si está disponible
    $doajLink = 'https://doaj.org/article/' . $result['id']; // Enlace directo al artículo en DOAJ

    // Enlace que abre el modal
echo "<div class='status-box'>
        <a href='#' onclick='mostrarModalDoaj()' class='status-link'>DOAJ: OK</a>
      </div>";

// Modal oculto inicialmente
echo "
    <div id='modalDoaj' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.7); z-index: 1000;'>
        <div style='position:relative; margin: 10% auto; padding: 20px; background:white; border: 2px solid #007bff; border-radius: 10px; width: 600px; max-width: 90%; box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);'>
            <h5 style='color: #007bff;'>Detalles de la Revista DOAJ</h5>
            <p><strong>Título:</strong> " . htmlspecialchars($title) . "</p>
            <p><strong>Revista:</strong> " . htmlspecialchars($journalTitle) . "</p>
            <p><strong>Autores:</strong> " . htmlspecialchars($authorsList) . "</p>
            <p><strong>Número de autores:</strong> " . htmlspecialchars($numberOfAuthors) . "</p>
            <p><strong>Fecha de publicación:</strong> " . htmlspecialchars($publicationDate) . "</p>
            <p><strong>DOI:</strong> " . htmlspecialchars($doaj_doi) . "</p>
            <p><strong>ISSN:</strong> " . htmlspecialchars($doaj_issn) . "</p>
            <p><strong>eISSN:</strong> " . htmlspecialchars($doaj_eissn) . "</p>
            <p><strong>Volumen:</strong> " . htmlspecialchars($doaj_volume) . "</p>
            <p><strong>Número:</strong> " . htmlspecialchars($doaj_number) . "</p>
            <p><strong>Enlace DOI:</strong> <a href='" . htmlspecialchars($doiUrl) . "' target='_blank'>" . htmlspecialchars($doiUrl) . "</a></p>
            <p><strong>Enlace DOAJ:</strong> <a href='" . htmlspecialchars($doajLink) . "' target='_blank'>" . htmlspecialchars($doajLink) . "</a></p>
            <button onclick='cerrarModalDoaj()' style='background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;'>Cerrar</button>
        </div>
    </div>";

// Scripts para manejar el modal
echo "
    <script>
        function mostrarModalDoaj() {
            document.getElementById('modalDoaj').style.display = 'block';
        }
        function cerrarModalDoaj() {
            document.getElementById('modalDoaj').style.display = 'none';
        }
    </script>";

}  else {
echo "<div class='status-box'>
        Doaj: <span class='not-found'>N/A</span>
      </div>";    $est_doaj=0;
}
    }
    
   /*AQYU VA LO DE CORE*/

$est_core=0;
$apiKeyc = "JLOvPD53AXrqN1fRYV4lwMc7BIaiZp8H";
$tituloArticulo = $nombre_articulo; //"Evaluación del daño oxidativo y por metilación del ADN ";

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
            //echo "✅ Resultado encontrado:\n";
            $est_core=1;
            $article = $data['results'][0]; // Obtener el primer resultado (y único)

            // Guardar los datos en variables
            $core_titulo = $article['title'] ?? 'No disponible';
             // Obtener el ID del artículo para construir el enlace
                $articleId = $article['id'];
                $coreLink = "https://core.ac.uk/works/" . $articleId; // Enlace al artículo en CORE
            // DOI
            $doi = "No disponible";
            if (isset($article['identifiers']) && is_array($article['identifiers'])) {
                foreach ($article['identifiers'] as $identifier) {
                    if (is_array($identifier) && isset($identifier['type']) && $identifier['type'] === 'DOI' && isset($identifier['identifier'])) {
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
            //echo "Título: $core_titulo\n";
            // echo "DOI: $core_doi\n";
            // echo "Autores: $core_num_profesores autores: $core_profesores\n";
            // echo "Revista/Publicado por: $core_revista\n";
            // echo "ISSN: $core_issn\n";
            // echo "Año de publicación: $core_anio_publicacion\n";

            // Enlace o mensaje según resultados
            if (isset($data['results']) && count($data['results']) > 0) {
                echo "<div class='status-box'>";
                echo "<a href='#' onclick='mostrarModalCore()' class='status-link'>CORE: OK</a>";
                echo "</div>";
                
                  // Modal oculto inicialmente (adaptado para CORE)
                echo "
                    <div id='modalCore' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.7); z-index: 1000;'>
                        <div style='position:relative; margin: 10% auto; padding: 20px; background:white; border: 2px solid #007bff; border-radius: 10px; width: 600px; max-width: 90%; box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);'>
                            <h5 style='color: #007bff;'>Detalles del Artículo CORE</h5>
                            <p><strong>Título:</strong> " . htmlspecialchars($core_titulo) . "</p>
                            <p><strong>Autores:</strong> " . htmlspecialchars($core_profesores) . "</p>
                            <p><strong>Número de autores:</strong> " . htmlspecialchars($core_num_profesores) . "</p>
                            <p><strong>Revista:</strong> " . htmlspecialchars($core_revista) . "</p>
                            <p><strong>ISSN:</strong> " . htmlspecialchars($core_issn) . "</p>
                            <p><strong>Año de publicación:</strong> " . htmlspecialchars($core_anio_publicacion) . "</p>
                            <p><strong>DOI:</strong> " . htmlspecialchars($core_doi) . "</p>
                      <p><strong>Enlace CORE:</strong> <a href='" . htmlspecialchars($coreLink) . "' target='_blank'>" . htmlspecialchars($coreLink) . "</a></p>
                            <button onclick='cerrarModalCore()' style='background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;'>Cerrar</button>
                        </div>
                    </div>";

                // Scripts para manejar el modal (adaptado para CORE)
                echo "
                    <script>
                        function mostrarModalCore() {
                            document.getElementById('modalCore').style.display = 'block';
                        }
                        function cerrarModalCore() {
                            document.getElementById('modalCore').style.display = 'none';
                        }
                    </script>";


            
                
                
                
                
            } else {
                echo "<div class='status-box'>";
                echo "CORE: <span class='not-found'>N/A</span>";
                echo "</div>";
            }

        } else {
            $est_core=0;
            echo "<div class='status-box'>";
            echo "CORE: <span class='not-found'>N/A</span>";
            echo "</div>";
        }
    }
}

// Cerrar cURL
curl_close($ch);

//echo "estado core. ". $est_core;
    
/*AQUI TERMINA CORE*/    
    
    echo "<br><br>";
// ISSN para Publindex

// Construir la cláusula WHERE de la consulta SQL, codificando los valores para seguridad
$where = "txt_issn_p='" . urlencode($issn) . "' OR txt_issn_e='" . urlencode($issn) . "'";

// Limitar el número de resultados
$limit = 500;

// Crear un arreglo con los parámetros de la consulta
$queryParams = [
    '$where' => $where,
    '$limit' => $limit
];

// Construir la URL completa
$url = "https://www.datos.gov.co/resource/mwmn-inyg.json?" . http_build_query($queryParams);

$ch = curl_init($url);
// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128'); // Cambia esto a tu proxy si es necesario
//echo "Opción CURLOPT_RETURNTRANSFER configurada: true<br>"; // Indica que se devolverá la respuesta como cadena

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);
    
    
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtener el código de estado HTTP

if ($response === false) {
    // echo "Código de estado HTTP: " . $httpCode . "<br>"; // Muestra el código de estado
    echo "Error en la ejecución de cURL: " . curl_error($ch) . "<br>"; // Muestra el error si hay alguno
    
} else {
  
}

// Cerrar cURL
curl_close($ch);

// Decodificar la respuesta JSON
$data = json_decode($response, true);
//echo "Datos decodificados: " . print_r($data, true) . "<br>"; // Muestra los datos decodificados

$tipo_revista = "Internacional"; // Inicializa la variable tipo_revista

// Verificar si hay resultados
if (!empty($data)) {
    // Extraer el último año
    $ultimoAno = 0; // Inicializa la variable para almacenar el último año
    foreach ($data as $item) {
        $nroAno = intval($item['nro_ano']); // Asegúrate de que el año sea un entero
         $issn_impreso = $item['txt_issn_p'];
        if ($nroAno > $ultimoAno) {
            $ultimoAno = $nroAno; // Actualiza el último año si se encuentra uno más reciente
        }
    }
    // Verifica si se encontró un año válido
    if ($ultimoAno > 0) {
        // Ahora consulta nuevamente usando el último año encontrado
        $urlUltimoAno = "https://www.datos.gov.co/resource/mwmn-inyg.json?txt_issn_p=$issn_impreso&nro_ano=$ultimoAno&\$limit=10";
        //echo "ultimo año: ".$urlUltimoAno;
        // Iniciar cURL para la segunda consulta
        $ch = curl_init($urlUltimoAno);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128'); // Cambia esto a tu proxy si es necesario
        $responseUltimoAno = curl_exec($ch);
        curl_close($ch);

        // Decodificar la respuesta JSON para el último año
        $dataUltimoAno = json_decode($responseUltimoAno, true);
$tipo_revista = "Nacional"; // Inicializa la variable tipo_revista

        // Mostrar resultados en el modal
       
echo "<div class='status-box'>
        <a href='#' onclick='mostrarModalp()'>Publindex: " . ($dataUltimoAno ? "Nacional" : "Internacional") . "</a>
      </div>";        
        // Modal oculto inicialmente
      echo "
    <div id='resultadoModal' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.5); z-index: 1000;'>
        <div style='position:relative; margin: 10% auto; padding: 20px; background:white; width: 80%; max-width: 600px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.5);'>
                <h5>Resultados de Publindex - IBN $ultimoAno</h5><br>
                <div class='modal-body'>";

        foreach ($dataUltimoAno as $item) {
            // Obtener los datos, asegurando que se manejen los casos no disponibles
            $tipoClasificacion = ($item['id_clas_rev'] ?? 'No disponible');
            $issnImpreso = ($item['txt_issn_p'] ?? 'No disponible');
            $issnDigital = ($item['txt_issn_e'] ?? 'No disponible');
            $institucion = ($item['nme_inst_edit_1'] ?? 'No disponible');
            $nombreRevista = ($item['nme_revista_in'] ?? 'No disponible');

            // Mostrar la información de forma ordenada
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<strong>Nombre de la Revista:</strong> " . htmlspecialchars($nombreRevista) . "<br>";
            echo "<strong>ISSN:</strong> " . htmlspecialchars($issnImpreso) . ", " . htmlspecialchars($issnDigital) . "<br>";
            echo "<strong>Clasificación:</strong> " . htmlspecialchars($tipoClasificacion) . "<br>";
            echo "<strong>Institución:</strong> " . htmlspecialchars($institucion) . "<br>";
            echo "</div>"; //cierra  info
        }

        echo "</div>"; // modal-body
        echo "<button onclick='cerrarModal()'>Cerrar</button>";
        echo "</div>"; // modal-content
        echo "</div>"; // modal-dialog
        //echo "</div>"; // modalse elimina

        echo "
        <script>
            function mostrarModalp() {
                document.getElementById('resultadoModal').style.display = 'block';
            }
            function cerrarModal() {
                document.getElementById('resultadoModal').style.display = 'none';
            }
        </script>";
    } else {
echo "<div class='status-box'>
        Publindex Internacional ISSNxx: <strong>" . htmlspecialchars($issn) . "</strong>
      </div>";    }
} 
    
    else {
    // Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "productividad"); // Cambiar según tu configuración
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Lista de columnas a consultar
$columnas = ["issn_impr", "issne_pub", "issnx_pub"];
$row = null;

foreach ($columnas as $columna) {
    $query = "SELECT * FROM publindex_int_homolog WHERE $columna = ? ORDER BY grup";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $issn);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        break; // Salir del bucle si se encuentra un resultado
    }
}

// Si no se encontró ningún resultado, asignamos valores por defecto
if (!$row) {
    $row = [
        'issn_impr' => 'No encontrado',
        'issne_pub' => 'No encontrado',
        'calificacion_int' => 'No disponible',
        'sires_int' => 'No disponible',
        'nombre_revista_int' => 'No disponible',
        'vigencia_int' => 'No disponible'
    ];
}

// Cerrar conexión
$stmt->close();
$conexion->close();


    // Preparar valores
    $issnImpreso = $row['issn_impr'];
    $issnDigital = $row['issne_pub'];
    $tipoClasificacion = $row['calificacion_int'];
    $institucion = $row['sires_int'];
    $nombreRevista = $row['nombre_revista_int'];
    $vigencia_int = $row['vigencia_int'];

   // Texto dinámico para el enlace según el resultado
    $tipoPublindex = $tipo_revista == 'Nacional' ? 'Publindex Nacional' : 'Publindex Internacional';

    // Enlace para abrir el modal
  
   // Modal
echo "
<div class='status-box'>    
    <!-- Enlace que abre el modal -->
    <a href='#' onclick='mostrarModalp()' style='color: #007bff; text-decoration: underline; font-weight: bold;'>
        Publindex Internacional
    </a>
</div>

<!-- Modal -->
<div id='detalleModal1' style='display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: white; border-radius: 8px; border: 2px solid #007bff; box-shadow: 0 4px 15px rgba(0,0,0,0.3); width: 400px; max-width: 90%;'>
    <div style='padding: 15px; background-color: #007bff; color: white; border-top-left-radius: 8px; border-top-right-radius: 8px;'>
        <h5 style='margin: 0; font-size: 18px;'>Resultados de Publindex - ISSN</h5>
    </div>
    <div style='padding: 15px; font-size: 14px; line-height: 1.6;'>
        <div><strong>Nombre de la Revista:</strong> " . htmlspecialchars($nombreRevista) . "</div>
        <div><strong>ISSN Impreso:</strong> " . htmlspecialchars($issnImpreso) . "</div>
        <div><strong>ISSN Digital:</strong> " . htmlspecialchars($issnDigital) . "</div>
        <div><strong>Clasificación:</strong> " . htmlspecialchars($tipoClasificacion) . "</div>
        <div><strong>LISTA_SIR:</strong> " . htmlspecialchars($institucion) . "</div>
        <div><strong>Vigencia:</strong> " . htmlspecialchars($vigencia_int) . "</div>
    </div>
    <div style='padding: 15px; text-align: right; background-color: #f1f1f1; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;'>
        <button onclick='cerrarModalp()' style='padding: 8px 12px; background-color: #007bff; color: white; border: none; border-radius: 5px; font-size: 14px; cursor: pointer;'>Cerrar</button>
    </div>
</div>

<!-- JavaScript para manejar el modal -->
<script>
   function mostrarModalp() {
        document.getElementById('detalleModal1').style.display = 'block';
    }
    
    function cerrarModalp() {
        document.getElementById('detalleModal1').style.display = 'none';
    }
</script>";

}
  
    
    echo '</div>';//cierra derecho

echo '</div>'; //cierra wrapwer primera fila
    ?>
  
    
    <div class="parent-container d-flex justify-content-start"> <!-- Contenedor padre -->
<div class="container mt-1"> <!-- Contenedor para el acordeón -->
<?php
if ($count > 0) {
    echo "<div class='accordion' id='accordionExample'>";
    echo "<div class='accordion-item'>";
    echo "<h2 class='accordion-header' id='headingArticulo'>";

    // Botón del acordeón alineado a la izquierda
    echo "<button class='accordion-button' type='button' data-bs-toggle='collapse' data-bs-target='#collapseArticulo' aria-expanded='true' aria-controls='collapseArticulo'>";
    echo "Existe en la base de datos un artículo similar";
    echo "</button>";

    echo "</h2>";

    // Contenedor para los detalles del artículo
    echo "<div id='collapseArticulo' class='accordion-collapse collapse' aria-labelledby='headingArticulo' data-bs-parent='#accordionExample'>";
    echo "<div class='accordion-bodyb' style='max-width: 500px;'>"; // Ajusta el ancho máximo aquí

    // Consulta para obtener el nombre del artículo, ISSN, id_articulo y profesores relacionados
    $sqlDetalles = "
        SELECT a.id_articulo, a.nombre_articulo, a.issn, sp.fk_id_profesor, t.nombre_completo, s.numero_oficio
        FROM articulo a
        LEFT JOIN solicitud s ON a.id_articulo = s.fk_id_articulo
        LEFT JOIN solicitud_profesor sp ON sp.fk_id_solicitud = s.id_solicitud_articulo
        LEFT JOIN tercero t ON t.documento_tercero = sp.fk_id_profesor
        WHERE a.issn = ? AND a.nombre_articulo LIKE ?
    ";
    $stmtDetalles = $conn->prepare($sqlDetalles);
    $stmtDetalles->bind_param("ss", $issn, $nombre_articulo_like);
    $stmtDetalles->execute();
    $stmtDetalles->bind_result($idArticuloBd, $nombreArticuloBd, $issnBd, $fkIdProfesor, $name_profesor, $numero_oficio);

    // Variables para mostrar una vez
    $articuloEncontrado = false;

    // Mostrar detalles del artículo solo una vez
    while ($stmtDetalles->fetch()) {
        if (!$articuloEncontrado) {
            echo "<p><strong>Nombre Artículo:</strong> " . htmlspecialchars($nombreArticuloBd) . "<br>";
            echo "<strong>ISSN:</strong> " . htmlspecialchars($issnBd) . "<br>";
            echo "<strong>ID Artículo:</strong> " . htmlspecialchars($idArticuloBd) . "</p>";
            $articuloEncontrado = true; // Marcar que ya se mostró el artículo
        }
        echo "<strong>Profesor ID:</strong> " . htmlspecialchars($fkIdProfesor) . " - " . htmlspecialchars($name_profesor) . ". Oficio: " . htmlspecialchars($numero_oficio) . "<br>";
    }

    // Almacenar el id_articulo en una variable si se encontró
    $id_articulo_encontrado = $idArticuloBd; // Guardar el ID del artículo encontrado
    $stmtDetalles->close();

    echo "</div>"; // Cerrar el acordeón-body
    echo "</div>"; // Cerrar el acordeón-collapse
    echo "</div>"; // Cerrar el acordeón-item
    echo "</div>"; // Cerrar el acordeón
    
} else {
   echo "<div class='accordion-bodyb' style='max-width: 500px; margin-top: 10px;'>";
echo "Artículo nuevo.";
echo "</div>";
}
?>
</div> <!-- Cerrar contenedor -->
  


<script>
function toggleDetalles() {
    var detalles = document.getElementById('detallesArticulo');
    if (detalles.style.display === 'none' || detalles.style.display === '') {
        detalles.style.display = 'block';
    } else {
        detalles.style.display = 'none';
    }
}
</script>


<script>
// Función para mostrar u ocultar los detalles del artículo
function toggleDetalles() {
    var detalles = document.getElementById('detallesArticulo');
    if (detalles.style.display === 'none') {
        detalles.style.display = 'block';
    } else {
        detalles.style.display = 'none';
    }
}
</script>
    
    
   

             <div class="container mt-1" style="margin: 10px;">

  
        <?php

       
    // Insertar datos en la base de datos
  // Verificar si el artículo ya existe
if ($count > 0) {
 
// Si el artículo ya existe, realizar un UPDATE
$stmt = $conn->prepare("UPDATE articulo SET 
    issn = ?, 
    nombre_articulo = ?, 
    nombre_revista_scimago = ?, 
    titulo_scopus = ?, 
    pais_scimago = ?, 
    editorial_scimago = ?, 
    nombre_revista_miar = ?, 
    issn_scopus = ?, 
    autores_scopus = ?, 
    fecha_publicacion_scopus = ?, 
    doi_scopus = ?, 
    eissn_scopus = ?, 
    volumen_scopus = ?, 
    issue_scopus = ?, 
    scopus_revista = ?, 
    scopus_tipo_publicacion = ?, 
    doaj_articulo = ?, 
    doaj_revista = ?, 
    doaj_issn = ?, 
    doaj_eissn = ?, 
    doaj_doi = ?, 
    doaj_volumen = ?, 
    doaj_numero = ?, 
    doaj_enlace_doaj = ?, 
    publindex_año = ?, 
    publindex_revista = ?, 
    publindex_issn = ?, 
    publindex_eissn = ?, 
    publindex_editor = ?, 
    publindex_clasific = ?,
    core_revista = ?, 
    core_doi = ?, 
    core_issn= ?, 
    core_enlace = ?, 
    core_num_prof= ?, 
    core_anio_pub = ?, 
    core_autores = ?
    
    WHERE id_articulo = ?"); // Añadir la cláusula WHERE para actualizar el artículo correcto

// Usar bind_param para enlazar las variables
$stmt->bind_param("ssssssssssssssssssssssssssssssssssissi", // Cambia a 'ssi' para el id_articulo
    $issn, $nombre_articulo, 
    $nombreRevistaScimago, $titulo_scopus, $pais, $editorial, 
    $nombreRevistaMiar, 
    $entry['prism:issn'], $entry['dc:creator'], $entry['prism:coverDate'], 
    $entry['prism:doi'], $entry['prism:eIssn'], 
    $entry['prism:volume'], $entry['prism:issueIdentifier'], 
    $entry['prism:publicationName'], $entry['subtypeDescription'],
    $doaj_articulo, $doaj_revista, $doaj_issn, $doaj_eissn, 
    $doaj_doi, $doaj_volume, $doaj_number, $doajLink, 
    $ultimoAno, $nombreRevista, $issnImpreso, $issnDigital, 
    $institucion, $tipoClasificacion, 
       $core_revista, $core_doi,$core_issn, $coreLink,  $core_num_profesores,$core_anio_publicacion,$core_profesores,       
           
                  
                  
                  
    $id_articulo_encontrado); // Aquí se usa el id_articulo encontrado

// Ejecutar la consulta y verificar si se actualizó correctamente
if ($stmt->execute()) {
    echo "<div class='accordion-bodyb' >";
echo "Artículo actualizado exitosamente.";
echo "</div>";
} else {
    echo "Error al actualizar el artículo: " . $stmt->error;
}

// Cerrar la conexión
$stmt->close();
$conn->close();
} else {
    // Si no se encuentra, proceder a insertar
    $stmt = $conn->prepare("INSERT INTO articulo (
        issn,
        nombre_articulo, 
        nombre_revista_scimago, 
        titulo_scopus, 
        pais_scimago, 
        editorial_scimago, 
        nombre_revista_miar, 
        issn_scopus, 
        autores_scopus, 
        fecha_publicacion_scopus, 
        doi_scopus, 
        eissn_scopus,
        volumen_scopus,
        issue_scopus, 
        scopus_revista,
        scopus_tipo_publicacion,
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
        publindex_clasific,
         core_revista, 
    core_doi, 
    core_issn, 
    core_enlace, 
    core_num_prof, 
    core_anio_pub, 
    core_autores ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Usar bind_param para enlazar las variables
    $stmt->bind_param("ssssssssssssssssssssssssssssssssssiss",
                      
        $issn, $nombre_articulo, 
        $nombreRevistaScimago, $titulo_scopus, $pais, $editorial, 
        $nombreRevistaMiar, 
        $entry['prism:issn'], $entry['dc:creator'], $entry['prism:coverDate'], 
        $entry['prism:doi'], $entry['prism:eIssn'], 
        $entry['prism:volume'], $entry['prism:issueIdentifier'], 
        $entry['prism:publicationName'], $entry['subtypeDescription'],
        $doaj_articulo, $doaj_revista, $doaj_issn, $doaj_eissn, 
        $doaj_doi, $doaj_volume, $doaj_number, $doajLink, 
        $ultimoAno, $nombreRevista, $issnImpreso, $issnDigital, 
        $institucion, $tipoClasificacion, 
       $core_revista, $core_doi,$core_issn, $coreLink,  $core_num_profesores,$core_anio_publicacion,$core_profesores);

    // Ejecutar la consulta y verificar si se insertó correctamente
    if ($stmt->execute()) {
        $id_articulo = $conn->insert_id; // Solo se usa si se inserta un nuevo artículo
        $id_articulo_encontrado=$id_articulo;
        
       echo "<div class='accordion-bodyb' style='max-width: 500px; margin-top: 10px;'>";
echo "Artículo insertado exitosamente.";
echo "</div>"; // Agrega este mensaje
    } else {
        echo "Error al insertar el artículo: " . $stmt->error;
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
}
  echo "</div> ";echo "</div> ";
}

?>
<script>
let nombreCompleto = '';
let nombreDepto = '';
let nombreFac = '';
     let trdFac = ''; // Nueva variable global
</script>
<!-- Formulario para que el usuario ingrese más datos -->
<div class="container mt-0 pb-4 custom-container">
          <form method="post" action="guardar_solicitud.php" style="border: 2px solid #bbb; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
    <h3 style="text-align: center; font-weight: bold; color: #333; margin-bottom: 20px;">Solicitud  Productividad</h3>
    
<?php
// Obtener el año y el mes actuales en el formato deseado
$identificador_base = date('Y_m');
?>
     <!-- Campo oculto para el ID del artículo -->
      <input type="hidden" name="fk_id_articulo" value="<?php echo $id_articulo_encontrado; ?>">

<div class="row mb-3 align-items-end">
  <div class="col-md-4">
    <label for="identificador_base" class="form-label fw-bold">Identificador:</label>
    <div class="input-group">
      <input type="text" class="form-control" id="identificador_base" name="identificador_base" 
             value="<?php echo $identificador_base; ?>" maxlength="7" pattern="\d{4}_\d{2}" placeholder="Año_Mes" required>
      <select class="form-select form-select-sm" id="numero_envio" name="numero_envio" style="width: 50px;" required>
        <?php for ($i = 1; $i <= 9; $i++): ?>
          <option value="<?php echo $i; ?>" <?php echo $i == 1 ? 'selected' : ''; ?>>
            <?php echo $i; ?>
          </option>
        <?php endfor; ?>
      </select>
    </div>
  </div>

  <div class="col-md-4">
    <label for="numero_profesores" class="form-label fw-bold"># Profesores solicitantes:</label>
    <input type="number" id="numero_profesores" name="numero_profesores" class="form-control" required min="1">
  </div>

  <div class="col-md-4">
    <label for="inputTrdFac" class="form-label fw-bold">Número de oficio:</label>
    <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control" required>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-12">
    <label class="form-label fw-bold mb-2">Estados de la Revista y Alertas:</label>
    
    <div class="d-flex flex-wrap align-items-center rounded p-2" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
      
      <div class="form-check form-check-inline me-4">
        <input class="form-check-input" type="checkbox" name="est_scimago" id="est_scimago" value="1" <?php echo ($est_scimago === 1) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="est_scimago">SCIMAGO</label>
      </div>
      
      <div class="form-check form-check-inline me-4">
        <input class="form-check-input" type="checkbox" name="est_doaj" id="est_doaj" value="1" <?php echo ($est_doaj === 1) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="est_doaj">DOAJ</label>
      </div>
      
      <div class="form-check form-check-inline me-4">
        <input class="form-check-input" type="checkbox" name="est_scopus" id="est_scopus" value="1" <?php echo ($est_scopus === 1) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="est_scopus">SCOPUS</label>
      </div>
      
      <div class="form-check form-check-inline me-4">
        <input class="form-check-input" type="checkbox" name="est_miar" id="est_miar" value="1" <?php echo ($est_miar === 1) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="est_miar">MIAR</label>
      </div>
      
      <div class="form-check form-check-inline me-4">
        <input class="form-check-input" type="checkbox" name="est_core" id="est_core" value="1" <?php echo ($est_core === 1) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="est_core">CORE</label>
      </div>

      <div class="vr mx-2 d-none d-md-block" style="height: 24px; background-color: #dee2e6;"></div>

      <div class="form-check form-check-inline ms-md-3 mt-2 mt-md-0">
        <input class="form-check-input" type="checkbox" name="mdpi_pred" id="mdpi_pred" value="1">
        <label class="form-check-label text-danger fw-bold" for="mdpi_pred">
            ⚠️ Revista MDPI o Predadora
        </label>
      </div>

    </div>
  </div>
</div>

<!-- Contenedor para documentos -->
<div id="contenedor_documentos" class="mb-3">
  <!-- Aquí se generarán los campos de documento -->
</div>

   

      <!-- Campos ocultos -->
      <input type="hidden" name="fk_id_articulo" value="<?php echo $id_articulo_encontrado; ?>">
      <input type="hidden" id="inputNombreCompleto" name="nombre_completo">
      <input type="hidden" id="inputDepto" name="departamento">
      <input type="hidden" id="inputFac" name="facultad">
      <input type="hidden" id="identificador_solicitud" name="identificador_solicitud" value="">
   
    
    <div class="row align-items-center mb-3">
  
</div>
 
    <div class="row align-items-center mb-3">
    <!-- Título del Artículo -->
    <div class="col-md-4">
        <label for="titulo_articulo" class="form-label fw-bold">Título del Artículo:</label>
        <input type="text" class="form-control form-control-sm" id="titulo_articulo" name="titulo_articulo" 
               value="<?php 
                  echo isset($entry['dc:title']) ? htmlspecialchars($entry['dc:title']) : 
                       (isset($title) ? htmlspecialchars($title) : htmlspecialchars($nombre_articulo)); 
               ?>" required>
    </div>
<!-- Volumen -->
 <div class="col-sm-1">
    <label for="volumen" class="form-label fw-bold">Volumen:</label>
    <input type="text" id="volumen" name="volumen" class="form-control form-control-sm" 
           value="<?php 
               if (isset($entry['prism:volume']) && !empty($entry['prism:volume'])) {
                   echo htmlspecialchars($entry['prism:volume']);
               } elseif (isset($doaj_volume) && !empty($doaj_volume)) {
                   echo htmlspecialchars($doaj_volume);
               } else {
                   echo '';
               }
           ?>" 
           onfocus="checkVolume()" onblur="checkVolume()">
</div>


    <div class="col-sm-1">
    <label for="numero_r" class="form-label fw-bold">Número:</label>
    <input type="text" id="numero_r" name="numero_r" class="form-control form-control-sm" 
           value="<?php 
               if (isset($entry['prism:issueIdentifier']) && !empty($entry['prism:issueIdentifier'])) {
                   echo htmlspecialchars($entry['prism:issueIdentifier']);
               } elseif (isset($doaj_number) && !empty($doaj_number)) {
                   echo htmlspecialchars($doaj_number);
               } else {
                   echo '';
               }
           ?>" 
           onfocus="checkNumero()" onblur="checkNumero()">
</div>
    <!-- Año de Publicación -->
<div class="col-sm-1">
    <label for="ano_publicacion" class="form-label fw-bold">Año:</label>
    <input type="text" id="ano_publicacion" name="ano_publicacion" class="form-control form-control-sm" 
           value="<?php 
               if (isset($entry['prism:coverDate']) && !empty($entry['prism:coverDate'])) {
                   echo htmlspecialchars(date('Y', strtotime($entry['prism:coverDate'])));
               } elseif (isset($publicationDate) && !empty($publicationDate)) {
                   echo htmlspecialchars(date('Y', strtotime($publicationDate)));
               } elseif (!empty($core_anio_publicacion)) { 
                   echo htmlspecialchars($core_anio_publicacion);
               } else {
                   echo ''; 
               }
           ?>" 
           onfocus="checkAno()" onblur="checkAno()">
</div>

    

    <!-- Número de Autores -->
<!-- Número de Autores -->
<div class="col-sm-1">
    <label for="numero_autores" class="form-label fw-bold"># Autores:</label>
    <input type="number" id="numero_autores" name="numero_autores" class="form-control form-control-sm" 
           value="<?php 
               if (isset($numberOfAuthors) && !empty($numberOfAuthors)) {
                   echo htmlspecialchars($numberOfAuthors);
               } elseif (!empty($core_num_profesores)) { 
                   echo htmlspecialchars($core_num_profesores);
               } else {
                   echo '';
               }
           ?>" 
           required onfocus="checkNumeroAutores()" onblur="checkNumeroAutores()">
</div>

    <!-- Tipo de Artículo -->
    <div class="col-sm-2">
   <label for="tipo_articulo" class="form-label fw-bold">Tipo artículo:</label>
        <select id="tipo_articulo" name="tipo_articulo" class="form-select form-select-sm" required onfocus="checkTipoArticulo()" onblur="checkTipoArticulo()">
            <option value="" selected>Selecciona un tipo de artículo</option>
            <option value="FULL PAPER" <?php 
                echo (isset($entry['subtypeDescription']) && ($entry['subtypeDescription'] == 'Article' || empty($entry['subtypeDescription'])) ? 'selected' : ''); 
            ?>>FULL PAPER</option>
            <option value="ARTICULO CORTO" <?php 
                echo (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'ShortArticle' ? 'selected' : ''); 
            ?>>ARTÍCULO CORTO</option>
            <option value="EDITORIALES" <?php 
                echo (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'Editorial' ? 'selected' : ''); 
            ?>>EDITORIALES</option>
            <option value="REVISION DE TEMA" <?php 
                echo (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'Review' ? 'selected' : ''); 
            ?>>REVISION DE TEMA</option>
            <option value="REPORTE DE CASO" <?php 
                echo (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'CaseReport' ? 'selected' : ''); 
            ?>>REPORTE DE CASO</option>
        </select>

</div>


    <!-- DOI -->
 <div class="col-sm-2">
    <label for="doi" class="form-label fw-bold">DOI:</label>
    <input type="text" id="doi" name="doi" class="form-control form-control-sm" value="<?php 
        if (isset($entry['prism:doi']) && !empty($entry['prism:doi'])) {
            echo htmlspecialchars($entry['prism:doi']);
        } elseif (isset($doiUrl) && !empty($doiUrl)) {
            echo htmlspecialchars($doiUrl);
        } elseif (!empty($core_doi)) {
            echo htmlspecialchars($core_doi);
        } else {
            echo '';
        }
    ?>">
</div>
</div>

<div class="row align-items-center mb-4">
    <div class="col-sm-4">
        <label for="nombre_revista" class="form-label fw-bold" style="font-size: 1.1rem;">Nombre de la Revista:</label>
        <input type="text" id="nombre_revista" name="nombre_revista" class="form-control" value="<?php 
            if (isset($entry['prism:publicationName']) && !empty($entry['prism:publicationName'])) {
                echo htmlspecialchars($entry['prism:publicationName']);
            } elseif (isset($journalTitle) && !empty($journalTitle)) {
                echo htmlspecialchars($journalTitle);
            } elseif (isset($nombreRevista) && !empty($nombreRevista)) {
                echo htmlspecialchars($nombreRevista);
            } elseif (isset($nombreRevistaScimago) && !empty($nombreRevistaScimago)) {
                echo htmlspecialchars($nombreRevistaScimago);
            } elseif (isset($nombreRevistaMiar) && !empty($nombreRevistaMiar)) {
                echo htmlspecialchars($nombreRevistaMiar);
            } else {
                echo '';
            }
        ?>">
    </div>
    
    <div class="col-md-1">
        <label for="issn" class="form-label fw-bold">ISSN:</label>
        <input type="text" class="form-control" id="issn" name="issn" 
               value="<?php 
                  echo isset($entry['prism:issn']) ? htmlspecialchars($entry['prism:issn']) : 
                       (isset($doaj_issn) ? htmlspecialchars($doaj_issn) : 
                       (isset($issnImpreso) ? htmlspecialchars($issnImpreso) : 
                       (isset($issnBd) ? htmlspecialchars($issnBd) : htmlspecialchars($issn))));
               ?>">
    </div>

    <div class="col-md-1">
        <label for="eissn" class="form-label fw-bold">eISSN:</label>
        <input type="text" class="form-control" id="eissn" name="eissn" 
               value="<?php 
                  echo isset($entry['prism:eIssn']) ? htmlspecialchars($entry['prism:eIssn']) : 
                       (isset($doaj_eissn) ? htmlspecialchars($doaj_eissn) : 
                       (isset($issnDigital) ? htmlspecialchars($issnDigital) : ''));
               ?>">
    </div>

  <div class="col-sm-2">
    <label for="tipo_publindex" class="form-label fw-bold" style="font-size: 0.9rem;">Tipo de Publindex:</label>
    <select id="tipo_publindex" name="tipo_publindex" class="form-select" required onchange="checkSelection(this)">
        <option value="">Seleccione un tipo</option>
        <option value="A1" <?php echo (isset($tipoClasificacion) && $tipoClasificacion === 'A1') ? 'selected' : ''; ?>>A1</option>
        <option value="A2" <?php echo (isset($tipoClasificacion) && $tipoClasificacion === 'A2') ? 'selected' : ''; ?>>A2</option>
        <option value="B" <?php echo (isset($tipoClasificacion) && $tipoClasificacion === 'B') ? 'selected' : ''; ?>>B</option>
        <option value="C" <?php echo (isset($tipoClasificacion) && $tipoClasificacion === 'C') ? 'selected' : ''; ?>>C</option>
    </select>
</div>

    <div class="col-sm-2">
        <label for="tipo_revista" class="form-label fw-bold">Tipo de Revista:</label>
        <select id="tipo_revista" name="tipo_revista" class="form-select" required>
            <option value="Nacional" <?php echo ($tipo_revista == "Nacional") ? "selected" : ""; ?>>Nacional</option>
            <option value="Internacional" <?php echo ($tipo_revista == "Internacional") ? "selected" : ""; ?>>Internacional</option>
        </select>
    </div>

    <div class="col-sm-2">
        <label for="puntaje" class="form-label fw-bold">Puntaje:</label>
        <input type="number" id="puntaje" name="puntaje" class="form-control" required readonly>
    </div>
</div>

<div class="text-end">
    <input type="submit" value="Enviar" class="btn btn-primary">
    <div class="text-end">
    <a href="MENU_INI.php" class="btn btn-secondary">Regresar a Menú Productividad</a>
</div>
</div>
             
</form>

   </div>
<script>
    const numeroProfesoresInput = document.getElementById('numero_profesores');
    const contenedorDocumentos = document.getElementById('contenedor_documentos');
    const numeroOficioInput = document.getElementById('inputTrdFac'); // Campo del número de oficio

    numeroProfesoresInput.addEventListener('input', () => {
        contenedorDocumentos.innerHTML = ''; // Limpiar el contenedor cada vez que se cambie el número

        const cantidad = parseInt(numeroProfesoresInput.value);
        if (isNaN(cantidad) || cantidad < 1) return;

        for (let i = 1; i <= cantidad; i++) {
            // Contenedor para cada conjunto de campos en una sola fila
            const fieldContainer = document.createElement('div');
            fieldContainer.classList.add('row', 'align-items-center', 'mb-2');

            // Etiqueta para el documento
            const label = document.createElement('label');
            label.textContent = `Documento solicitante ${i}:`;
            label.setAttribute('for', `documento_${i}`);
            label.classList.add('col-sm-3', 'col-form-label', 'fw-bold');

            // Campo de entrada para el documento
            const input = document.createElement('input');
            input.type = 'text';
            input.id = `documento_${i}`;
            input.name = `documento_${i}`;
            input.required = true;
            input.classList.add('form-control', 'col-sm-3', 'me-3'); // Tamaño reducido
            input.style.maxWidth = '150px'; // Limitar el ancho del campo
            input.addEventListener('blur', () => buscarDatos(input, i));

            // Contenedor para mostrar los datos (junto al input)
            const datosContainer = document.createElement('div');
            datosContainer.id = `datos_${i}`;
            datosContainer.classList.add('col', 'datos-container', 'text-muted', 'ps-2');

            // Ensamblar el grupo en el contenedor principal
            fieldContainer.appendChild(label);
            fieldContainer.appendChild(input);
            fieldContainer.appendChild(datosContainer);
            contenedorDocumentos.appendChild(fieldContainer);
        }
    });

    function buscarDatos(input, index) {
        const documento = input.value;
        if (documento === '') return;

        console.log(`Buscando datos para el documento: ${documento}`);

        fetch(`obtener_datos_profesor.php?documento=${documento}`)
            .then(response => response.text())
            .then(text => {
                console.log(text);
                try {
                    const data = JSON.parse(text);
                    const datosContainer = document.getElementById(`datos_${index}`);

                    if (data.error) {
                        datosContainer.textContent = data.error;
                    } else {
                        // Asignar los datos encontrados al contenedor
                        datosContainer.textContent = `Nombre: ${data.nombre_completo}, Departamento: ${data.nombre_depto},  ${data.nombre_fac}`;

                        // Si es el primer profesor, prellenar el número de oficio
                        if (index === 1) {
                            if (data.numero_oficio) {
                                numeroOficioInput.value = data.numero_oficio; // Prellenar el campo de número de oficio
                                console.log(`Número de oficio prellenado: ${data.numero_oficio}`); // Agregado para verificar
                            } else {
                                console.warn('Número de oficio no encontrado en los datos del profesor.');
                                numeroOficioInput.value = ''; // Limpiar si no hay dato
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error al analizar JSON:', e);
                    console.log('Respuesta no válida:', text);
                }
            })
            .catch(error => {
                console.error('Error en la solicitud fetch:', error);
            });
    }
</script>
<script>
    // Concatenar la parte de fecha y el número de envío cuando el usuario edite
    function updateIdentificadorSolicitud() {
        let base = document.getElementById('identificador_base').value;
        let envio = document.getElementById('numero_envio').value;
        
        // Concatenar y establecer el valor completo en el campo oculto
        document.getElementById('identificador_solicitud').value = base + "_" + envio;
    }

    // Actualizar identificador completo al modificar cualquiera de los campos
    document.getElementById('identificador_base').addEventListener('input', updateIdentificadorSolicitud);
    document.getElementById('numero_envio').addEventListener('change', updateIdentificadorSolicitud);
    
    // Inicializar el campo oculto al cargar la página
    updateIdentificadorSolicitud();
</script>
    <script>
    function checkSelection(selectElement) {
        if (selectElement.value === "") {
            selectElement.classList.add('alerta-select');
        } else {
            selectElement.classList.remove('alerta-select');
        }
    }

    // Llamar a la función al cargar la página para mantener el estado
    document.addEventListener('DOMContentLoaded', function() {
        checkSelection(document.getElementById('tipo_publindex'));
    });
</script>
                 <script>
    function checkVolume() {
        const volumenInput = document.getElementById('volumen');
        if (volumenInput.value.trim() === "") {
            volumenInput.classList.add('alerta-input');
        } else {
            volumenInput.classList.remove('alerta-input');
        }
    }

    // Llamar a la función al cargar la página para mantener el estado
    document.addEventListener('DOMContentLoaded', function() {
        checkVolume();
    });
</script>
                 <script>
    function checkNumero() {
        const numeroInput = document.getElementById('numero_r');
        if (numeroInput.value.trim() === "") {
            numeroInput.classList.add('alerta-input');
        } else {
            numeroInput.classList.remove('alerta-input');
        }
    }

    // Llamar a la función al cargar la página para mantener el estado
    document.addEventListener('DOMContentLoaded', function() {
        checkNumero();
    });
</script>
             <script>
    function checkAno() {
        const anoInput = document.getElementById('ano_publicacion');
        if (anoInput.value.trim() === "") {
            anoInput.classList.add('alerta-input');
        } else {
            anoInput.classList.remove('alerta-input');
        }
    }

    // Llamar a la función al cargar la página para mantener el estado
    document.addEventListener('DOMContentLoaded', function() {
        checkAno();
    });
</script>    
                 <script>
    function checkNumeroAutores() {
        const numeroAutoresInput = document.getElementById('numero_autores');
        if (numeroAutoresInput.value.trim() === "") {
            numeroAutoresInput.classList.add('alerta-input');
        } else {
            numeroAutoresInput.classList.remove('alerta-input');
        }
    }

    // Llamar a la función al cargar la página para mantener el estado
    document.addEventListener('DOMContentLoaded', function() {
        checkNumeroAutores();
    });
</script>
        
<script>
    function checkTipoArticulo() {
        const tipoArticuloSelect = document.getElementById('tipo_articulo');
        if (tipoArticuloSelect.value === "") {
            tipoArticuloSelect.classList.add('alerta-select');
        } else {
            tipoArticuloSelect.classList.remove('alerta-select');
        }
    }

    // Llamar a la función al cargar la página para mantener el estado
    document.addEventListener('DOMContentLoaded', function() {
        checkTipoArticulo();
    });
</script>
        </div>

             <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script></div></div>
    </body>
</html>