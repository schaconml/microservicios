<?php

namespace App\Services;

use App\Contracts\DevolucionServiceInterface;
use App\Exceptions\ConflictoException;
use App\Exceptions\NoEncontradoException;
use App\Exceptions\SolicitudInvalidaException;
use App\Models\Reserva;
use App\Models\Vehiculo;
use DateTime;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;



// Reglas de negocio de devoluciones (Regla A: máquina de estados del vehículo)
class DevolucionService implements DevolucionServiceInterface
{
    // Lista las reservas completadas, la devolución más reciente primero
    public function listar(): Collection
    {
        return Reserva::with(['cliente', 'vehiculo'])
            ->where('estado', 'completada')
            ->orderByDesc('fecha_devolucion_real')
            ->orderByDesc('id')
            ->get();
    }



    // Lista las reservas activas que aún esperan su devolución, las que vencen primero arriba
    public function pendientes(): Collection
    {
        return Reserva::with(['cliente', 'vehiculo'])
            ->where('estado', 'activa')
            ->orderBy('fecha_fin')
            ->orderBy('id')
            ->get();
    }



    // Completa una reserva activa y deja el vehículo disponible o en mantenimiento, dentro de una transacción
    public function registrar(array $datos): Reserva
    {
        $reservaId = filter_var($datos['reserva_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reservaId === false || $reservaId < 1) {
            throw new SolicitudInvalidaException('El reserva_id es obligatorio y debe ser un entero positivo');
        }

        $fecha = $datos['fecha_devolucion_real'] ?? null;
        if ($fecha === null || $fecha === '') {
            $fecha = date('Y-m-d');
        }
        if (!$this->fechaValida($fecha)) {
            throw new SolicitudInvalidaException('La fecha_devolucion_real debe tener el formato YYYY-MM-DD');
        }

        $estadoVehiculo = $datos['estado_vehiculo'] ?? null;
        if ($estadoVehiculo === null || $estadoVehiculo === '') {
            $estadoVehiculo = 'disponible';
        }
        if (!is_string($estadoVehiculo) || !in_array($estadoVehiculo, ['disponible', 'mantenimiento'], true)) {
            throw new SolicitudInvalidaException('El estado_vehiculo debe ser disponible o mantenimiento');
        }

        Capsule::transaction(function () use ($reservaId, $fecha, $estadoVehiculo) {
            $reserva = Reserva::query()->lockForUpdate()->find($reservaId);
            if ($reserva === null) {
                throw new NoEncontradoException('Reserva no encontrada');
            }

            if ($reserva->estado !== 'activa') {
                throw new ConflictoException('La reserva ya fue completada o cancelada');
            }

            $vehiculo = Vehiculo::query()->lockForUpdate()->find($reserva->vehiculo_id);

            $reserva->fecha_devolucion_real = $fecha;
            $reserva->estado = 'completada';
            $reserva->save();

            if ($vehiculo !== null) {
                $vehiculo->estado = $estadoVehiculo;
                $vehiculo->save();
            }
        });

        return Reserva::with(['cliente', 'vehiculo'])->find($reservaId);
    }



    // Comprueba que el texto sea una fecha real con formato YYYY-MM-DD; la usa registrar()
    private function fechaValida(mixed $fecha): bool
    {
        if (!is_string($fecha)) {
            return false;
        }

        $objeto = DateTime::createFromFormat('Y-m-d', $fecha);

        return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
    }
}
