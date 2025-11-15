<?php

namespace DeviceService\Services;

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

            // Объявляем exchange для событий
            $this->channel->exchange_declare('device_events', 'direct', false, true, false);

            echo "✅ RabbitMQ connected for Device Service\n";
        } catch (Exception $e) {
            echo "❌ RabbitMQ connection failed: " . $e->getMessage() . "\n";
            // Позволяем сервису работать без RabbitMQ
        }
    }

    public function notifyMonolithAboutDevice(string $eventType, array $deviceData): bool
    {
        try {
            if (!$this->channel || !$this->channel->is_open()) {
                echo "⚠️ RabbitMQ not available, skipping event: {$eventType}\n";
                return false;
            }

            $message = [
                'event_type' => $eventType,
                'service' => 'device-service',
                'data' => $deviceData,
                'timestamp' => date('c')
            ];

            $amqpMessage = new AMQPMessage(
                json_encode($message),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            // Публикуем в exchange с routing key = event_type
            $this->channel->basic_publish($amqpMessage, 'device_events', $eventType);

            echo "✅ [RABBITMQ] Event published: {$eventType} for device: {$deviceData['name']}\n";
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
