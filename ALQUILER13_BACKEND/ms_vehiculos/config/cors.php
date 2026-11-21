<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\App;



// Registra la ruta de OPTIONS y el middleware que agrega las cabeceras CORS
return function (App $app): void {
    $app->options('/{routes:.+}', fn(Request $request, Response $response) => $response);



    $app->add(function (Request $request, Handler $handler): Response {
        $respuesta = $handler->handle($request);
        $origen = $request->getHeaderLine('Origin');

        return $respuesta
            ->withHeader('Access-Control-Allow-Origin', $origen !== '' ? $origen : '*')
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    });
};
