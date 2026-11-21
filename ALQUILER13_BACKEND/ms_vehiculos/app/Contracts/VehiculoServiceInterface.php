<?php

namespace App\Contracts;

use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;



// Operaciones de vehículos que el controlador necesita (el servicio las implementa)
interface VehiculoServiceInterface
{
    public function listar(?string $estado): Collection;

    public function obtener(int $id): Vehiculo;

    public function crear(array $datos): Vehiculo;

    public function actualizar(int $id, array $datos): Vehiculo;

    public function cambiarEstado(int $id, array $datos): Vehiculo;

    public function eliminar(int $id): void;
}
