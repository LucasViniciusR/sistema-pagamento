<?php

namespace App\Services;

use App\Contracts\ServicoDeAutorizacaoInterface;
use Illuminate\Support\Facades\Http;

class ServicoDeAutorizacaoExterno implements ServicoDeAutorizacaoInterface
{
    public function autorizar(): bool
    {
        $resposta = Http::get('https://util.devi.tools/api/v2/authorize');

        return $resposta->successful()
            && ($resposta->json()['data']['authorization'] ?? false) === true;
    }
}
