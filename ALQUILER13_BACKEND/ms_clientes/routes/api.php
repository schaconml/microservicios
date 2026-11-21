<?php

use App\Controllers\ClienteController;
use Slim\App;



// Solo relaciona cada ruta con un método del controlador, sin lógica propia
return function (App $app, ClienteController $controlador): void {
    $app->get('/clientes', [$controlador, 'listar']);
    $app->get('/clientes/{id:[0-9]+}', [$controlador, 'obtener']);
    $app->post('/clientes', [$controlador, 'crear']);
    $app->put('/clientes/{id:[0-9]+}', [$controlador, 'actualizar']);
    $app->delete('/clientes/{id:[0-9]+}', [$controlador, 'eliminar']);
};
