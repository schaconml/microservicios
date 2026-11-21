<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;



// Modelo de la tabla vehiculos
class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = ['marca', 'modelo', 'anio', 'categoria', 'estado'];

    protected $casts = [
        'anio' => 'integer',
    ];



    // Las fechas salen como "2026-11-20 10:30:00" y no en formato ISO
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
