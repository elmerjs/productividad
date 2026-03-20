<?php
// ============================================================
// TEST SCIMAGO v3 — fix Brotli encoding
// Uso: test_scimago_v3.php?issn=2046-1402
// ============================================================

$issn = $_GET['issn'] ?? '2046-1402';

echo "<h2>🔍 Test SCImago v3 para ISSN: <strong>" . htmlspecialchars($issn) . "</strong></h2>";
echo "<hr>";

$urlScimago = "https://www.scimagojr.com/journalsearch.php?q=" . urlencode($issn);
echo "<p><strong>URL:</strong> <a href='$urlScimago' target='_blank'>$urlScimago</a></p>";

// ── INTENTO A: Solo gzip (sin br) ──
echo "<h3>🅐 Con proxy + Accept-Encoding solo gzip</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            $urlScimago);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_PROXY,          'http://proxy.unicauca.edu.co:3128');
curl_setopt($ch, CURLOPT_TIMEOUT,        30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// ── CLAVE: forzar solo gzip, excluir br ──
curl_setopt($ch, CURLOPT_ENCODING,       'gzip');
curl_setopt($ch, CURLOPT_USERAGENT,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' .
    '(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: es-CO,es;q=0.9,en;q=0.8',
    'Accept-Encoding: gzip, deflate',   // <-- sin "br"
    'Connection: keep-alive',
    'Upgrade-Insecure-Requests: 1',
    'Referer: https://www.google.com/',
    'Cache-Control: max-age=0',
]);
$contenidoA = curl_exec($ch);
$httpCodeA  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errA       = curl_error($ch);
$errnoA     = curl_errno($ch);
curl_close($ch);

echo "<ul>";
echo "<li><strong>HTTP Code:</strong> " . ($httpCodeA == 200 ? "✅ $httpCodeA" : "❌ $httpCodeA") . "</li>";
echo "<li><strong>Error cURL:</strong> " . ($errnoA ? "❌ $errA" : "✅ Sin errores") . "</li>";
echo "<li><strong>Bytes recibidos:</strong> " . strlen($contenidoA) . "</li>";
echo "</ul>";

// ── INTENTO B: Sin encoding forzado y sin header Accept-Encoding ──
echo "<h3>🅑 Con proxy + sin Accept-Encoding en headers</h3>";
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL,            $urlScimago);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch2, CURLOPT_PROXY,          'http://proxy.unicauca.edu.co:3128');
curl_setopt($ch2, CURLOPT_TIMEOUT,        30);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
// ── sin CURLOPT_ENCODING para que no negocie compresión ──
curl_setopt($ch2, CURLOPT_USERAGENT,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' .
    '(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: es-CO,es;q=0.9,en;q=0.8',
    // sin Accept-Encoding → el servidor responde sin compresión
    'Connection: keep-alive',
    'Upgrade-Insecure-Requests: 1',
    'Referer: https://www.google.com/',
]);
$contenidoB = curl_exec($ch2);
$httpCodeB  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$errB       = curl_error($ch2);
$errnoB     = curl_errno($ch2);
curl_close($ch2);

echo "<ul>";
echo "<li><strong>HTTP Code:</strong> " . ($httpCodeB == 200 ? "✅ $httpCodeB" : "❌ $httpCodeB") . "</li>";
echo "<li><strong>Error cURL:</strong> " . ($errnoB ? "❌ $errB" : "✅ Sin errores") . "</li>";
echo "<li><strong>Bytes recibidos:</strong> " . strlen($contenidoB) . "</li>";
echo "</ul>";

// ── ANALIZAR el mejor resultado ──
$mejor = null;
$tag   = '';
if ($httpCodeA == 200 && strlen($contenidoA) > 500) { $mejor = $contenidoA; $tag = 'A (gzip forzado)'; }
elseif ($httpCodeB == 200 && strlen($contenidoB) > 500) { $mejor = $contenidoB; $tag = 'B (sin encoding)'; }

if ($mejor) {
    echo "<h3>✅ Contenido válido del intento $tag — Analizando DOM</h3>";

    $dom = new DOMDocument();
    @$dom->loadHTML($mejor);
    $xp = new DOMXPath($dom);

    $q1 = $xp->query("//a[contains(@href, 'journalsearch.php')]");
    $q2 = $xp->query("//span[@class='jrnlname']");

    echo "<ul>";
    echo "<li>XPath original <code>//a[contains(@href,'journalsearch.php')]</code>: <strong>" . $q1->length . "</strong></li>";
    echo "<li>XPath <code>//span[@class='jrnlname']</code>: <strong>" . $q2->length . "</strong></li>";
    echo "</ul>";

    if ($q1->length > 0) {
        $r    = $q1->item(0);
        $jrnl = $xp->query(".//span[@class='jrnlname']", $r);
        $nombre = $jrnl->length > 0 ? $jrnl->item(0)->nodeValue : '— span no encontrado dentro del <a>';
        echo "<p>✅ <strong>Revista:</strong> " . htmlspecialchars($nombre) . "</p>";

        // país y editorial
        $info      = explode("\n", trim($r->textContent));
        $pais      = trim($info[0] ?? 'No disponible');
        $editorial = trim($info[1] ?? 'No disponible');
        echo "<p><strong>País:</strong> " . htmlspecialchars($pais) . "</p>";
        echo "<p><strong>Editorial:</strong> " . htmlspecialchars($editorial) . "</p>";
        echo "<p style='color:green;font-weight:700'>🎉 ¡XPath original funciona! Puedes usar este bloque cURL en tu archivo principal.</p>";
    } elseif ($q2->length > 0) {
        echo "<p>⚠️ XPath original no funcionó pero <code>span.jrnlname</code> sí. Revista: <strong>"
            . htmlspecialchars($q2->item(0)->nodeValue) . "</strong></p>";
        echo "<p>Habrá que ajustar el XPath en tu código principal.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ HTML recibido pero 0 resultados en XPath. SCImago puede haber cambiado su estructura.</p>";
        echo "<textarea style='width:100%;height:200px;font-size:11px;'>" 
            . htmlspecialchars(substr($mejor, 0, 3000)) . "</textarea>";
    }
} else {
    echo "<h3>❌ Ningún intento devolvió contenido válido</h3>";
    echo "<p>HTML intento A:</p>";
    echo "<textarea style='width:100%;height:150px;font-size:11px;'>" 
        . htmlspecialchars(substr($contenidoA, 0, 2000)) . "</textarea>";
    echo "<p>HTML intento B:</p>";
    echo "<textarea style='width:100%;height:150px;font-size:11px;'>" 
        . htmlspecialchars(substr($contenidoB, 0, 2000)) . "</textarea>";
}

echo "<hr><p style='color:#666;font-size:.85em'>
    Si intento A o B devuelven ✅ con XPath funcionando → copiamos ese bloque cURL a tu archivo principal.<br>
    Si ambos fallan → pasamos al plan B: usar la API de Scopus para datos de revista.
</p>";
?>
