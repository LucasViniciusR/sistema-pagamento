<?php

namespace App\Exceptions;

use Exception;

class UsuarioNaoEncontradoException extends Exception
{
    public function __construct(string $message = 'Usuário não encontrado')
    {
        parent::__construct($message, 404);
    }
}
