<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'] ?? 'pgsql',
    'host' => $_ENV['DB_HOST'] ?? 'postgres',
    'database' => $_ENV['DB_DATABASE'] ?? 'smarthome',
    'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? 'postgres',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();