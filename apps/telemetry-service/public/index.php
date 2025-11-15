<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use TelemetryService\Controllers\TelemetryController;
use TelemetryService\Services\TelemetryService;
use TelemetryService\Services\IntegrationService;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Middleware для правильных заголовков
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('X-Content-Type-Options', 'nosniff');
});

$app->addBodyParsingMiddleware();

// Обработка ошибок
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$telemetryService = new TelemetryService();
$telemetryController = new TelemetryController($telemetryService);

// Маршруты
$app->get('/devices/{id}/value', [$telemetryController, 'getDeviceValue']);
$app->patch('/devices/{id}/value', [$telemetryController, 'updateDeviceValue']);
$app->post('/devices/{id}/poll', [$telemetryController, 'pollDevice']);
$app->post('/devices/poll', [$telemetryController, 'pollAllDevices']);

// Health check
$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'service' => 'telemetry-service',
        'timestamp' => date('c')
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Корневой маршрут
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'service' => 'telemetry-service',
        'timestamp' => date('c'),
        'endpoints' => [
            'GET /devices/{id}/value' => 'Get device value',
            'PATCH /devices/{id}/value' => 'Update device value',
            'POST /devices/{id}/poll' => 'Poll device for value',
            'POST /devices/poll' => 'Poll all devices',
            'GET /health' => 'Health check'
        ]
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Обработчик для 404
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'error',
        'message' => 'Route not found'
    ]));
    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
});

$app->run();
