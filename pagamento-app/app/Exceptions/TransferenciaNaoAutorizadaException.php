<?php

namespace App\Exceptions;

use Exception;

class TransferenciaNaoAutorizadaException extends Exception
{
    public function __construct()
    {
        parent::__construct('Transferência não autorizada pelo serviço externo', 403);
    }
}
