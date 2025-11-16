<?php

namespace DeviceService\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DeviceService\Services\DeviceService;
use DeviceService\Services\IntegrationService;

class DeviceController
{
    private DeviceService $deviceService;
    private IntegrationService $integrationService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
        $this->integrationService = new IntegrationService();
    }

    // ВОЗВРАЩАЕМ getDevices метод
    public function getDevices(Request $request, Response $response): Response
    {
        try {
            $devices = $this->deviceService->getAllDevices();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $devices
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch devices: ' . $e->getMessage());
        }
    }

    public function getDevice(Request $request, Response $response, array $args): Response
    {
        try {
            $deviceId = $args['id'] ?? '';

            if (empty($deviceId)) {
                return $this->errorResponse($response, 'Device ID is required', 400);
            }

            $device = $this->deviceService->getDeviceById($deviceId);

            if (!$device) {
                return $this->errorResponse($response, 'Device not found', 404);
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $device
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch device: ' . $e->getMessage());
        }
    }

    public function createDevice(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['name']) || empty($data['type'])) {
                return $this->errorResponse($response, 'Name and type are required', 400);
            }

            $device = $this->deviceService->createDevice([
                'name' => $data['name'],
                'type' => $data['type'],
                'location' => $data['location'] ?? '',
                'ip_address' => $data['ip_address'] ?? '192.168.1.100'
            ]);

            // ИНТЕГРАЦИЯ: Уведомляем монолит о новом устройстве
            $this->integrationService->notifyMonolithAboutDevice('device_created', $device);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $device
            ]));

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to create device: ' . $e->getMessage());
        }
    }

    public function updateDevice(Request $request, Response $response, array $args): Response
    {
        try {
            $deviceId = $args['id'] ?? '';
            $data = $request->getParsedBody();

            if (empty($deviceId)) {
                return $this->errorResponse($response, 'Device ID is required', 400);
            }

            $device = $this->deviceService->updateDevice($deviceId, $data);

            if (!$device) {
                return $this->errorResponse($response, 'Device not found', 404);
            }

            // ИНТЕГРАЦИЯ: Уведомляем монолит об обновлении устройства
            $this->integrationService->notifyMonolithAboutDevice('device_updated', $device);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $device
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to update device: ' . $e->getMessage());
        }
    }

    public function deleteDevice(Request $request, Response $response, array $args): Response
    {
        try {
            $deviceId = $args['id'] ?? '';

            if (empty($deviceId)) {
                return $this->errorResponse($response, 'Device ID is required', 400);
            }

            $result = $this->deviceService->deleteDevice($deviceId);

            if (!$result) {
                return $this->errorResponse($response, 'Device not found', 404);
            }

            // ИНТЕГРАЦИЯ: Уведомляем монолит об удалении устройства
            $this->integrationService->notifyMonolithAboutDevice('device_deleted', ['id' => $deviceId]);

            return $response->withStatus(204);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to delete device: ' . $e->getMessage());
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
