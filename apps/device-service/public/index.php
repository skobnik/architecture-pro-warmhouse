<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use DeviceService\Controllers\DeviceController;
use DeviceService\Services\DeviceService;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$deviceService = new DeviceService();
$deviceController = new DeviceController($deviceService);

// Правильные маршруты
$app->get('/devices', [$deviceController, 'getDevices']);
$app->get('/devices/{id}', [$deviceController, 'getDevice']);
$app->post('/devices', [$deviceController, 'createDevice']);
$app->put('/devices/{id}', [$deviceController, 'updateDevice']);
$app->delete('/devices/{id}', [$deviceController, 'deleteDevice']);

// Health check
$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok', 'service' => 'device-service']));
    return $response->withHeader('Content-Type', 'application/json');
});

//Корневой маршрут
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'service' => 'device-service',
        'endpoints' => [
            'GET /devices' => 'Get all devices',
            'GET /devices/{id}' => 'Get device by ID',
            'POST /devices' => 'Create device',
            'PUT /devices/{id}' => 'Update device',
            'DELETE /devices/{id}' => 'Delete device',
            'GET /health' => 'Health check'
        ]
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
