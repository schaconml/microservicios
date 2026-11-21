<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;



// Modelo de la tabla vehiculos; aquí solo se consulta y se cambia su estado
class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $casts = [
        'anio' => 'integer',
    ];



    // Las fechas salen como "2026-11-20 10:30:00" y no en formato ISO
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
