<?php

namespace App\Exceptions;



// Choque con el estado actual de los datos (409)
class ConflictoException extends ReglaNegocioException
{
    public function __construct(string $mensaje)
    {
        parent::__construct($mensaje, 409);
    }
}
