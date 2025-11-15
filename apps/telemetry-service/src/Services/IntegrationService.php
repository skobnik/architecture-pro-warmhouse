<?php

namespace TelemetryService\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class IntegrationService
{
    private $connection;
    private $channel;

    public function __construct()
    {
        try {
            $this->connection = new AMQPStreamConnection(
                $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
                $_ENV['RABBITMQ_PORT'] ?? 5672,
                $_ENV['RABBITMQ_USER'] ?? 'guest',
                $_ENV['RABBITMQ_PASS'] ?? 'guest'
            );

            $this->channel = $this->connection->channel();

            // Объявляем exchange для телеметрии
            $this->channel->exchange_declare('telemetry_events', 'direct', false, true, false);

            echo "✅ RabbitMQ connected for Telemetry Service\n";
        } catch (Exception $e) {
            echo "❌ RabbitMQ connection failed: " . $e->getMessage() . "\n";
        }
    }

    public function notifyMonolithAboutTelemetry(string $eventType, array $telemetryData): bool
    {
        try {
            if (!$this->channel || !$this->channel->is_open()) {
                echo "⚠️ RabbitMQ not available, skipping event: {$eventType}\n";
                return false;
            }

            $message = [
                'event_type' => $eventType,
                'service' => 'telemetry-service',
                'data' => $telemetryData,
                'timestamp' => date('c')
            ];

            $amqpMessage = new AMQPMessage(
                json_encode($message),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            // Публикуем в exchange с routing key = event_type
            $this->channel->basic_publish($amqpMessage, 'telemetry_events', $eventType);

            $deviceId = $telemetryData['device_id'] ?? 'unknown';
            $value = $telemetryData['value'] ?? 'unknown';

            echo "✅ [RABBITMQ] Telemetry event published: {$eventType} for device: {$deviceId} = {$value}\n";
            return true;
        } catch (Exception $e) {
            echo "❌ RabbitMQ publish failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function __destruct()
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (Exception $e) {
            // Игнорируем ошибки при закрытии
        }
    }
}
