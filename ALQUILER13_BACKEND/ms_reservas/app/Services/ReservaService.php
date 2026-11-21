<?php

namespace App\Services;

use App\Contracts\ReservaServiceInterface;
use App\Exceptions\ConflictoException;
use App\Exceptions\NoEncontradoException;
use App\Exceptions\SolicitudInvalidaException;
use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Vehiculo;
use DateTime;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;



// Reglas de negocio de reservas (Regla A: máquina de estados del vehículo)
class ReservaService implements ReservaServiceInterface
{
    // Lista las reservas con cliente y vehículo, con filtro opcional por estado
    public function listar(?string $estado): Collection
    {
        $consulta = Reserva::with(['cliente', 'vehiculo'])->orderByDesc('id');

        if ($estado !== null && $estado !== '') {
            if (!in_array($estado, ['activa', 'completada', 'cancelada'], true)) {
                throw new SolicitudInvalidaException('El estado de filtro debe ser activa, completada o cancelada');
            }
            $consulta->where('estado', $estado);
        }

        return $consulta->get();
    }



    // Busca una reserva con su cliente y vehículo; lo reutilizan otros métodos de esta clase
    public function obtener(int $id): Reserva
    {
        $reserva = Reserva::with(['cliente', 'vehiculo'])->find($id);

        if ($reserva === null) {
            throw new NoEncontradoException('Reserva no encontrada');
        }

        return $reserva;
    }



    // Crea una reserva activa y marca el vehículo como alquilado, todo dentro de una transacción
    public function crear(array $datos): Reserva
    {
        $clienteId = filter_var($datos['cliente_id'] ?? null, FILTER_VALIDATE_INT);
        if ($clienteId === false || $clienteId < 1) {
            throw new SolicitudInvalidaException('El cliente_id es obligatorio y debe ser un entero positivo');
        }

        $vehiculoId = filter_var($datos['vehiculo_id'] ?? null, FILTER_VALIDATE_INT);
        if ($vehiculoId === false || $vehiculoId < 1) {
            throw new SolicitudInvalidaException('El vehiculo_id es obligatorio y debe ser un entero positivo');
        }

        $inicio = $datos['fecha_inicio'] ?? null;
        if (!$this->fechaValida($inicio)) {
            throw new SolicitudInvalidaException('La fecha_inicio debe tener el formato YYYY-MM-DD');
        }

        $fin = $datos['fecha_fin'] ?? null;
        if (!$this->fechaValida($fin)) {
            throw new SolicitudInvalidaException('La fecha_fin debe tener el formato YYYY-MM-DD');
        }

        if ($fin < $inicio) {
            throw new SolicitudInvalidaException('La fecha_fin no puede ser anterior a la fecha_inicio');
        }

        $reservaId = Capsule::transaction(function () use ($clienteId, $vehiculoId, $inicio, $fin) {
            $cliente = Cliente::find($clienteId);
            if ($cliente === null) {
                throw new NoEncontradoException('Cliente no encontrado');
            }

            // Se bloquea la fila del vehículo para que dos reservas simultáneas no lo tomen a la vez
            $vehiculo = Vehiculo::query()->lockForUpdate()->find($vehiculoId);
            if ($vehiculo === null) {
                throw new NoEncontradoException('Vehículo no encontrado');
            }

            if ($vehiculo->estado !== 'disponible') {
                throw new ConflictoException('El vehículo no está disponible para alquiler');
            }

            // Revisión defensiva de solapamiento inclusivo con otras reservas activas
            $solapada = Reserva::query()
                ->where('vehiculo_id', $vehiculoId)
                ->where('estado', 'activa')
                ->where('fecha_inicio', '<=', $fin)
                ->where('fecha_fin', '>=', $inicio)
                ->exists();
            if ($solapada) {
                throw new ConflictoException('El vehículo ya tiene una reserva activa en esas fechas');
            }

            $reserva = Reserva::create([
                'cliente_id' => $clienteId,
                'vehiculo_id' => $vehiculoId,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'fecha_devolucion_real' => null,
                'estado' => 'activa',
            ]);

            $vehiculo->estado = 'alquilado';
            $vehiculo->save();

            return $reserva->id;
        });

        return $this->obtener($reservaId);
    }



    // Cancela una reserva activa y deja el vehículo disponible, dentro de una transacción
    public function cancelar(int $id): Reserva
    {
        Capsule::transaction(function () use ($id) {
            $reserva = Reserva::query()->lockForUpdate()->find($id);
            if ($reserva === null) {
                throw new NoEncontradoException('Reserva no encontrada');
            }

            if ($reserva->estado !== 'activa') {
                throw new ConflictoException('Solo se pueden cancelar reservas activas');
            }

            $vehiculo = Vehiculo::query()->lockForUpdate()->find($reserva->vehiculo_id);

            $reserva->estado = 'cancelada';
            $reserva->save();

            if ($vehiculo !== null) {
                $vehiculo->estado = 'disponible';
                $vehiculo->save();
            }
        });

        return $this->obtener($id);
    }



    // Comprueba que el texto sea una fecha real con formato YYYY-MM-DD; la usa crear()
    private function fechaValida(mixed $fecha): bool
    {
        if (!is_string($fecha)) {
            return false;
        }

        $objeto = DateTime::createFromFormat('Y-m-d', $fecha);

        return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
    }
}
