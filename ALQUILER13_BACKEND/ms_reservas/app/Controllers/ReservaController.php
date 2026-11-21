<?php

namespace App\Controllers;

use App\Contracts\ReservaServiceInterface;
use App\Exceptions\ReglaNegocioException;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



// Traduce HTTP a llamadas del servicio y de vuelta a JSON (sin reglas de negocio)
class ReservaController
{
    public function __construct(
        private ReservaServiceInterface $servicio,
        private JsonResponse $json
    ) {
    }



    // GET /reservas
    public function listar(Request $request, Response $response): Response
    {
        $estado = $request->getQueryParams()['estado'] ?? null;
        $estado = is_string($estado) ? $estado : null;

        return $this->ejecutar($response, 'Listado de reservas', fn() => $this->servicio->listar($estado));
    }



    // GET /reservas/{id}
    public function obtener(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Reserva encontrada', fn() => $this->servicio->obtener($id));
    }



    // POST /reservas
    public function crear(Request $request, Response $response): Response
    {
        $datos = $this->leerCuerpo($request);

        return $this->ejecutar($response, 'Reserva creada correctamente', fn() => $this->servicio->crear($datos), 201);
    }



    // PATCH /reservas/{id}/cancelar
    public function cancelar(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Reserva cancelada correctamente', fn() => $this->servicio->cancelar($id));
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
