<?php

use App\Controllers\ReservaController;
use Slim\App;



// Solo relaciona cada ruta con un método del controlador, sin lógica propia
return function (App $app, ReservaController $controlador): void {
    $app->get('/reservas', [$controlador, 'listar']);
    $app->get('/reservas/{id:[0-9]+}', [$controlador, 'obtener']);
    $app->post('/reservas', [$controlador, 'crear']);
    $app->patch('/reservas/{id:[0-9]+}/cancelar', [$controlador, 'cancelar']);
};
