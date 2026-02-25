<?php
$doi = "10.1000/demo_DOI";
$url = "https://doi.org/$doi";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Configuración del proxy (solo si es necesario)
curl_setopt($ch, CURLOPT_PROXY, "http://tu_proxy:puerto"); // Reemplaza "tu_proxy:puerto" por tu configuración de proxy
curl_setopt($ch, CURLOPT_PROXYUSERPWD, "usuario:contraseña"); // Solo si tu proxy requiere autenticación

curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    echo "El DOI existe y es válido.";
} else {
    echo "El DOI no existe o no es válido. Código de respuesta: $http_code";
}
?>
