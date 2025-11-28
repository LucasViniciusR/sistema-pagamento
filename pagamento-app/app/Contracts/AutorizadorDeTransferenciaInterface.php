<?php

namespace App\Contracts;

interface AutorizadorDeTransferenciaInterface
{
    public function autorizar(): bool;
}