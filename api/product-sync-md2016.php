<?php
// Obtener el contenido de la solicitud POST
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Extraer las propiedades del cuerpo de la solicitud
$properties = $data['properties'] ?? [];

$hs_latest_source = $properties['hs_latest_source']['value'] ?? null;
$hs_object_id = $properties['hs_object_id']['value'] ?? null;
$tmp_product_interest_dynamics_landing = $properties['tmp_product_interest_dynamics_landing']['value'] ?? null;
$hs_latest_source_data_1 = $properties['hs_latest_source_data_1']['value'] ?? null;
$observations__dynamics_ = $properties['observations__dynamics_']['value'] ?? null;
$firstname = $properties['firstname']['value'] ?? "Sin Nombre";
$lastname = $properties['lastname']['value'] ?? "Sin Apellido";
$email = $properties['email']['value'] ?? null;
$phone = $properties['phone']['value'] ?? "111";
$md_city = $properties['md_city']['value'] ?? "AS8";
$numero_de_cedula = $properties['numero_de_cedula']['value'] ?? null;
$modalidad_de_estudio = $properties['modalidad_de_estudio']['value'] ?? null;
$product_id_dynamics = $properties['product_id_dynamics']['value'] ?? null;
$gd_student_type_antiquity = $properties['gd_student_type_antiquity']['value'] ?? "NUEVO";
$campaign_attribution = $properties['campaign_attribution']['value'] ?? "UNKNOWN";
$prefered_contact_channel = $properties['prefered_contact_channel']['value'] ?? null;
$tmp_md_first_json_sent = $properties['tmp_md_first_json_sent']['value'] ?? null;
$tmp_md_object_id = $properties['md_object_id']['value'] ?? null;
$codigo_origen = $properties['codigo_origen']['value'] ?? "OL-ODH";

// Limpiar codigo_origen
$codigo_origen = str_replace(' ', '', $codigo_origen);

// Validar numero_de_cedula
if (!$numero_de_cedula || $numero_de_cedula < 500000 || $numero_de_cedula > 9999999) {
    $numero_de_cedula = $hs_object_id;
}

// Definir las variables adicionales que se necesiten
$nombre_carrera = '';
$id_carrera = '';
$products = [];

// Obtener productos desde la API
$response = file_get_contents('https://eleve-products.herokuapp.com/api/getProducts');
$products = json_decode($response, true);

// Procesar la lógica de productos
if ($product_id_dynamics) {
    foreach ($products as $product) {
        if ($product['md_id_carrera'] == $product_id_dynamics) {
            $nombre_carrera = $product['md_nombre_carrera'];
            $id_carrera = $product['md_id_carrera'];
            if (strpos($product['hs_slug_code'], "wz-") !== false) {
                // Si el producto es tipo "wizard"
                $temp = $numero_de_cedula;
                $numero_de_cedula = $hs_object_id;
                $hs_object_id = $temp;
            }
        }
    }
} else {
    // Lógica adicional según modalidad_de_estudio y tmp_product_interest_dynamics_landing
}

// Autenticación para obtener el token
$data = http_build_query([
    'grant_type' => 'password',
    'username' => 'UA',
    'password' => 'UaPassW!',
]);

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => $data,
    ],
];

$context = stream_context_create($options);
$token_response = file_get_contents('http://190.128.233.147:8088/getToken', false, $context);
$token_data = json_decode($token_response, true);

if (isset($token_data['access_token'])) {
    $access_token = $token_data['access_token'];

    // Datos a enviar a la segunda API
    $data_to_send = [
        'nombre' => $firstname,
        'apellido' => $lastname,
        'telefono' => $phone,
        'mail' => $email,
        'nombre_carrera' => $nombre_carrera,
        'id_carrera' => $id_carrera,
        'id_sede' => '58440f3f-504d-ec11-b945-00505689be00',
        'observaciones' => $observations__dynamics_,
        'nrodocumento' => $numero_de_cedula,
        'cod_universidad' => 'UA',
        'origen' => $codigo_origen,
        'fuente_origen' => $hs_latest_source,
        'id_hubspot' => $hs_object_id,
        'id_ciudad' => $md_city,
        'new_tipodealumno' => $gd_student_type_antiquity,
        'new_campaa' => $campaign_attribution,
        'new_prefered_contact_channel' => $prefered_contact_channel,
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\nAuthorization: Bearer " . $access_token . "\r\n",
            'method' => 'POST',
            'content' => json_encode($data_to_send, JSON_UNESCAPED_UNICODE),
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents('http://190.128.233.147:8088/api/ContactOPP/', false, $context);

    // Preparar respuesta del webhook
    $response_data = [
        'hs_execution_state' => 'complete',
        'hs_status_code' => 200,
        'hs_server_response' => json_decode($result, true),
        'id_carrera' => $id_carrera,
        'md_object_id' => $tmp_md_object_id,
        'md_json_sent' => json_encode($data_to_send, JSON_UNESCAPED_UNICODE),
    ];

    header('Content-Type: application/json');
    echo json_encode($response_data, JSON_UNESCAPED_UNICODE);
} else {
    // Manejo del error si no se recibe el token de acceso
    $error_response = [
        'hs_execution_state' => 'error',
        'hs_status_code' => 500,
        'hs_server_response' => 'No se recibió el token de acceso.',
    ];
    header('Content-Type: application/json');
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
}
?>