<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;



// Operaciones de disponibilidad que el controlador necesita (el servicio las implementa, solo lectura)
interface DisponibilidadServiceInterface
{
    public function consultar(?string $categoria, ?string $fechaInicio, ?string $fechaFin): Collection;

    public function resumen(): array;
}
