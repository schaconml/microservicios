<?php

namespace App\Support;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Throwable;



// Convierte las excepciones no controladas al formato de error JSON del contrato
class ErrorHandler
{
    public function __construct(
        private JsonResponse $json,
        private ResponseFactoryInterface $fabrica
    ) {
    }



    // Slim lo invoca desde el middleware de errores registrado en public/index.php
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $excepcion,
        bool $mostrarDetalles,
        bool $registrar,
        bool $registrarDetalles
    ): ResponseInterface {
        $respuesta = $this->fabrica->createResponse();

        if ($excepcion instanceof HttpNotFoundException) {
            return $this->json->error($respuesta, 'Ruta no encontrada', 404);
        }

        if ($excepcion instanceof HttpMethodNotAllowedException) {
            return $this->json->error($respuesta, 'Método no permitido', 405);
        }

        $mensaje = 'Error interno del servidor';
        if ($mostrarDetalles) {
            $mensaje = $mensaje . ': ' . $excepcion->getMessage();
        }

        return $this->json->error($respuesta, $mensaje, 500);
    }
}
