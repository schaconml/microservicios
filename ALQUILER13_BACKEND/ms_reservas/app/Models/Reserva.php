<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



// Modelo de la tabla reservas, con sus relaciones a cliente y vehículo
class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = ['cliente_id', 'vehiculo_id', 'fecha_inicio', 'fecha_fin', 'fecha_devolucion_real', 'estado'];

    protected $casts = [
        'cliente_id' => 'integer',
        'vehiculo_id' => 'integer',
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'fecha_devolucion_real' => 'date:Y-m-d',
    ];



    // Cliente dueño de la reserva (se carga con with('cliente'))
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }



    // Vehículo reservado (se carga con with('vehiculo'))
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }



    // Las fechas salen como "2026-11-20 10:30:00" y no en formato ISO
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
