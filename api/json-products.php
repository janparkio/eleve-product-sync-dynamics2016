<?php
// Ruta del archivo JSON
$file_path = __DIR__ . '/products.json';

// Verificar si el archivo existe, si no, crearlo
if (!file_exists($file_path)) {
    file_put_contents($file_path, json_encode([]));
}

// Obtener el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Leer y devolver el contenido del archivo JSON
    $json_data = file_get_contents($file_path);
    header('Content-Type: application/json');
    echo $json_data;
} elseif ($method === 'POST') {
    // Leer el cuerpo de la solicitud POST
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);

    // Validar y guardar el nuevo contenido en el archivo JSON
    if (json_last_error() === JSON_ERROR_NONE) {
        file_put_contents($file_path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
    } else {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    }
} else {
    // Método no soportado
    header('Content-Type: application/json', true, 405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>