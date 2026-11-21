<?php

namespace App\Contracts;

use App\Models\Reserva;
use Illuminate\Database\Eloquent\Collection;



// Operaciones de devoluciones que el controlador necesita (el servicio las implementa)
interface DevolucionServiceInterface
{
    public function listar(): Collection;

    public function pendientes(): Collection;

    public function registrar(array $datos): Reserva;
}
