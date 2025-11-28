<?php

namespace App\Exceptions;

use Exception;

class SaldoInsuficienteException extends Exception
{
    public function __construct()
    {
        parent::__construct('Saldo insuficiente para realizar a transferência', 422);
    }
}
