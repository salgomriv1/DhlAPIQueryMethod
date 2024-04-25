<?php
function obtenerPrecioEnvioConCache($peso, $destino) {
    $cache_file = 'cache.json';
    $cache_duration = 7 * 24 * 60 * 60; // 1 semana en segundos

    // Comprobar si el archivo de caché existe y si su contenido es válido
    if (file_exists($cache_file)) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        if (time() - $cache_data['timestamp'] < $cache_duration) {
            // Comprobar si el resultado está en caché
            $cache_key = $peso . '_' . $destino['countryCode'] . '_' . $destino['cityName'];
            if (isset($cache_data['cache'][$cache_key])) {
                return $cache_data['cache'][$cache_key];
            }
        }
    }

    // Si el resultado no está en caché o la caché ha expirado, llamar a la API
    $precio_envio = llamarApiObtenerPrecio($peso, $destino);

    // Guardar el resultado en caché
    $cache_key = $peso . '_' . $destino['countryCode'] . '_' . $destino['cityName'];
    $cache_data['timestamp'] = time();
    $cache_data['cache'][$cache_key] = $precio_envio;
    file_put_contents($cache_file, json_encode($cache_data));

    return $precio_envio;
}

function llamarApiObtenerPrecio($peso, $destino) {
    
    return obtenerPrecioEnvio($peso, $destino);
}

function obtenerPrecioEnvio($peso, $destino) {
    $curl = curl_init();

    // Construir la URL de la API con los parámetros proporcionados
    $url = "https://express.api.dhl.com/mydhlapi/test/rates";
    $url .= "?accountNumber=yourAccountNumber";
    $url .= "&originCountryCode=ES";
    $url .= "&originCityName=Valencia";
    $url .= "&destinationCountryCode=" . urlencode($destino['countryCode']);
    $url .= "&destinationCityName=" . urlencode($destino['cityName']);
    $url .= "&weight=" . urlencode($peso);
    $url .= "&length=5";
    $url .= "&width=5";
    $url .= "&height=5";
    $url .= "&plannedShippingDate=2024-12-03";
    $url .= "&isCustomsDeclarable=false";
    $url .= "&unitOfMeasurement=metric";
    $url .= "&nextBusinessDay=false";
    $url .= "&strictValidation=false";
    $url .= "&getAllValueAddedServices=false";
    $url .= "&requestEstimatedDeliveryDate=true";
    $url .= "&estimatedDeliveryDateType=QDDF";

    // Configurar las opciones de cURL
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: Basic YXBEN3RSOWVMOG***eUIkNWFSXjBwQ143aw=="
        ],
    ]);

    // Ejecutar la solicitud cURL
    $response = curl_exec($curl);
    $err = curl_error($curl);

   // echo $response;
    
    
    // Manejar errores
    if ($err) {
        echo "cURL Error #:" . $err;
        return false;
    } else {

        // Decodificar el JSON
        $data = json_decode($response, true);

        //echo $data;
        
        // Buscar el producto "EXPRESS WORLDWIDE EU"
        $producto_deseado = "EXPRESS WORLDWIDE EU";
        $precio_envio = null;
        
        foreach ($data['products'] as $producto) {
            if ($producto['productName'] === $producto_deseado) {
                // Obtener el precio del producto "EXPRESS WORLDWIDE EU"
                foreach ($producto['totalPrice'] as $precio) {
                    if ($precio['currencyType'] === 'BILLC') {
                        $precio_envio = $precio['price'];
                        break;
                    }
                }
                break;
            }
        }
        
        // Comprobar si se encontró el producto y su precio
        if ($precio_envio !== null) {
            //echo "El precio del envío para '$producto_deseado' es: $precio_envio EUR";
            return $precio_envio;
        } else {
            return null;
           // echo "No se encontró información de precio para el producto '$producto_deseado'";
        }
        
    }

    // Cerrar la sesión cURL
    curl_close($curl);
}

// Ejemplo de uso:
$peso = 5; // en kilogramos
$destino = [
    'countryCode' => 'CZ',
    'cityName' => 'Prague'
];

$precioEnvio = obtenerPrecioEnvioConCache($peso, $destino);
if ($precioEnvio !== false) {
    echo "El precio del envío es: $precioEnvio EUR";
} else {
    echo "No se pudo obtener el precio del envío.";
}
