<?php

namespace App\Contracts;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Collection;



// Operaciones de clientes que el controlador necesita (el servicio las implementa)
interface ClienteServiceInterface
{
    public function listar(): Collection;

    public function obtener(int $id): Cliente;

    public function crear(array $datos): Cliente;

    public function actualizar(int $id, array $datos): Cliente;

    public function eliminar(int $id): void;
}
