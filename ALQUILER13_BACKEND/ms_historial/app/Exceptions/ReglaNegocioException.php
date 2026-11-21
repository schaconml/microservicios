<?php

namespace App\Exceptions;

use RuntimeException;



// Base de los errores esperados del dominio; el código es el estado HTTP que usa el controlador
class ReglaNegocioException extends RuntimeException
{
    public function __construct(string $mensaje, int $codigoHttp)
    {
        parent::__construct($mensaje, $codigoHttp);
    }
}
