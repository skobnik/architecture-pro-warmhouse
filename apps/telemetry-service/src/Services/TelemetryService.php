<?php

namespace TelemetryService\Services;

use TelemetryService\Models\Device;
use GuzzleHttp\Client;
use Predis\Client as RedisClient;

class TelemetryService
{
    private Client $httpClient;
    private RedisClient $redis;

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 5]);
        $this->redis = new RedisClient([
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => 6379
        ]);
    }

    public function getCurrentValue(string $deviceId): ?array
    {
        try {
            echo "Getting current value for device: $deviceId\n";

            $device = Device::find($deviceId);

            if (!$device) {
                echo "Device not found\n";
                return null;
            }

            $valueData = [
                'value' => $device->value,
                'last_updated' => $device->last_seen,
                'status' => $device->status
            ];

            echo "Value found: " . ($device->value ?? 'null') . "\n";
            return $valueData;
        } catch (\Exception $e) {
            echo "Error getting device value: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function updateValue(string $deviceId, $value): ?array
    {
        try {
            echo "Updating value for device: $deviceId to $value\n";

            $device = Device::find($deviceId);

            if (!$device) {
                echo "Device not found for value update\n";
                return null;
            }

            $device->update([
                'value' => $value,
                'last_seen' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ]);

            echo "Value updated successfully\n";
            return $device->toArray();
        } catch (\Exception $e) {
            echo "Error updating device value: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function pollDevice(string $deviceId): ?float
    {
        try {
            echo "Polling device: $deviceId\n";

            $device = Device::find($deviceId);

            if (!$device) {
                echo "Device not found for polling\n";
                return null;
            }

            // Имитация опроса устройства - генерируем случайное значение
            $temperature = rand(180, 250) / 10.0; // 18.0 - 25.0

            $device->update([
                'value' => $temperature,
                'last_seen' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ]);

            echo "Polling successful, value: $temperature\n";
            return $temperature;
        } catch (\Exception $e) {
            echo "Error polling device: " . $e->getMessage() . "\n";
            if (isset($device)) {
                $device->update(['status' => 'error']);
            }
            return null;
        }
    }

    public function pollAllDevices(): array
    {
        try {
            echo "Polling all devices\n";

            $devices = Device::where('status', '!=', 'inactive')->get();
            $results = [];

            foreach ($devices as $device) {
                $value = $this->pollDevice($device->id);
                $results[$device->id] = [
                    'success' => $value !== null,
                    'value' => $value
                ];
            }

            echo "Polled " . count($devices) . " devices\n";
            return $results;
        } catch (\Exception $e) {
            echo "Error polling all devices: " . $e->getMessage() . "\n";
            return [];
        }
    }
}
