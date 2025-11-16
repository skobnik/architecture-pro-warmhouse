<?
header('Content-Type: application/json');

$request_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'];
$query_params = [];

if (isset($parsed_url['query'])) {
    parse_str($parsed_url['query'], $query_params);
}

$path_parts = explode('/', trim($path, '/'));
$base_path = $path_parts[0] ?? '';

if ($base_path === 'temperature') {

    $location = $query_params['location'] ?? '';

    echo json_encode([
        'Value' => getTemperature(),
        'Location' => $location,
        'Unit' => 'celsius',
        'Status' => 'active',
        'Timestamp' => date('c'),
        'Description' => '',
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}

function getTemperature()
{
    return rand(5, 50);
}
