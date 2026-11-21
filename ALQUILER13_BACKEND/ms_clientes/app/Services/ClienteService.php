<?php

namespace App\Services;

use App\Contracts\ClienteServiceInterface;
use App\Exceptions\ConflictoException;
use App\Exceptions\NoEncontradoException;
use App\Exceptions\SolicitudInvalidaException;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;



// Reglas de negocio de clientes
class ClienteService implements ClienteServiceInterface
{
    // Lista los clientes del más nuevo al más antiguo
    public function listar(): Collection
    {
        return Cliente::query()->orderByDesc('id')->get();
    }



    // Busca un cliente por id; lo reutilizan otros métodos de esta clase
    public function obtener(int $id): Cliente
    {
        $cliente = Cliente::find($id);

        if ($cliente === null) {
            throw new NoEncontradoException('Cliente no encontrado');
        }

        return $cliente;
    }



    // Crea un cliente; los datos limpios salen de validarDatos() y la licencia se revisa en licenciaDuplicada()
    public function crear(array $datos): Cliente
    {
        $limpios = $this->validarDatos($datos);

        if ($this->licenciaDuplicada($limpios['numero_licencia'], null)) {
            throw new ConflictoException('Ya existe un cliente con ese número de licencia');
        }

        $creado = Cliente::create($limpios);

        return Cliente::find($creado->id);
    }



    // Actualiza un cliente; la licencia debe ser única sin contar al propio cliente
    public function actualizar(int $id, array $datos): Cliente
    {
        $limpios = $this->validarDatos($datos);
        $cliente = $this->obtener($id);

        if ($this->licenciaDuplicada($limpios['numero_licencia'], $id)) {
            throw new ConflictoException('Ya existe otro cliente con ese número de licencia');
        }

        $cliente->nombre = $limpios['nombre'];
        $cliente->telefono = $limpios['telefono'];
        $cliente->correo = $limpios['correo'];
        $cliente->numero_licencia = $limpios['numero_licencia'];
        $cliente->save();

        return Cliente::find($id);
    }



    // Elimina un cliente; si tiene reservas la base de datos lo impide (ON DELETE RESTRICT, SQLSTATE 23000)
    public function eliminar(int $id): void
    {
        $cliente = $this->obtener($id);

        try {
            $cliente->delete();
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new ConflictoException('No se puede eliminar el cliente porque tiene historial de reservas');
            }
            throw $e;
        }
    }



    // Valida y limpia los campos del cliente; lo usan crear() y actualizar()
    private function validarDatos(array $datos): array
    {
        $nombre = $this->aTexto($datos['nombre'] ?? null);
        if ($nombre === '') {
            throw new SolicitudInvalidaException('El nombre es obligatorio');
        }
        if (mb_strlen($nombre) > 150) {
            throw new SolicitudInvalidaException('El nombre no puede superar los 150 caracteres');
        }

        $licencia = $this->aTexto($datos['numero_licencia'] ?? null);
        if ($licencia === '') {
            throw new SolicitudInvalidaException('El número de licencia es obligatorio');
        }
        if (mb_strlen($licencia) > 50) {
            throw new SolicitudInvalidaException('El número de licencia no puede superar los 50 caracteres');
        }

        $telefono = $this->aTexto($datos['telefono'] ?? null);
        if ($telefono === '') {
            $telefono = null;
        }
        if ($telefono !== null && mb_strlen($telefono) > 50) {
            throw new SolicitudInvalidaException('El teléfono no puede superar los 50 caracteres');
        }

        $correo = $this->aTexto($datos['correo'] ?? null);
        if ($correo === '') {
            $correo = null;
        }
        if ($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new SolicitudInvalidaException('El correo no tiene un formato válido');
        }
        if ($correo !== null && mb_strlen($correo) > 100) {
            throw new SolicitudInvalidaException('El correo no puede superar los 100 caracteres');
        }

        return [
            'nombre' => $nombre,
            'telefono' => $telefono,
            'correo' => $correo,
            'numero_licencia' => $licencia,
        ];
    }



    // Convierte texto o número a cadena sin espacios en los extremos; lo usa validarDatos()
    private function aTexto(mixed $valor): string
    {
        if (is_int($valor) || is_float($valor)) {
            $valor = (string) $valor;
        }

        return is_string($valor) ? trim($valor) : '';
    }



    // Indica si otro cliente ya tiene la licencia; $ignorarId excluye al cliente que se está editando
    private function licenciaDuplicada(string $licencia, ?int $ignorarId): bool
    {
        $consulta = Cliente::query()->where('numero_licencia', $licencia);

        if ($ignorarId !== null) {
            $consulta->where('id', '<>', $ignorarId);
        }

        return $consulta->exists();
    }
}
