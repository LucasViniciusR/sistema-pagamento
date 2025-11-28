<?php

namespace App\Exceptions;

use Exception;

class ValorInvalidoException extends Exception
{
    public function __construct()
    {
        parent::__construct('O valor da transferência deve ser pelo menos 0.01', 422);
    }
}
