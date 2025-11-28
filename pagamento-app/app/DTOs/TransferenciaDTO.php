<?php

namespace App\DTOs;

readonly class TransferenciaDTO
{
    public function __construct(
        public float $valor,
        public int $pagadorId,
        public int $recebedorId,
    ) {}
}
