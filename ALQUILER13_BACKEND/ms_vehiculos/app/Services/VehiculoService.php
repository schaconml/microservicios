<?php

namespace App\Services;

use App\Contracts\VehiculoServiceInterface;
use App\Exceptions\ConflictoException;
use App\Exceptions\NoEncontradoException;
use App\Exceptions\SolicitudInvalidaException;
use App\Models\Vehiculo;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;



// Reglas de negocio de vehículos
class VehiculoService implements VehiculoServiceInterface
{
    // Lista los vehículos del más nuevo al más antiguo, con filtro opcional por estado
    public function listar(?string $estado): Collection
    {
        $consulta = Vehiculo::query()->orderByDesc('id');

        if ($estado !== null && $estado !== '') {
            if (!in_array($estado, ['disponible', 'alquilado', 'mantenimiento'], true)) {
                throw new SolicitudInvalidaException('El estado de filtro debe ser disponible, alquilado o mantenimiento');
            }
            $consulta->where('estado', $estado);
        }

        return $consulta->get();
    }



    // Busca un vehículo por id; lo reutilizan otros métodos de esta clase
    public function obtener(int $id): Vehiculo
    {
        $vehiculo = Vehiculo::find($id);

        if ($vehiculo === null) {
            throw new NoEncontradoException('Vehículo no encontrado');
        }

        return $vehiculo;
    }



    // Crea un vehículo; los datos limpios salen de validarDatos()
    public function crear(array $datos): Vehiculo
    {
        $limpios = $this->validarDatos($datos);

        $estado = $datos['estado'] ?? 'disponible';
        if ($estado === null || $estado === '') {
            $estado = 'disponible';
        }
        if (!in_array($estado, ['disponible', 'mantenimiento'], true)) {
            throw new SolicitudInvalidaException('Al crear, el estado solo puede ser disponible o mantenimiento');
        }

        $creado = Vehiculo::create([
            'marca' => $limpios['marca'],
            'modelo' => $limpios['modelo'],
            'anio' => $limpios['anio'],
            'categoria' => $limpios['categoria'],
            'estado' => $estado,
        ]);

        return Vehiculo::find($creado->id);
    }



    // Actualiza los datos básicos; si llega un estado distinto, aplica la regla del estado alquilado
    public function actualizar(int $id, array $datos): Vehiculo
    {
        $limpios = $this->validarDatos($datos);

        Capsule::transaction(function () use ($id, $datos, $limpios) {
            $vehiculo = Vehiculo::query()->lockForUpdate()->find($id);

            if ($vehiculo === null) {
                throw new NoEncontradoException('Vehículo no encontrado');
            }

            if (isset($datos['estado']) && $datos['estado'] !== $vehiculo->estado) {
                if ($vehiculo->estado === 'alquilado') {
                    throw new ConflictoException('No se puede cambiar manualmente el estado de un vehículo alquilado; registre la devolución o cancele la reserva');
                }
                if (!in_array($datos['estado'], ['disponible', 'mantenimiento'], true)) {
                    throw new SolicitudInvalidaException('El estado solo puede ser disponible o mantenimiento');
                }
                $vehiculo->estado = $datos['estado'];
            }

            $vehiculo->marca = $limpios['marca'];
            $vehiculo->modelo = $limpios['modelo'];
            $vehiculo->anio = $limpios['anio'];
            $vehiculo->categoria = $limpios['categoria'];
            $vehiculo->save();
        });

        return Vehiculo::find($id);
    }



    // Cambia solo el estado; un vehículo alquilado se libera con la devolución o la cancelación (ms_devoluciones y ms_reservas)
    public function cambiarEstado(int $id, array $datos): Vehiculo
    {
        $estado = $datos['estado'] ?? null;

        if (!is_string($estado) || !in_array($estado, ['disponible', 'mantenimiento'], true)) {
            throw new SolicitudInvalidaException('El estado debe ser disponible o mantenimiento');
        }

        Capsule::transaction(function () use ($id, $estado) {
            $vehiculo = Vehiculo::query()->lockForUpdate()->find($id);

            if ($vehiculo === null) {
                throw new NoEncontradoException('Vehículo no encontrado');
            }

            if ($vehiculo->estado === 'alquilado') {
                throw new ConflictoException('No se puede cambiar manualmente el estado de un vehículo alquilado; registre la devolución o cancele la reserva');
            }

            $vehiculo->estado = $estado;
            $vehiculo->save();
        });

        return Vehiculo::find($id);
    }



    // Elimina un vehículo; si tiene reservas la base de datos lo impide (ON DELETE RESTRICT, SQLSTATE 23000)
    public function eliminar(int $id): void
    {
        $vehiculo = $this->obtener($id);

        try {
            $vehiculo->delete();
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new ConflictoException('No se puede eliminar el vehículo porque tiene historial de reservas');
            }
            throw $e;
        }
    }



    // Valida marca, modelo, año y categoría; lo usan crear() y actualizar()
    private function validarDatos(array $datos): array
    {
        $marca = is_string($datos['marca'] ?? null) ? trim($datos['marca']) : '';
        if ($marca === '') {
            throw new SolicitudInvalidaException('La marca es obligatoria');
        }
        if (mb_strlen($marca) > 100) {
            throw new SolicitudInvalidaException('La marca no puede superar los 100 caracteres');
        }

        $modelo = is_string($datos['modelo'] ?? null) ? trim($datos['modelo']) : '';
        if ($modelo === '') {
            throw new SolicitudInvalidaException('El modelo es obligatorio');
        }
        if (mb_strlen($modelo) > 100) {
            throw new SolicitudInvalidaException('El modelo no puede superar los 100 caracteres');
        }

        $anio = filter_var($datos['anio'] ?? null, FILTER_VALIDATE_INT);
        if ($anio === false || $anio < 1901 || $anio > 2155) {
            throw new SolicitudInvalidaException('El año debe ser un entero de 4 dígitos entre 1901 y 2155');
        }

        $categoria = is_string($datos['categoria'] ?? null) ? trim($datos['categoria']) : '';
        if ($categoria === '') {
            $categoria = null;
        }
        if ($categoria !== null && mb_strlen($categoria) > 50) {
            throw new SolicitudInvalidaException('La categoría no puede superar los 50 caracteres');
        }

        return [
            'marca' => $marca,
            'modelo' => $modelo,
            'anio' => $anio,
            'categoria' => $categoria,
        ];
    }
}
