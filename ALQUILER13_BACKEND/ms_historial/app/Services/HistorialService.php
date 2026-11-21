<?php

namespace App\Services;

use App\Contracts\HistorialServiceInterface;
use App\Exceptions\NoEncontradoException;
use App\Exceptions\SolicitudInvalidaException;
use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Vehiculo;
use DateTime;
use Illuminate\Database\Eloquent\Collection;



// Reglas de consulta del historial (solo lectura, sin transacciones ni cambios de estado)
class HistorialService implements HistorialServiceInterface
{
    // Lista el historial completo con vehículo y cliente, con filtros opcionales por estado y fechas
    public function listar(?string $estado, ?string $desde, ?string $hasta): Collection
    {
        if ($estado !== null && $estado !== '') {
            if (!in_array($estado, ['activa', 'completada', 'cancelada'], true)) {
                throw new SolicitudInvalidaException('El estado de filtro debe ser activa, completada o cancelada');
            }
        }

        $desde = $this->normalizarFecha($desde, 'desde');
        $hasta = $this->normalizarFecha($hasta, 'hasta');

        if ($desde !== null && $hasta !== null && $hasta < $desde) {
            throw new SolicitudInvalidaException('El parámetro hasta no puede ser anterior a desde');
        }

        $consulta = Reserva::with(['cliente', 'vehiculo'])->orderByDesc('id');

        if ($estado !== null && $estado !== '') {
            $consulta->where('estado', $estado);
        }
        if ($desde !== null) {
            $consulta->where('fecha_inicio', '>=', $desde);
        }
        if ($hasta !== null) {
            $consulta->where('fecha_fin', '<=', $hasta);
        }

        return $consulta->get();
    }



    // Historial de un vehículo; verifica que el vehículo exista (404 si no)
    public function porVehiculo(int $id): array
    {
        $vehiculo = Vehiculo::find($id);
        if ($vehiculo === null) {
            throw new NoEncontradoException('Vehículo no encontrado');
        }

        $reservas = Reserva::with(['cliente', 'vehiculo'])
            ->where('vehiculo_id', $id)
            ->orderByDesc('id')
            ->get();

        return [
            'vehiculo' => $vehiculo,
            'total_alquileres' => $reservas->count(),
            'reservas' => $reservas,
        ];
    }



    // Historial de un cliente; verifica que el cliente exista (404 si no)
    public function porCliente(int $id): array
    {
        $cliente = Cliente::find($id);
        if ($cliente === null) {
            throw new NoEncontradoException('Cliente no encontrado');
        }

        $reservas = Reserva::with(['cliente', 'vehiculo'])
            ->where('cliente_id', $id)
            ->orderByDesc('id')
            ->get();

        return [
            'cliente' => $cliente,
            'total_alquileres' => $reservas->count(),
            'reservas' => $reservas,
        ];
    }



    // Reporte básico con conteos por estado y las últimas 5 reservas registradas
    public function resumen(): array
    {
        $ultimas = Reserva::with(['cliente', 'vehiculo'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'total_reservas' => Reserva::query()->count(),
            'activas' => Reserva::query()->where('estado', 'activa')->count(),
            'completadas' => Reserva::query()->where('estado', 'completada')->count(),
            'canceladas' => Reserva::query()->where('estado', 'cancelada')->count(),
            'ultimas_reservas' => $ultimas,
        ];
    }



    // Devuelve la fecha limpia o null si no se envió; valida el formato YYYY-MM-DD
    private function normalizarFecha(?string $fecha, string $nombre): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        if (!$this->fechaValida($fecha)) {
            throw new SolicitudInvalidaException('El parámetro ' . $nombre . ' debe tener el formato YYYY-MM-DD');
        }

        return $fecha;
    }



    // Comprueba que el texto sea una fecha real con formato YYYY-MM-DD
    private function fechaValida(mixed $fecha): bool
    {
        if (!is_string($fecha)) {
            return false;
        }

        $objeto = DateTime::createFromFormat('Y-m-d', $fecha);

        return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
    }
}
