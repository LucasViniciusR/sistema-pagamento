<?php

namespace App\Exceptions;

use Exception;

class TransferenciaMesmoUsuarioException extends Exception
{
    public function __construct()
    {
        parent::__construct('Não é possível transferir para si mesmo', 422);
    }
}
