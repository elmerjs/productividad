<?php
// ISSN que deseas buscar
$issn = '0123-3432'; // Cambia esto al ISSN que necesites

// URL de la API de Publindex con un límite de 500 registros y el filtro por ISSN
$url = "https://www.datos.gov.co/resource/mwmn-inyg.json?txt_issn_p=$issn&\$limit=500";

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
            $tipoClasificacion = ($item['id_clas_rev'] ?? 'No disponible');
            $issnImpreso = ($item['txt_issn_p'] ?? 'No disponible');
            $issnDigital = ($item['txt_issn_l'] ?? 'No disponible');
            $institucion = ($item['nme_inst_edit_1'] ?? 'No disponible');
            
            echo "ISSN: $issnImpreso, $issnDigital\n";
            echo "Clasificación: $tipoClasificacion\n";
            echo "Institución: $institucion\n";
            echo "-----------------------------------\n";
        }
    } else {
        echo "No se encontraron años válidos para el ISSN: $issn.";
    }
} else {
    echo "No se encontraron registros para el ISSN: $issn.";
}
?>
