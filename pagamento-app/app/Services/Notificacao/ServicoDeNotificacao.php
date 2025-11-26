<?php

namespace App\Services\Notificacao;

use Illuminate\Support\Facades\Http;

class ServicoDeNotificacao
{
    public function enviar(string $email, string $mensagem): void
    {
        Http::post('https://util.devi.tools/api/v1/notify', [
            'email' => $email,
            'mensagem' => $mensagem,
        ]);
    }
}
