<?php

namespace App\Exceptions;



// Recurso inexistente (404)
class NoEncontradoException extends ReglaNegocioException
{
    public function __construct(string $mensaje)
    {
        parent::__construct($mensaje, 404);
    }
}
