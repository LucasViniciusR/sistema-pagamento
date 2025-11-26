<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ServicoAutorizacaoExterno
{
    public function autorizar(): bool
    {
        $resposta = Http::get('https://util.devi.tools/api/v2/authorize');

        return true;
    }
}
