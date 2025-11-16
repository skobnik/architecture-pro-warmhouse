<?php

namespace DeviceService\Services;

use DeviceService\Models\Device;
use Predis\Client as RedisClient;

class DeviceService
{
    private RedisClient $redis;

    public function __construct()
    {
        $this->redis = new RedisClient([
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => 6379
        ]);
    }

    public function getAllDevices(): array
    {
        try {
            echo "Fetching all devices from database...\n";

            $devices = Device::all()->toArray();
            echo "Found " . count($devices) . " devices\n";

            return $devices;
        } catch (\Exception $e) {
            echo "Error fetching devices: " . $e->getMessage() . "\n";
            return [];
        }
    }

    public function getDeviceById(string $id): ?array
    {
        try {
            echo "Fetching device with ID: $id\n";

            $device = Device::find($id);

            if (!$device) {
                echo "Device not found\n";
                return null;
            }

            echo "Device found: " . $device->name . "\n";
            return $device->toArray();
        } catch (\Exception $e) {
            echo "Error fetching device: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function createDevice(array $data): array
    {
        try {
            echo "Creating new device: " . $data['name'] . "\n";

            $device = Device::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'location' => $data['location'] ?? '',
                'ip_address' => $data['ip_address'] ?? '192.168.1.100',
                'status' => 'active',
                'value' => null
            ]);

            echo "Device created with ID: " . $device->id . "\n";
            return $device->toArray();
        } catch (\Exception $e) {
            echo "Error creating device: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function updateDevice(string $id, array $data): ?array
    {
        try {
            echo "Updating device with ID: $id\n";

            $device = Device::find($id);

            if (!$device) {
                echo "Device not found for update\n";
                return null;
            }

            $device->update($data);
            echo "Device updated successfully\n";

            return $device->toArray();
        } catch (\Exception $e) {
            echo "Error updating device: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function deleteDevice(string $id): bool
    {
        try {
            echo "Deleting device with ID: $id\n";

            $device = Device::find($id);

            if (!$device) {
                echo "Device not found for deletion\n";
                return false;
            }

            $result = $device->delete();
            echo "Device deleted: " . ($result ? 'success' : 'failed') . "\n";

            return $result;
        } catch (\Exception $e) {
            echo "Error deleting device: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
