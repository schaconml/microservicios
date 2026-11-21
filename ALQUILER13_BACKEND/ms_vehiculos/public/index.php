<?php

use App\Controllers\VehiculoController;
use App\Middlewares\AuthMiddleware;
use App\Services\VehiculoService;
use App\Support\ErrorHandler;
use App\Support\JsonResponse;
use Slim\Factory\AppFactory;



require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

date_default_timezone_set('America/Bogota');



// Ensamblaje de dependencias: el servicio entra al controlador por constructor
$json = new JsonResponse();
$controlador = new VehiculoController(new VehiculoService(), $json);



// Middlewares en orden LIFO: el último que se agrega es el primero que se ejecuta
$app = AppFactory::create();
$fabrica = $app->getResponseFactory();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$rutas = require __DIR__ . '/../routes/api.php';
$rutas($app, $controlador);

$app->add(new AuthMiddleware($json, $fabrica));

$cors = require __DIR__ . '/../config/cors.php';
$cors($app);

$errores = $app->addErrorMiddleware(true, true, true);
$errores->setDefaultErrorHandler(new ErrorHandler($json, $fabrica));

$app->run();
