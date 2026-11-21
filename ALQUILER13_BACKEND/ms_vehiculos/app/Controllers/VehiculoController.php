<?php

namespace App\Controllers;

use App\Contracts\VehiculoServiceInterface;
use App\Exceptions\ReglaNegocioException;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



// Traduce HTTP a llamadas del servicio y de vuelta a JSON (sin reglas de negocio)
class VehiculoController
{
    public function __construct(
        private VehiculoServiceInterface $servicio,
        private JsonResponse $json
    ) {
    }



    // GET /vehiculos
    public function listar(Request $request, Response $response): Response
    {
        $estado = $request->getQueryParams()['estado'] ?? null;
        $estado = is_string($estado) ? $estado : null;

        return $this->ejecutar($response, 'Listado de vehículos', fn() => $this->servicio->listar($estado));
    }



    // GET /vehiculos/{id}
    public function obtener(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Vehículo encontrado', fn() => $this->servicio->obtener($id));
    }



    // POST /vehiculos
    public function crear(Request $request, Response $response): Response
    {
        $datos = $this->leerCuerpo($request);

        return $this->ejecutar($response, 'Vehículo creado correctamente', fn() => $this->servicio->crear($datos), 201);
    }



    // PUT /vehiculos/{id}
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $datos = $this->leerCuerpo($request);

        return $this->ejecutar($response, 'Vehículo actualizado correctamente', fn() => $this->servicio->actualizar($id, $datos));
    }



    // PATCH /vehiculos/{id}/estado
    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $datos = $this->leerCuerpo($request);

        return $this->ejecutar($response, 'Estado del vehículo actualizado', fn() => $this->servicio->cambiarEstado($id, $datos));
    }



    // DELETE /vehiculos/{id}
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Vehículo eliminado correctamente', function () use ($id) {
            $this->servicio->eliminar($id);
            return null;
        });
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
