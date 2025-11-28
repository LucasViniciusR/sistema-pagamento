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

class TransferenciaNaoPermitidaException extends Exception
{
    public function __construct()
    {
        parent::__construct('Lojistas não podem realizar transferências', 403);
    }
}

class TransferenciaNaoAutorizadaException extends Exception
{
    public function __construct()
    {
        parent::__construct('Transferência não autorizada pelo serviço externo', 403);
    }
}

class UsuarioNaoEncontradoException extends Exception
{
    public function __construct(string $message = 'Usuário não encontrado')
    {
        parent::__construct($message, 404);
    }
}

class TransferenciaMesmoUsuarioException extends Exception
{
    public function __construct()
    {
        parent::__construct('Não é possível transferir para si mesmo', 422);
    }
}

class ValorInvalidoException extends Exception
{
    public function __construct()
    {
        parent::__construct('O valor da transferência deve ser pelo menos 0.01', 422);
    }
}