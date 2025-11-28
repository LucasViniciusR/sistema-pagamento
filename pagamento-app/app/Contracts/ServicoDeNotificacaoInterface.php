<?php

namespace App\Contracts;

interface ServicoDeNotificacaoInterface
{
    public function enviar(string $destinatario, string $conteudo): void;
}
