<?php

use App\Controllers\VehiculoController;
use Slim\App;



// Solo relaciona cada ruta con un método del controlador, sin lógica propia
return function (App $app, VehiculoController $controlador): void {
    $app->get('/vehiculos', [$controlador, 'listar']);
    $app->get('/vehiculos/{id:[0-9]+}', [$controlador, 'obtener']);
    $app->post('/vehiculos', [$controlador, 'crear']);
    $app->put('/vehiculos/{id:[0-9]+}', [$controlador, 'actualizar']);
    $app->patch('/vehiculos/{id:[0-9]+}/estado', [$controlador, 'cambiarEstado']);
    $app->delete('/vehiculos/{id:[0-9]+}', [$controlador, 'eliminar']);
};
