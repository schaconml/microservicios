<?php

namespace App\Controllers;

use App\Contracts\DevolucionServiceInterface;
use App\Exceptions\ReglaNegocioException;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



// Traduce HTTP a llamadas del servicio y de vuelta a JSON (sin reglas de negocio)
class DevolucionController
{
    public function __construct(
        private DevolucionServiceInterface $servicio,
        private JsonResponse $json
    ) {
    }



    // GET /devoluciones
    public function listar(Request $request, Response $response): Response
    {
        return $this->ejecutar($response, 'Listado de devoluciones', fn() => $this->servicio->listar());
    }



    // GET /devoluciones/pendientes
    public function pendientes(Request $request, Response $response): Response
    {
        return $this->ejecutar($response, 'Reservas pendientes de devolución', fn() => $this->servicio->pendientes());
    }



    // POST /devoluciones
    public function registrar(Request $request, Response $response): Response
    {
        $datos = $this->leerCuerpo($request);

        return $this->ejecutar($response, 'Devolución registrada correctamente', fn() => $this->servicio->registrar($datos));
    }



    // Devuelve el cuerpo JSON como arreglo (vacío si no vino o no era válido)
    private function leerCuerpo(Request $request): array
    {
        $cuerpo = $request->getParsedBody();

        return is_array($cuerpo) ? $cuerpo : [];
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
