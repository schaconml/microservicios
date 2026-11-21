<?php

namespace App\Exceptions;



// Datos no válidos (400)
class SolicitudInvalidaException extends ReglaNegocioException
{
    public function __construct(string $mensaje)
    {
        parent::__construct($mensaje, 400);
    }
}
