<?php

use App\Controllers\HistorialController;
use Slim\App;



// Solo relaciona cada ruta con un método del controlador, sin lógica propia
return function (App $app, HistorialController $controlador): void {
    $app->get('/historial', [$controlador, 'listar']);
    $app->get('/historial/resumen', [$controlador, 'resumen']);
    $app->get('/historial/vehiculo/{id:[0-9]+}', [$controlador, 'porVehiculo']);
    $app->get('/historial/cliente/{id:[0-9]+}', [$controlador, 'porCliente']);
};
