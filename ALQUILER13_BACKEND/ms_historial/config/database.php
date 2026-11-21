<?php

use Illuminate\Database\Capsule\Manager as Capsule;



// Conexión Eloquent a la base de datos única del proyecto
$capsule = new Capsule();

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'alquiler_vehiculos_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
