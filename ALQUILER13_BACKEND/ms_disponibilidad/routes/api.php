<?php

use App\Controllers\DisponibilidadController;
use Slim\App;



// Solo relaciona cada ruta con un método del controlador, sin lógica propia
return function (App $app, DisponibilidadController $controlador): void {
    $app->get('/disponibilidad', [$controlador, 'consultar']);
    $app->get('/disponibilidad/resumen', [$controlador, 'resumen']);
};
