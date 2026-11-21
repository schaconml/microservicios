<?php

namespace App\Middlewares;

use App\Support\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;



// Valida el token de la cabecera Authorization (PSR-15)
class AuthMiddleware implements MiddlewareInterface
{
    private const TOKEN = 'Bearer 12345';

    public function __construct(
        private JsonResponse $json,
        private ResponseFactoryInterface $fabrica
    ) {
    }



    // Deja pasar OPTIONS porque el navegador no envía el token en esa petición previa
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        if ($request->getHeaderLine('Authorization') !== self::TOKEN) {
            return $this->json->error($this->fabrica->createResponse(), 'No autorizado', 401);
        }

        return $handler->handle($request);
    }
}
