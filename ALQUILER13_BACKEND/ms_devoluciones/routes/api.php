<?php

use App\Controllers\DevolucionController;
use Slim\App;



// Solo relaciona cada ruta con un método del controlador, sin lógica propia
return function (App $app, DevolucionController $controlador): void {
    $app->get('/devoluciones', [$controlador, 'listar']);
    $app->get('/devoluciones/pendientes', [$controlador, 'pendientes']);
    $app->post('/devoluciones', [$controlador, 'registrar']);
};
