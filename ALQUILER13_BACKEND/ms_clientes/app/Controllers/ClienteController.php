<?php

namespace App\Controllers;

use App\Contracts\ClienteServiceInterface;
use App\Exceptions\ReglaNegocioException;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



// Traduce HTTP a llamadas del servicio y de vuelta a JSON (sin reglas de negocio)
class ClienteController
{
    public function __construct(
        private ClienteServiceInterface $servicio,
        private JsonResponse $json
    ) {
    }



    // GET /clientes
    public function listar(Request $request, Response $response): Response
    {
        return $this->ejecutar($response, 'Listado de clientes', fn() => $this->servicio->listar());
    }



    // GET /clientes/{id}
    public function obtener(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Cliente encontrado', fn() => $this->servicio->obtener($id));
    }



    // POST /clientes
    public function crear(Request $request, Response $response): Response
    {
        $datos = $this->leerCuerpo($request);

        return $this->ejecutar($response, 'Cliente creado correctamente', fn() => $this->servicio->crear($datos), 201);
    }



    // PUT /clientes/{id}
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $datos = $this->leerCuerpo($request);

        return $this->ejecutar($response, 'Cliente actualizado correctamente', fn() => $this->servicio->actualizar($id, $datos));
    }



    // DELETE /clientes/{id}
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        return $this->ejecutar($response, 'Cliente eliminado correctamente', function () use ($id) {
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
