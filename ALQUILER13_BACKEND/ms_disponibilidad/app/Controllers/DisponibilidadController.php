<?php

namespace App\Controllers;

use App\Contracts\DisponibilidadServiceInterface;
use App\Exceptions\ReglaNegocioException;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



// Traduce HTTP a llamadas del servicio y de vuelta a JSON (sin reglas de negocio)
class DisponibilidadController
{
    public function __construct(
        private DisponibilidadServiceInterface $servicio,
        private JsonResponse $json
    ) {
    }



    // GET /disponibilidad?categoria=&fecha_inicio=&fecha_fin=
    public function consultar(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $categoria = isset($params['categoria']) && is_string($params['categoria']) ? $params['categoria'] : null;
        $inicio = isset($params['fecha_inicio']) && is_string($params['fecha_inicio']) ? $params['fecha_inicio'] : null;
        $fin = isset($params['fecha_fin']) && is_string($params['fecha_fin']) ? $params['fecha_fin'] : null;

        return $this->ejecutar($response, 'Vehículos disponibles', fn() => $this->servicio->consultar($categoria, $inicio, $fin));
    }



    // GET /disponibilidad/resumen
    public function resumen(Request $request, Response $response): Response
    {
        return $this->ejecutar($response, 'Resumen de disponibilidad', fn() => $this->servicio->resumen());
    }



    // Corre la acción del servicio y convierte los errores de dominio en respuestas JSON de error
    private function ejecutar(Response $response, string $mensaje, callable $accion, int $estado = 200): Response
    {
        try {
            $data = $accion();
        } catch (ReglaNegocioException $e) {
            return $this->json->error($response, $e->getMessage(), $e->getCode());
        }

        return $this->json->exito($response, $mensaje, $data, $estado);
    }
}
