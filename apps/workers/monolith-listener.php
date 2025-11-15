<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

echo "🚀 Starting Monolith Event Listener...\n";

$maxRetries = 5;
$retryCount = 0;

while ($retryCount < $maxRetries) {
    try {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Объявляем exchanges
        $channel->exchange_declare('device_events', 'direct', false, true, false);
        $channel->exchange_declare('telemetry_events', 'direct', false, true, false);

        // Создаем очередь для монолита
        $channel->queue_declare('monolith_events', false, true, false, false);

        // Биндим события
        $channel->queue_bind('monolith_events', 'device_events', 'device_created');
        $channel->queue_bind('monolith_events', 'device_events', 'device_updated');
        $channel->queue_bind('monolith_events', 'device_events', 'device_deleted');
        $channel->queue_bind('monolith_events', 'telemetry_events', 'telemetry_updated');
        $channel->queue_bind('monolith_events', 'telemetry_events', 'device_polled');

        echo "✅ Connected to RabbitMQ\n";
        echo "📡 Listening for events from microservices...\n\n";

        $callback = function ($msg) {
            $event = json_decode($msg->body, true);

            echo "🎯 [MONOLITH] Received: {$event['event_type']} from {$event['service']}\n";
            echo "   Device: " . ($event['data']['name'] ?? $event['data']['device_id'] ?? 'unknown') . "\n";
            echo "   Value: " . ($event['data']['value'] ?? 'N/A') . "\n";
            echo "   Time: {$event['timestamp']}\n";
            echo "   ---\n";

            $msg->ack();
        };

        $channel->basic_consume('monolith_events', '', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }
    } catch (Exception $e) {
        $retryCount++;
        echo "❌ Connection failed (attempt $retryCount/$maxRetries): " . $e->getMessage() . "\n";

        if ($retryCount < $maxRetries) {
            echo "🔄 Retrying in 5 seconds...\n";
            sleep(5);
        } else {
            echo "💥 Max retries reached. Exiting.\n";
            exit(1);
        }
    }
}
