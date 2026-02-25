<?php
// URL de la búsqueda
$url = "https://dialnet.unirioja.es/buscar/documentos?querysDismax.DOCUMENTAL_TODO=TOOLS+FOR+DEVELOPING+APPLICATIONS+IN+THE+SEMANTIC+WEB+OF+THINGS%3A+A+SYSTEMATIC+LITERATURE+REVIEW";

// Inicializar cURL
$ch = curl_init($url);

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

// Configurar el uso de un proxy
curl_setopt($ch, CURLOPT_PROXY, 'proxy.unicauca.edu.co:3128'); // Reemplaza con la dirección y el puerto de tu proxy
curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'usuario:contraseña'); // Si tu proxy requiere autenticación, descomenta y reemplaza

// Ejecutar la solicitud y obtener la respuesta
$response = curl_exec($ch);

// Verificar si hubo errores
if ($response === false) {
    echo "Error en la ejecución de cURL: " . curl_error($ch);
} else {
    // Aquí puedes usar expresiones regulares o un parser de HTML como DOMDocument para extraer información
    echo $response; // Imprime el HTML para inspeccionar la estructura
}

// Cerrar cURL
curl_close($ch);

    
?>