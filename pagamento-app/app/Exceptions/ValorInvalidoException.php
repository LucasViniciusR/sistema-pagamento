<?php

namespace App\Exceptions;

use Exception;

class ValorInvalidoException extends Exception
{
    public function __construct()
    {
        parent::__construct('Valor de transferência invalido. O valor deve ter no máximo 2 casas decimais e ser maior que 0.01', 422);
    }
}
