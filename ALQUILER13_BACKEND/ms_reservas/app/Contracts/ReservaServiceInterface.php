<?php

namespace App\Contracts;

use App\Models\Reserva;
use Illuminate\Database\Eloquent\Collection;



// Operaciones de reservas que el controlador necesita (el servicio las implementa)
interface ReservaServiceInterface
{
    public function listar(?string $estado): Collection;

    public function obtener(int $id): Reserva;

    public function crear(array $datos): Reserva;

    public function cancelar(int $id): Reserva;
}
