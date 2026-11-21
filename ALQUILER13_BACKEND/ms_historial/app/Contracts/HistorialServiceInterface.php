<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;



// Operaciones de historial que el controlador necesita (el servicio las implementa, solo lectura)
interface HistorialServiceInterface
{
    public function listar(?string $estado, ?string $desde, ?string $hasta): Collection;

    public function porVehiculo(int $id): array;

    public function porCliente(int $id): array;

    public function resumen(): array;
}
