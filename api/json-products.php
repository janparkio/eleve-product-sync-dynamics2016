<?php
// Ruta del archivo JSON
$jsonFilePath = 'products.json';

// Verificar si el archivo JSON existe
if (!file_exists($jsonFilePath)) {
    file_put_contents($jsonFilePath, json_encode([]));
}

// Leer el contenido del archivo JSON
$jsonData = file_get_contents($jsonFilePath);
$products = json_decode($jsonData, true);

// Verificar el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    header('Content-Type: application/json');
    echo json_encode($products);
    exit;
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $index = $input['index'] ?? -1;
    $product = $input['product'] ?? [];

    if ($action === 'add') {
        $products[] = $product;
    } elseif ($action === 'edit' && $index !== -1 && isset($products[$index])) {
        $products[$index] = $product;
    } elseif ($action === 'delete' && $index !== -1 && isset($products[$index])) {
        array_splice($products, $index, 1);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid action or index']);
        exit;
    }

    // Guardar los cambios en el archivo JSON
    file_put_contents($jsonFilePath, json_encode($products, JSON_UNESCAPED_UNICODE));

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}
?>