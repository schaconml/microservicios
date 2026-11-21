<?php

namespace App\Services;

use App\Contracts\DisponibilidadServiceInterface;
use App\Exceptions\SolicitudInvalidaException;
use App\Models\Reserva;
use App\Models\Vehiculo;
use DateTime;
use Illuminate\Database\Eloquent\Collection;



// Reglas de consulta de disponibilidad (solo lectura, Regla A en modo defensivo)
class DisponibilidadService implements DisponibilidadServiceInterface
{
    // Vehículos disponibles, con filtro opcional por categoría y exclusión defensiva por fechas
    public function consultar(?string $categoria, ?string $fechaInicio, ?string $fechaFin): Collection
    {
        $categoria = is_string($categoria) ? trim($categoria) : null;
        if ($categoria === '') {
            $categoria = null;
        }

        $tieneInicio = $fechaInicio !== null && $fechaInicio !== '';
        $tieneFin = $fechaFin !== null && $fechaFin !== '';

        if ($tieneInicio xor $tieneFin) {
            throw new SolicitudInvalidaException('Debe enviar fecha_inicio y fecha_fin juntas');
        }

        if ($tieneInicio && !$this->fechaValida($fechaInicio)) {
            throw new SolicitudInvalidaException('La fecha_inicio debe tener el formato YYYY-MM-DD');
        }
        if ($tieneFin && !$this->fechaValida($fechaFin)) {
            throw new SolicitudInvalidaException('La fecha_fin debe tener el formato YYYY-MM-DD');
        }
        if ($tieneInicio && $tieneFin && $fechaFin < $fechaInicio) {
            throw new SolicitudInvalidaException('La fecha_fin no puede ser anterior a la fecha_inicio');
        }

        // Bajo Regla A solo el estado disponible puede alquilarse (usa idx_vehiculos_estado)
        $consulta = Vehiculo::query()->where('estado', 'disponible')->orderByDesc('id');

        if ($categoria !== null) {
            $consulta->where('categoria', $categoria);
        }

        if ($tieneInicio && $tieneFin) {
            // Exclusión defensiva: un disponible con reserva activa solapada no se ofrece
            $ocupados = Reserva::query()
                ->where('estado', 'activa')
                ->where('fecha_inicio', '<=', $fechaFin)
                ->where('fecha_fin', '>=', $fechaInicio)
                ->pluck('vehiculo_id')
                ->unique()
                ->values()
                ->all();

            if (count($ocupados) > 0) {
                $consulta->whereNotIn('id', $ocupados);
            }
        }

        return $consulta->get();
    }



    // Conteo de la flota por estado y disponibles agrupados por categoría
    public function resumen(): array
    {
        $porCategoria = Vehiculo::query()
            ->selectRaw('categoria, COUNT(*) as total')
            ->where('estado', 'disponible')
            ->groupBy('categoria')
            ->get();

        return [
            'total_vehiculos' => Vehiculo::query()->count(),
            'disponibles' => Vehiculo::query()->where('estado', 'disponible')->count(),
            'alquilados' => Vehiculo::query()->where('estado', 'alquilado')->count(),
            'mantenimiento' => Vehiculo::query()->where('estado', 'mantenimiento')->count(),
            'disponibles_por_categoria' => $porCategoria,
        ];
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
