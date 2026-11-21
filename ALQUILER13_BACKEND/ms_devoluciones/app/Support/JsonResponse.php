<?php

namespace App\Support;

use Psr\Http\Message\ResponseInterface;



// Arma las respuestas JSON con el formato uniforme de los contratos (success / error)
class JsonResponse
{
    // Respuesta de éxito; la usan los controladores y el manejador de errores
    public function exito(ResponseInterface $respuesta, string $mensaje, mixed $data = null, int $estado = 200): ResponseInterface
    {
        $cuerpo = [
            'status' => 'success',
            'message' => $mensaje,
            'data' => $data ?? new \stdClass(),
        ];

        return $this->escribir($respuesta, $cuerpo, $estado);
    }



    // Respuesta de error; la usan los controladores, el AuthMiddleware y el manejador de errores
    public function error(ResponseInterface $respuesta, string $mensaje, int $estado): ResponseInterface
    {
        $cuerpo = [
            'status' => 'error',
            'error' => $mensaje,
        ];

        return $this->escribir($respuesta, $cuerpo, $estado);
    }



    // Escribe el JSON en el cuerpo y fija cabecera y código HTTP
    private function escribir(ResponseInterface $respuesta, array $cuerpo, int $estado): ResponseInterface
    {
        $json = json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $respuesta->getBody()->write($json);

        return $respuesta
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($estado);
    }
}
