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
    const tipoArticulo = document.getElementById("tipo_articulo").value;
    const tipoPublindex = document.getElementById("tipo_publindex").value;
    const numeroAutores = parseInt(document.getElementById("numero_autores").value);
    const valoresBase = { "A1": 15, "A2": 12, "B": 8, "C": 3 };
    if (!valoresBase[tipoPublindex] || isNaN(numeroAutores) || numeroAutores <= 0) {
        document.getElementById("puntaje").value = 0;
        return;
    }
    let puntajeBase = valoresBase[tipoPublindex];
    let puntaje = 0;
    if (tipoArticulo === "FULL PAPER") {
        if (numeroAutores <= 3)      puntaje = puntajeBase;
        else if (numeroAutores <= 5) puntaje = puntajeBase / 2;
        else                         puntaje = puntajeBase / (numeroAutores/2);
    } else if (tipoArticulo === "ARTICULO CORTO") {
        let factor = 0.6;
        if (numeroAutores <= 3)      puntaje = puntajeBase * factor;
        else if (numeroAutores <= 5) puntaje = (puntajeBase * factor) / 2;
        else                         puntaje = (puntajeBase * factor) / (numeroAutores/2);
    } else if (tipoArticulo === "REVISION DE TEMA" || tipoArticulo === "EDITORIALES" || tipoArticulo === "REPORTE DE CASO") {
        let factor = 0.3;
        if (numeroAutores <= 3)      puntaje = puntajeBase * factor;
        else if (numeroAutores <= 5) puntaje = (puntajeBase * factor) / 2;
        else                         puntaje = (puntajeBase * factor) / (numeroAutores/2);
    }
    document.getElementById("puntaje").value = puntaje.toFixed(2);
}
        window.addEventListener('DOMContentLoaded', (event) => {
            document.getElementById("tipo_articulo").addEventListener("change", calcularPuntaje);
            document.getElementById("tipo_publindex").addEventListener("change", calcularPuntaje);
            document.getElementById("numero_autores").addEventListener("input", calcularPuntaje);
            calcularPuntaje();
        });
    </script>
    <style>
        .datos-container { margin-top: 5px; font-style: italic; color: #555; }
        .accordion-bodyb {
            max-width: auto; padding: 14px;
            background-color: #e0ffe0; border: 1px solid #4CAF50;
            color: #4CAF50; border-radius: 5px;
            margin-top: 0px; margin-left: 10px;
        }
        .status-alert { color: red; font-weight: bold; }
        .status-box {
            display: inline-block; padding: 8px 15px;
            background-color: #f7f9fc; border-radius: 10px;
            box-shadow: 0px 2px 4px rgba(0,0,0,0.1);
            margin-right: 10px; font-family: Arial, sans-serif; font-size: 14px;
        }
        .not-found { color: red !important; font-weight: bold; }
        .parent-container { display: inline-flex; justify-content: flex-start; }
        .container {
            margin: 0; padding: 10px; width: 100vw;
            max-width: 100%; overflow: auto;
        }
        .wrapper { display: flex; }
        .box { flex: 1; width: 100%; padding: 10px; border: 1px solid #ccc; margin: 10px; }
        .left  { background-color: ghostwhite; }
        .right { background-color: floralwhite; }
        .custom-container { width: 100%; max-width: none; margin: 0 auto; }
        body { padding-bottom: 20px; }
        .alerta-select, .alerta-input { background-color: #ffecb3; }
    </style>
</head>
<body>

<div class="container" style="margin: 10px;">
    <h3 class="text-primary mb-4">Artículos Indexados:</h3>

    <?php

// Conexión a la base de datos
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "productividad";
$est_scopus = '';
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// ── Función de traducción lazy (solo se llama si DOAJ falla en intento 1) ──
function traducirArticulo($texto) {
    try {
        $trans  = new GoogleTranslate();
        $result = $trans->translate('en', 'es', $texto);
        $result = str_replace([",",".",";",":","?","!","(",")",'"',"'"], "", $result);
        $stopwords = ["un","una","unos","unas","el","la","los","las","de","por","en"];
        $palabras  = explode(" ", $result);
        $filtradas = array_filter($palabras, fn($p) => !in_array(strtolower($p), $stopwords));
        return implode(" ", $filtradas);
    } catch (Exception $e) {
        error_log("GoogleTranslate error: " . $e->getMessage());
        return $texto; // fallback: devuelve el original
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $issn            = urlencode(trim($_POST['issn']));
    $nombre_articulo = trim($_POST['nombre_articulo']);
    $nombre_articulo_esp = null; // se traducirá solo si hace falta

    echo '<div class="wrapper">';

    // ── División izquierda ──
    echo '<div class="box left">';
    echo "<div style='border: 1px solid #ccc; padding: 10px;'>";
    echo "<strong>Artículo Consultado:</strong> " . htmlspecialchars($nombre_articulo) . "<br>";
    echo "<strong>ISSN Consultado:</strong> "     . htmlspecialchars($issn) . "<br>";
    echo "</div>";

    // Patrón LIKE para verificar duplicado
    $nombre_articulo_modificado = preg_replace('/[^\w\s]/u', '%', $nombre_articulo);
    $nombre_articulo_modificado = preg_replace('/%+/', '%', $nombre_articulo_modificado);
    $nombre_articulo_like = '%' . $nombre_articulo_modificado . '%';

    $sql  = "SELECT COUNT(*) FROM articulo WHERE issn = ? AND nombre_articulo LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $issn, $nombre_articulo_like);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    ?>

    <?php
    echo '</div>'; // cierra .box.left

    // ── División derecha ──
    echo '<div class="box right">';

    $urlScimago = "https://www.scimagojr.com/journalsearch.php?q=" . $issn;
    $urlMiar    = "https://miar.ub.edu/issn/" . $issn;

    // ══════════════════════════════════════════════
    // SCIMAGO — con fix de encoding Brotli
    // ══════════════════════════════════════════════
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            $urlScimago);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_PROXY,          'http://proxy.unicauca.edu.co:3128');
    curl_setopt($ch, CURLOPT_TIMEOUT,        30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_ENCODING,       'gzip');  // FIX: evita Brotli
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' .
        '(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: es-CO,es;q=0.9,en;q=0.8',
        'Accept-Encoding: gzip, deflate',  // FIX: sin "br"
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Referer: https://www.google.com/',
        'Cache-Control: max-age=0',
    ]);

    $contenidoScimago = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "<div class='status-box'>SCImago: <span class='status-alert'>Error conexión</span></div>";
        curl_close($ch);
    } else {
        curl_close($ch);
        $dom = new DOMDocument();
        @$dom->loadHTML($contenidoScimago);
        $xpath             = new DOMXPath($dom);
        $resultadosScimago = $xpath->query("//a[contains(@href, 'journalsearch.php')]");

        $nombreRevistaScimago = 'No disponible';
        $pais      = 'No disponible';
        $editorial = 'No disponible';

        if ($resultadosScimago->length > 0) {
            $est_scimago = 1;
            $resultado   = $resultadosScimago[0];

            $jrnlNode = $xpath->query(".//span[@class='jrnlname']", $resultado)->item(0);
            $nombreRevistaScimago = $jrnlNode ? trim($jrnlNode->nodeValue) : 'No disponible';

            $spanPais = $xpath->query(".//span[contains(@class,'country')]", $resultado)->item(0);
            $pais     = $spanPais ? trim($spanPais->nodeValue) : 'No disponible';

            if ($pais === 'No disponible') {
                $textoLimpio = preg_replace('/\s+/', ' ', trim($resultado->textContent));
                $textoLimpio = trim(str_replace($nombreRevistaScimago, '', $textoLimpio));
                $partes      = array_values(array_filter(explode('  ', $textoLimpio)));
                $pais        = isset($partes[0]) ? trim($partes[0]) : 'No disponible';
                $editorial   = isset($partes[1]) ? trim($partes[1]) : 'No disponible';
            }

            echo "<div class='status-box'><a href='#' onclick='mostrarModal()'>SCImago: Ok</a></div>";
            echo "
            <div id='modalScimago' style='display:none; position:fixed; top:0; left:0; width:100%;
                 height:100%; background:rgba(0,0,0,0.5); z-index:1000;'>
                <div style='position:relative; margin:10% auto; padding:20px;
                            background:white; width:600px; border-radius:8px;
                            box-shadow:0 4px 20px rgba(0,0,0,0.2);'>
                    <h5>Detalles de la Revista — SCImago</h5>
                    <p><strong>ISSN:</strong> "     . htmlspecialchars($issn) . "</p>
                    <p><strong>Título:</strong> "   . htmlspecialchars($nombreRevistaScimago) . "</p>
                    <p><strong>País:</strong> "     . htmlspecialchars($pais) . "</p>
                    <p><strong>Editorial:</strong> ". htmlspecialchars($editorial) . "</p>
                    <p><a href='https://www.scimagojr.com/journalsearch.php?q="
                        . urlencode($issn) . "' target='_blank'>Ver en SCImago ↗</a></p>
                    <button onclick='cerrarModalsc()'>Cerrar</button>
                </div>
            </div>";
            echo "<script>
                function mostrarModal()  { document.getElementById('modalScimago').style.display='block'; }
                function cerrarModalsc() { document.getElementById('modalScimago').style.display='none';  }
            </script>";
        } else {
            echo "<div class='status-box'>SCImago: <span class='status-alert'>N/A</span></div>";
            $est_scimago = 0;
        }
    }

    // ══════════════════════════════════════════════
    // MIAR
    // ══════════════════════════════════════════════
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            $urlMiar);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_PROXY,          'http://proxy.unicauca.edu.co:3128');
    curl_setopt($ch, CURLOPT_TIMEOUT,        30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $contenidoMiar = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Error al obtener la página MIAR: " . curl_error($ch);
        curl_close($ch);
    }
    curl_close($ch);

    $domMiar      = new DOMDocument();
    @$domMiar->loadHTML($contenidoMiar);
    $xpathMiar    = new DOMXPath($domMiar);
    $resultadosMiar = $xpathMiar->query("//*[@id='divtxt_Revista_0']");
    $nombreRevistaMiar = null;

    if ($resultadosMiar->length > 0) {
        $est_miar = 1;
        $resultado = $resultadosMiar[0];
        $nombreRevistaMiar = trim($resultado->textContent);
        echo "<div class='status-box'><a href='#' onclick='mostrarModalMiar()'>MIAR: Ok</a></div>";
        echo "
        <div id='modalMiar' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;'>
            <div style='position:relative; margin:10% auto; padding:20px; background:white; width:600px;'>
                <h5>Detalles de la Revista en MIAR</h5>
                <div class='modal-body'>
                    <div style='border:1px solid #ccc; padding:10px; margin-bottom:10px;'>
                        <strong>Título:</strong> " . htmlspecialchars($nombreRevistaMiar) . "<br>
                        <a href='https://miar.ub.edu/issn/" . urlencode($issn) . "' target='_blank'>Ver en MIAR</a>
                    </div>
                </div>
                <button onclick='cerrarModalMiar()'>Cerrar</button>
            </div>
        </div>";
        echo "<script>
            function mostrarModalMiar() { document.getElementById('modalMiar').style.display='block'; }
            function cerrarModalMiar()  { document.getElementById('modalMiar').style.display='none';  }
        </script>";
    } else {
        echo "<div class='status-box'>MIAR: <span class='status-alert'>N/A</span></div>";
        $est_miar = 0;
    }

    // ══════════════════════════════════════════════
    // SCOPUS
    // ══════════════════════════════════════════════
    $apiKey    = '803bbe28a496ac467be562f4f18d3d91';
    $titulo    = $nombre_articulo;
    $titulo    = str_replace([':', '(', ')', '–', ','], ' ', $titulo);
    $titulo    = preg_replace('/\s+/', ' ', $titulo);
    $titulo    = trim($titulo);
    $titulo    = urlencode($titulo);
    $urlScopus = 'https://api.elsevier.com/content/search/scopus';

    function buscarEnScopus($query) {
        global $apiKey, $urlScopus;
        $queryParams = ['query' => $query, 'apiKey' => $apiKey, 'httpAccept' => 'application/json'];
        $url = $urlScopus . '?' . http_build_query($queryParams);
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY,          'http://proxy.unicauca.edu.co:3128');
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error al realizar la solicitud a Scopus: ' . curl_error($ch);
            return null;
        }
        curl_close($ch);
        return json_decode($response, true);
    }

    $data = buscarEnScopus('title(' . $titulo . ')');

    if (isset($data['search-results']['entry']) && !empty($data['search-results']['entry'])) {
        $entry = $data['search-results']['entry'][0];
        if (isset($entry['eid'])) {
            echo "<div class='status-box'><a href='#' onclick='mostrarModalScopus()'>Scopus: Ok</a></div>";
            $est_scopus = 1;
            echo "
            <div id='modalScopus' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;'>
                <div style='position:relative; margin:10% auto; padding:20px; background:white; width:80%; max-width:600px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);'>
                    <h3>Detalles del Artículo en Scopus</h3>
                    <p><strong>Título:</strong> "               . (isset($entry['dc:title'])               ? htmlspecialchars($entry['dc:title'])               : 'No disponible') . "</p>
                    <p><strong>Autores:</strong> "              . (isset($entry['dc:creator'])             ? htmlspecialchars(is_array($entry['dc:creator']) ? implode(', ', $entry['dc:creator']) : $entry['dc:creator']) : 'No disponible') . "</p>
                    <p><strong>Fecha de publicación:</strong> " . (isset($entry['prism:coverDate'])        ? htmlspecialchars($entry['prism:coverDate'])        : 'No disponible') . "</p>
                    <p><strong>DOI:</strong> "                  . (isset($entry['prism:doi'])              ? htmlspecialchars($entry['prism:doi'])              : 'No disponible') . "</p>
                    <p><strong>ISSN:</strong> "                 . (isset($entry['prism:issn'])             ? htmlspecialchars($entry['prism:issn'])             : 'No disponible') . "</p>
                    <p><strong>eISSN:</strong> "                . (isset($entry['prism:eIssn'])            ? htmlspecialchars($entry['prism:eIssn'])            : 'No disponible') . "</p>
                    <p><strong>Volumen:</strong> "              . (isset($entry['prism:volume'])           ? htmlspecialchars($entry['prism:volume'])           : 'No disponible') . "</p>
                    <p><strong>Número:</strong> "               . (isset($entry['prism:issueIdentifier'])  ? htmlspecialchars($entry['prism:issueIdentifier'])  : 'No disponible') . "</p>
                    <p><strong>Revista:</strong> "              . (isset($entry['prism:publicationName'])  ? htmlspecialchars($entry['prism:publicationName'])  : 'No disponible') . "</p>
                    <p><strong>Tipo de Documento:</strong> "    . (isset($entry['subtypeDescription'])     ? htmlspecialchars($entry['subtypeDescription'])     : 'No disponible') . "</p>
                    <p><a href='https://www.scopus.com/record/display.uri?eid=" . urlencode($entry['eid']) . "&origin=resultslist' target='_blank'>Ver en Scopus</a></p>
                    <button onclick='cerrarModalScopus()'>Cerrar</button>
                </div>
            </div>";
            echo "<script>
                function mostrarModalScopus() { document.getElementById('modalScopus').style.display='block'; }
                function cerrarModalScopus()  { document.getElementById('modalScopus').style.display='none';  }
            </script>";
        } else {
            echo "<div class='status-box'>Scopus: <span style='color:red;'>N/A</span></div>";
        }
    } else {
        echo "<div class='status-box'>Scopus: <span style='color:red;'>N/A</span></div>";
        $est_scopus = 0;
    }

    // ══════════════════════════════════════════════
    // Función reutilizable: mostrar modal DOAJ
    // ══════════════════════════════════════════════
    function mostrarModalDoaj($result) {
        $title           = $result['bibjson']['title']          ?? 'No disponible';
        $journalTitle    = $result['bibjson']['journal']['title'] ?? 'No disponible';
        $publicationDate = $result['bibjson']['year']            ?? 'No disponible';
        $doaj_doi = 'No disponible';
        if (isset($result['bibjson']['identifier'])) {
            foreach ($result['bibjson']['identifier'] as $id) {
                if ($id['type'] === 'doi') { $doaj_doi = $id['id']; break; }
            }
        }
        $doaj_issn  = 'No disponible';
        $doaj_eissn = 'No disponible';
        if (isset($result['bibjson']['journal']['issns']) && count($result['bibjson']['journal']['issns']) > 0) {
            $doaj_issn = $result['bibjson']['journal']['issns'][0];
        }
        if (isset($result['bibjson']['identifier'])) {
            foreach ($result['bibjson']['identifier'] as $id) {
                if ($id['type'] === 'eissn') { $doaj_eissn = $id['id']; break; }
            }
        }
        $authors = [];
        if (isset($result['bibjson']['author'])) {
            foreach ($result['bibjson']['author'] as $author) { $authors[] = $author['name']; }
        }
        $authorsList     = $authors ? implode(', ', $authors) : 'No disponible';
        $numberOfAuthors = count($authors);
        $doaj_volume     = $result['bibjson']['journal']['volume'] ?? 'No disponible';
        $doaj_number     = $result['bibjson']['journal']['number'] ?? 'No disponible';
        $doiUrl          = 'https://doi.org/' . $doaj_doi;
        $doajLink        = 'https://doaj.org/article/' . $result['id'];

        echo "<div class='status-box'>
                <a href='#' onclick='mostrarModalDoajFn()' class='status-link'>DOAJ: OK</a>
              </div>";
        echo "
        <div id='modalDoaj' style='display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000;'>
            <div style='position:relative; margin:10% auto; padding:20px; background:white; border:2px solid #007bff; border-radius:10px; width:600px; max-width:90%; box-shadow:0 0 15px rgba(0,0,0,0.5);'>
                <h5 style='color:#007bff;'>Detalles de la Revista DOAJ</h5>
                <p><strong>Título:</strong> "              . htmlspecialchars($title)           . "</p>
                <p><strong>Revista:</strong> "             . htmlspecialchars($journalTitle)    . "</p>
                <p><strong>Autores:</strong> "             . htmlspecialchars($authorsList)     . "</p>
                <p><strong>Número de autores:</strong> "   . htmlspecialchars($numberOfAuthors) . "</p>
                <p><strong>Fecha de publicación:</strong> ". htmlspecialchars($publicationDate) . "</p>
                <p><strong>DOI:</strong> "                 . htmlspecialchars($doaj_doi)        . "</p>
                <p><strong>ISSN:</strong> "                . htmlspecialchars($doaj_issn)       . "</p>
                <p><strong>eISSN:</strong> "               . htmlspecialchars($doaj_eissn)      . "</p>
                <p><strong>Volumen:</strong> "             . htmlspecialchars($doaj_volume)     . "</p>
                <p><strong>Número:</strong> "              . htmlspecialchars($doaj_number)     . "</p>
                <p><strong>Enlace DOI:</strong> <a href='" . htmlspecialchars($doiUrl)  . "' target='_blank'>" . htmlspecialchars($doiUrl)  . "</a></p>
                <p><strong>Enlace DOAJ:</strong> <a href='" . htmlspecialchars($doajLink) . "' target='_blank'>" . htmlspecialchars($doajLink) . "</a></p>
                <button onclick='cerrarModalDoajFn()' style='background-color:#007bff; color:white; border:none; padding:10px 15px; border-radius:5px; cursor:pointer;'>Cerrar</button>
            </div>
        </div>";
        echo "<script>
            function mostrarModalDoajFn() { document.getElementById('modalDoaj').style.display='block'; }
            function cerrarModalDoajFn()  { document.getElementById('modalDoaj').style.display='none';  }
        </script>";
    }

    // ══════════════════════════════════════════════
    // DOAJ — Intento 1: título original (inglés)
    // ══════════════════════════════════════════════
    $tituloEscapado = urlencode('bibjson.title:"' . $nombre_articulo . '"');
    $url = 'https://doaj.org/api/search/articles/' . $tituloEscapado;
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_PROXY,          'proxy.unicauca.edu.co:3128');
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);

    if (isset($data['results']) && count($data['results']) > 0) {
        $est_doaj = 1;
        mostrarModalDoaj($data['results'][0]);

    } else {

        // ══════════════════════════════════════════════
        // DOAJ — Intento 2: título traducido al español
        // (traducción lazy: solo ocurre si llegamos aquí)
        // ══════════════════════════════════════════════
        if ($nombre_articulo_esp === null) {
            $nombre_articulo_esp = traducirArticulo($nombre_articulo);
        }
        $tituloEscapado = urlencode($nombre_articulo_esp);
        $url = 'https://doaj.org/api/search/articles/' . $tituloEscapado;
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_PROXY,          'proxy.unicauca.edu.co:3128');
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);

        if (isset($data['results']) && count($data['results']) > 0) {
            $est_doaj = 1;
            mostrarModalDoaj($data['results'][0]);
        } else {
            echo "<div class='status-box'>Doaj: <span class='not-found'>N/A</span></div>";
            $est_doaj = 0;
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
// $issn viene urlencode'd desde el POST, primero revertir
$issnLimpio   = urldecode($issn);
$tipo_revista = "Internacional";
$est_publindex = 0;

$queryParams = [
    '$where' => "txt_issn_p='" . $issnLimpio . "' OR txt_issn_e='" . $issnLimpio . "'",
    '$limit' => 500
];
$url = "https://www.datos.gov.co/resource/mwmn-inyg.json?" . http_build_query($queryParams);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false) {
    echo "Error cURL Publindex: " . curl_error($ch) . "<br>";
}
curl_close($ch);
$data = json_decode($response, true);

if (!empty($data)) {
    $ultimoAno = 0;
    foreach ($data as $item) {
        $nroAno = intval($item['nro_ano'] ?? 0);
        if ($nroAno > $ultimoAno) $ultimoAno = $nroAno;
    }

    if ($ultimoAno > 0) {
        // ── Segunda consulta con $issnLimpio y http_build_query ──
        $urlUltimoAno = "https://www.datos.gov.co/resource/mwmn-inyg.json?" . http_build_query([
            'txt_issn_p' => $issnLimpio,
            'nro_ano'    => $ultimoAno,
            '$limit'     => 10
        ]);

        $ch = curl_init($urlUltimoAno);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128');
        $responseUltimoAno = curl_exec($ch);
        curl_close($ch);
        $dataUltimoAno = json_decode($responseUltimoAno, true);

        if (!empty($dataUltimoAno)) {
            $tipo_revista  = "Nacional"; // ← solo aquí, cuando hay datos confirmados
            $est_publindex = 1;

            echo "<div class='status-box'>
                    <a href='#' onclick='mostrarModalp()'>Publindex: Nacional ($ultimoAno)</a>
                  </div>";
            echo "
            <div id='resultadoModal' style='display:none; position:fixed; top:0; left:0;
                 width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;'>
                <div style='position:relative; margin:10% auto; padding:20px; background:white;
                            width:80%; max-width:600px; border-radius:8px;
                            box-shadow:0 2px 10px rgba(0,0,0,0.5);'>
                    <h5>Resultados de Publindex - IBN $ultimoAno</h5><br>
                    <div class='modal-body'>";

            foreach ($dataUltimoAno as $item) {
                $tipoClasificacion = $item['id_clas_rev']     ?? 'No disponible';
                $issnImpreso       = $item['txt_issn_p']      ?? 'No disponible';
                $issnDigital       = $item['txt_issn_e']      ?? 'No disponible';
                $institucion       = $item['nme_inst_edit_1'] ?? 'No disponible';
                $nombreRevista     = $item['nme_revista_in']  ?? 'No disponible';
                echo "<div style='border:1px solid #ccc; padding:10px; margin-bottom:10px;'>";
                echo "<strong>Revista:</strong> "       . htmlspecialchars($nombreRevista)     . "<br>";
                echo "<strong>ISSN:</strong> "          . htmlspecialchars($issnImpreso)       . " / " . htmlspecialchars($issnDigital) . "<br>";
                echo "<strong>Clasificación:</strong> " . htmlspecialchars($tipoClasificacion) . "<br>";
                echo "<strong>Institución:</strong> "   . htmlspecialchars($institucion)       . "<br>";
                echo "</div>";
            }

            echo "</div>"; // modal-body
            echo "<button onclick='cerrarModal()'>Cerrar</button>";
            echo "</div></div>"; // modal-content + modal
            echo "<script>
                function mostrarModalp() { document.getElementById('resultadoModal').style.display='block'; }
                function cerrarModal()   { document.getElementById('resultadoModal').style.display='none';  }
            </script>";

        } else {
            echo "<div class='status-box'>Publindex Internacional ISSN: <strong>"
                . htmlspecialchars($issnLimpio) . "</strong></div>";
        }
    }

} else {
    // ── Fallback: tabla local publindex_int_homolog ──
    $conexion = new mysqli("localhost", "root", "", "productividad");
    if ($conexion->connect_error) die("Conexión fallida: " . $conexion->connect_error);

    $columnas = ["issn_impr", "issne_pub", "issnx_pub"];
    $row = null;
    foreach ($columnas as $columna) {
        $stmt2 = $conexion->prepare("SELECT * FROM publindex_int_homolog WHERE $columna = ? ORDER BY grup");
        $stmt2->bind_param("s", $issnLimpio); // ← $issnLimpio, no $issn
        $stmt2->execute();
        $resultado = $stmt2->get_result();
        if ($resultado->num_rows > 0) { $row = $resultado->fetch_assoc(); break; }
        $stmt2->close();
    }
    $conexion->close();

    if (!$row) {
        $row = [
            'issn_impr'          => 'No encontrado',
            'issne_pub'          => 'No encontrado',
            'calificacion_int'   => 'No disponible',
            'sires_int'          => 'No disponible',
            'nombre_revista_int' => 'No disponible',
            'vigencia_int'       => 'No disponible'
        ];
    }

    $est_publindex     = ($row['nombre_revista_int'] !== 'No disponible') ? 1 : 0;
    $issnImpreso       = $row['issn_impr'];
    $issnDigital       = $row['issne_pub'];
    $tipoClasificacion = $row['calificacion_int'];
    $institucion       = $row['sires_int'];
    $nombreRevista     = $row['nombre_revista_int'];
    $vigencia_int      = $row['vigencia_int'];

    echo "
    <div class='status-box'>
        <a href='#' onclick='mostrarModalp()' style='color:#007bff; font-weight:bold;'>
            Publindex Internacional
        </a>
    </div>
    <div id='detalleModal1' style='display:none; position:fixed; top:50%; left:50%;
         transform:translate(-50%,-50%); z-index:1000; background:white; border-radius:8px;
         border:2px solid #007bff; box-shadow:0 4px 15px rgba(0,0,0,0.3); width:400px; max-width:90%;'>
        <div style='padding:15px; background:#007bff; color:white; border-radius:8px 8px 0 0;'>
            <h5 style='margin:0;'>Resultados de Publindex - ISSN</h5>
        </div>
        <div style='padding:15px; font-size:14px; line-height:1.6;'>
            <div><strong>Revista:</strong> "      . htmlspecialchars($nombreRevista)     . "</div>
            <div><strong>ISSN Impreso:</strong> " . htmlspecialchars($issnImpreso)       . "</div>
            <div><strong>ISSN Digital:</strong> " . htmlspecialchars($issnDigital)       . "</div>
            <div><strong>Clasificación:</strong> ". htmlspecialchars($tipoClasificacion) . "</div>
            <div><strong>LISTA_SIR:</strong> "    . htmlspecialchars($institucion)       . "</div>
            <div><strong>Vigencia:</strong> "     . htmlspecialchars($vigencia_int)      . "</div>
        </div>
        <div style='padding:15px; text-align:right; background:#f1f1f1; border-radius:0 0 8px 8px;'>
            <button onclick='cerrarModalp()' style='padding:8px 12px; background:#007bff;
                    color:white; border:none; border-radius:5px; cursor:pointer;'>Cerrar</button>
        </div>
    </div>
    <script>
        function mostrarModalp() { document.getElementById('detalleModal1').style.display='block'; }
        function cerrarModalp()  { document.getElementById('detalleModal1').style.display='none';  }
    </script>";
}

echo '</div>'; // cierra .box.right
echo '</div>'; // cierra .wrapper

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
<div class="container mt-4 pb-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-4 text-center">
            <h3 class="fw-bold text-dark">
                <i class="fas fa-file-signature text-primary me-2"></i>Solicitud de Productividad
            </h3>
            <p class="text-muted small">Complete los datos para formalizar el registro del artículo</p>
        </div>
        
        <div class="card-body px-4">
            <form method="post" action="guardar_solicitud.php">
                
                <div class="text-uppercase fw-bold text-secondary mb-3" style="font-size: 0.75rem; letter-spacing: 1px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    Información Administrativa
                </div>
                
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-4">
                        <label for="identificador_base" class="form-label fw-bold small">Identificador:</label>
                        <div class="input-group">
                            <input type="text" class="form-control shadow-sm" id="identificador_base" name="identificador_base" 
                                   value="<?= date('Y_m') ?>" maxlength="7" pattern="\d{4}_\d{2}" placeholder="Año_Mes" required>
                            <select class="form-select shadow-sm" id="numero_envio" name="numero_envio" style="max-width: 70px;" required>
                                <?php for($i=1;$i<=9;$i++): ?>
                                    <option value="<?= $i ?>" <?= $i == 1 ? 'selected' : ''; ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="numero_profesores" class="form-label fw-bold text-primary small"># Profesores solicitantes:</label>
                        <input type="number" id="numero_profesores" name="numero_profesores" class="form-control border-primary shadow-sm" required min="1" placeholder="Ej: 2">
                    </div>
                    <div class="col-md-4">
                        <label for="inputTrdFac" class="form-label fw-bold small">Número de oficio:</label>
                        <input type="text" id="inputTrdFac" name="inputTrdFac" class="form-control shadow-sm" placeholder="Oficio TRD" required>
                    </div>
                </div>

                <div id="contenedor_documentos" class="mb-4 rounded-3" style="background-color: #f1f5f9; border-left: 4px solid #0d6efd;">
                    </div>

                <div class="text-uppercase fw-bold text-secondary mb-3" style="font-size: 0.75rem; letter-spacing: 1px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    Estados de la Revista y Alertas
                </div>
                
                <div class="p-3 mb-4 rounded-3 border bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="est_scimago" id="est_scimago" value="1" <?= (isset($est_scimago) && $est_scimago == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="est_scimago">SCIMAGO</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="est_doaj" id="est_doaj" value="1" <?= (isset($est_doaj) && $est_doaj == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="est_doaj">DOAJ</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="est_scopus" id="est_scopus" value="1" <?= (isset($est_scopus) && $est_scopus == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="est_scopus">SCOPUS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="est_miar" id="est_miar" value="1" <?= (isset($est_miar) && $est_miar == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="est_miar">MIAR</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="est_core" id="est_core" value="1" <?= (isset($est_core) && $est_core == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="est_core">CORE</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 border-start border-2">
                            <div class="form-check ms-3">
                                <input class="form-check-input" type="checkbox" name="mdpi_pred" id="mdpi_pred" value="1">
                                <label class="form-check-label text-danger fw-bold small" for="mdpi_pred">
                                    ⚠️ Revista MDPI o Predadora
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-uppercase fw-bold text-secondary mb-3" style="font-size: 0.75rem; letter-spacing: 1px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    Detalles del Artículo
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="titulo_articulo" class="form-label fw-bold small">Título del Artículo:</label>
                        <input type="text" class="form-control form-control-sm shadow-sm" id="titulo_articulo" name="titulo_articulo" 
                               value="<?= isset($entry['dc:title']) ? htmlspecialchars($entry['dc:title']) : (isset($title) ? htmlspecialchars($title) : htmlspecialchars($nombre_articulo)); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label for="issn" class="form-label fw-bold small">ISSN:</label>
                        <input type="text" class="form-control form-control-sm shadow-sm" id="issn" name="issn" 
                               value="<?= isset($entry['prism:issn']) ? htmlspecialchars($entry['prism:issn']) : (isset($doaj_issn) ? htmlspecialchars($doaj_issn) : (isset($issnImpreso) ? htmlspecialchars($issnImpreso) : (isset($issnBd) ? htmlspecialchars($issnBd) : urldecode($issn)))); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="eissn" class="form-label fw-bold small">eISSN:</label>
                        <input type="text" class="form-control form-control-sm shadow-sm" id="eissn" name="eissn" 
                               value="<?= isset($entry['prism:eIssn']) ? htmlspecialchars($entry['prism:eIssn']) : (isset($doaj_eissn) ? htmlspecialchars($doaj_eissn) : (isset($issnDigital) ? htmlspecialchars($issnDigital) : '')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="doi" class="form-label fw-bold small text-primary">DOI:</label>
                        <input type="text" class="form-control form-control-sm shadow-sm border-primary" id="doi" name="doi" 
                               value="<?= isset($entry['prism:doi']) ? htmlspecialchars($entry['prism:doi']) : (isset($doiUrl) ? htmlspecialchars($doiUrl) : ($core_doi ?? '')); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label for="volumen" class="form-label fw-bold small">Volumen:</label>
                        <input type="text" id="volumen" name="volumen" class="form-control form-control-sm" 
                               onfocus="checkVolume()" onblur="checkVolume()" value="<?= $entry['prism:volume'] ?? ($doaj_volume ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="numero_r" class="form-label fw-bold small">Número:</label>
                        <input type="text" id="numero_r" name="numero_r" class="form-control form-control-sm" 
                               onfocus="checkNumero()" onblur="checkNumero()" value="<?= $entry['prism:issueIdentifier'] ?? ($doaj_number ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="ano_publicacion" class="form-label fw-bold small">Año:</label>
                        <input type="text" id="ano_publicacion" name="ano_publicacion" class="form-control form-control-sm" 
                               onfocus="checkAno()" onblur="checkAno()" value="<?= isset($entry['prism:coverDate']) ? date('Y', strtotime($entry['prism:coverDate'])) : ($publicationDate ?? ($core_anio_publicacion ?? '')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="numero_autores" class="form-label fw-bold small"># Autores:</label>
                        <input type="number" id="numero_autores" name="numero_autores" class="form-control form-control-sm" 
                               onfocus="checkNumeroAutores()" onblur="checkNumeroAutores()" required value="<?= $numberOfAuthors ?? ($core_num_profesores ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="nombre_revista" class="form-label fw-bold small">Nombre Revista:</label>
                        <input type="text" id="nombre_revista" name="nombre_revista" class="form-control form-control-sm shadow-sm" 
                               value="<?= $entry['prism:publicationName'] ?? ($journalTitle ?? ($nombreRevista ?? ($nombreRevistaScimago ?? ($nombreRevistaMiar ?? '')))); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="tipo_articulo" class="form-label fw-bold small">Tipo artículo:</label>
                        <select id="tipo_articulo" name="tipo_articulo" class="form-select form-select-sm" required onfocus="checkTipoArticulo()" onblur="checkTipoArticulo()">
                            <option value="">Seleccione...</option>
                            <option value="FULL PAPER" <?= (isset($entry['subtypeDescription']) && ($entry['subtypeDescription'] == 'Article' || empty($entry['subtypeDescription']))) ? 'selected' : ''; ?>>FULL PAPER</option>
                            <option value="ARTICULO CORTO" <?= (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'ShortArticle') ? 'selected' : ''; ?>>ARTÍCULO CORTO</option>
                            <option value="EDITORIALES" <?= (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'Editorial') ? 'selected' : ''; ?>>EDITORIALES</option>
                            <option value="REVISION DE TEMA" <?= (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'Review') ? 'selected' : ''; ?>>REVISION DE TEMA</option>
                            <option value="REPORTE DE CASO" <?= (isset($entry['subtypeDescription']) && $entry['subtypeDescription'] == 'CaseReport') ? 'selected' : ''; ?>>REPORTE DE CASO</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="tipo_publindex" class="form-label fw-bold small">Publindex:</label>
                        <select id="tipo_publindex" name="tipo_publindex" class="form-select form-select-sm" required onchange="checkSelection(this)">
                            <option value="">Clasific...</option>
                            <option value="A1" <?= (isset($tipoClasificacion) && $tipoClasificacion === 'A1') ? 'selected' : ''; ?>>A1</option>
                            <option value="A2" <?= (isset($tipoClasificacion) && $tipoClasificacion === 'A2') ? 'selected' : ''; ?>>A2</option>
                            <option value="B" <?= (isset($tipoClasificacion) && $tipoClasificacion === 'B') ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?= (isset($tipoClasificacion) && $tipoClasificacion === 'C') ? 'selected' : ''; ?>>C</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="tipo_revista" class="form-label fw-bold small">Tipo Revista:</label>
                        <select id="tipo_revista" name="tipo_revista" class="form-select form-select-sm" required>
                            <option value="Nacional" <?= ($tipo_revista == "Nacional") ? "selected" : ""; ?>>Nacional</option>
                            <option value="Internacional" <?= ($tipo_revista == "Internacional") ? "selected" : ""; ?>>Internacional</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="puntaje" class="form-label fw-bold text-success small">Puntaje Calculado:</label>
                        <input type="number" id="puntaje" name="puntaje" class="form-control form-control-sm fw-bold border-success text-success bg-light shadow-sm" readonly step="0.01">
                    </div>
                </div>

                <input type="hidden" name="fk_id_articulo" value="<?= $id_articulo_encontrado; ?>">
                <input type="hidden" id="identificador_solicitud" name="identificador_solicitud">
                <input type="hidden" id="inputNombreCompleto" name="nombre_completo">
                <input type="hidden" id="inputDepto" name="departamento">
                <input type="hidden" id="inputFac" name="facultad">

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="index.php" class="btn btn-outline-secondary px-4 shadow-sm">← Regresar</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
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