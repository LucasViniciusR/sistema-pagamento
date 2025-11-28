<?php

namespace App\Contracts;

interface NotificadorDeTransferenciaInterface
{
    public function notificar(string $transferenciaId, float $valor, string $email): void;
}
