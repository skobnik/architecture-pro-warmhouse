<?php

namespace TelemetryService\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TelemetryService\Services\TelemetryService;
use TelemetryService\Services\IntegrationService;

class TelemetryController
{
    private TelemetryService $telemetryService;
    private IntegrationService $integrationService;

    public function __construct(TelemetryService $telemetryService)
    {
        $this->telemetryService = $telemetryService;
        $this->integrationService = new IntegrationService();
    }

    public function getDeviceValue(Request $request, Response $response, array $args): Response
    {
        try {
            $deviceId = $args['id'] ?? '';

            if (empty($deviceId)) {
                return $this->errorResponse($response, 'Device ID is required', 400);
            }

            $value = $this->telemetryService->getCurrentValue($deviceId);

            if ($value === null) {
                return $this->errorResponse($response, 'Device value not found', 404);
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $value
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch device value: ' . $e->getMessage());
        }
    }

    public function updateDeviceValue(Request $request, Response $response, array $args): Response
    {
        try {
            $deviceId = $args['id'] ?? '';
            $data = $request->getParsedBody();

            if (empty($deviceId)) {
                return $this->errorResponse($response, 'Device ID is required', 400);
            }

            if (!isset($data['value'])) {
                return $this->errorResponse($response, 'Value is required', 400);
            }

            $result = $this->telemetryService->updateValue($deviceId, $data['value']);

            if (!$result) {
                return $this->errorResponse($response, 'Device not found', 404);
            }

            // ИНТЕГРАЦИЯ: Уведомляем монолит об изменении телеметрии
            $this->integrationService->notifyMonolithAboutTelemetry('telemetry_updated', [
                'device_id' => $deviceId,
                'value' => $data['value'],
                'device_data' => $result
            ]);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $result
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to update device value: ' . $e->getMessage());
        }
    }

    public function pollDevice(Request $request, Response $response, array $args): Response
    {
        try {
            $deviceId = $args['id'] ?? '';

            if (empty($deviceId)) {
                return $this->errorResponse($response, 'Device ID is required', 400);
            }

            $value = $this->telemetryService->pollDevice($deviceId);

            if ($value === null) {
                return $this->errorResponse($response, 'Device polling failed', 404);
            }

            // ИНТЕГРАЦИЯ: Уведомляем монолит о опросе устройства
            $this->integrationService->notifyMonolithAboutTelemetry('device_polled', [
                'device_id' => $deviceId,
                'value' => $value
            ]);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => ['value' => $value]
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to poll device: ' . $e->getMessage());
        }
    }

    public function pollAllDevices(Request $request, Response $response): Response
    {
        try {
            $results = $this->telemetryService->pollAllDevices();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $results
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to poll devices: ' . $e->getMessage());
        }
    }

    private function errorResponse(Response $response, string $message, int $code = 500): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $message
        ]));

        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
