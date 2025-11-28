<?php

namespace App\Services\Notificacao;

use App\Contracts\ServicoDeNotificacaoInterface;
use Illuminate\Support\Facades\Http;

class ServicoDeNotificacaoEmail implements ServicoDeNotificacaoInterface
{
    public function enviar(string $destinatario, string $conteudo): void
    {
        Http::post('https://util.devi.tools/api/v1/notify', [
            'email' => $destinatario,
            'mensagem' => $conteudo,
        ]);
    }
}
