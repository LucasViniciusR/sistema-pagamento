<?php

namespace App\Contracts;

interface NotificadorDeTransferencia
{
    public function notificar(string $transferenciaId, float $valor, string $email): void;
}
