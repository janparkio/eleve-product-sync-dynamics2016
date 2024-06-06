<?php
// Obtener el contenido de la solicitud POST
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Procesar los datos recibidos
$hs_latest_source = $data["hs_latest_source"];
$hs_object_id = $data["hs_object_id"];
$tmp_product_interest_dynamics_landing = $data["tmp_product_interest_dynamics_landing"];
$hs_latest_source_data_1 = $data["hs_latest_source_data_1"];
$observations__dynamics_ = $data["observations__dynamics_"];
$firstname = $data["firstname"] ?? "Sin Nombre";
$lastname = $data["lastname"] ?? "Sin Apellido";
$email = $data["email"];
$phone = $data["phone"] ?? "111";
$md_city = $data["md_city"] ?? "AS8";
$numero_de_cedula = $data["numero_de_cedula"];
$modalidad_de_estudio = $data["modalidad_de_estudio"];
$product_id_dynamics = $data["product_id_dynamics"];
$gd_student_type_antiquity = $data["gd_student_type_antiquity"] ?? "NUEVO";
$campaign_attribution = $data["campaign_attribution"] ?? "UNKNOWN";
$prefered_contact_channel = $data["prefered_contact_channel"];
$tmp_md_first_json_sent = $data["tmp_md_first_json_sent"];
$tmp_md_object_id = $data["md_object_id"];
$codigo_origen = $data["codigo_origen"] ?? "OL-ODH";

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

// Preparar datos para el segundo API
$data = [
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

// Configurar y enviar la solicitud a la segunda API
$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data),
    ],
];
$context = stream_context_create($options);
$result = file_get_contents('http://190.128.233.147:8088/api/ContactOPP/', false, $context);

// Enviar la respuesta del webhook
header('Content-Type: application/json');
echo json_encode([
    'md_json_sent' => json_encode($data),
    'id_carrera' => $id_carrera,
    'md_object_id' => $tmp_md_object_id,
    'response' => json_decode($result, true),
]);
?>