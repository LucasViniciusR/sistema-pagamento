<?php

namespace App\Contracts;

interface ServicoDeAutorizacaoInterface
{
    public function autorizar(): bool;
}
