<?php

namespace App\Exceptions;

use Exception;

class TransferenciaNaoPermitidaException extends Exception
{
    public function __construct()
    {
        parent::__construct('Lojistas não podem realizar transferências', 403);
    }
}
