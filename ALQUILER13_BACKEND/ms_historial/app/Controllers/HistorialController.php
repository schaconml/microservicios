<?php

namespace App\Controllers;

use App\Contracts\HistorialServiceInterface;
use App\Exceptions\ReglaNegocioException;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



// Traduce HTTP a llamadas del servicio y de vuelta a JSON (sin reglas de negocio)
class HistorialController
{
    public function __construct(
        private HistorialServiceInterface $servicio,
        private JsonResponse $json
    ) {
    }



    // GET /historial?estado=&desde=&hasta=
    public function listar(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $estado = isset($params['estado']) && is_string($params['estado']) ? $params['estado'] : null;
        $desde = isset($params['desde']) && is_string($params['desde']) ? $params['desde'] : null;
        $hasta = isset($params['hasta']) && is_string($params['hasta']) ? $params['hasta'] : null;

        return $this->ejecutar($response, 'Historial de alquileres', fn() => $this->servicio->listar($estado, $desde, $hasta));
    }



    // GET /historial/vehiculo/{id}
    public function porVehiculo(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Historial del vehículo', fn() => $this->servicio->porVehiculo($id));
    }



    // GET /historial/cliente/{id}
    public function porCliente(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Historial del cliente', fn() => $this->servicio->porCliente($id));
    }



    // GET /historial/resumen
    public function resumen(Request $request, Response $response): Response
    {
        return $this->ejecutar($response, 'Resumen del historial', fn() => $this->servicio->resumen());
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
