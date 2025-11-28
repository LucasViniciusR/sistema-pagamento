<?php

namespace App\Contracts;

use App\Models\Transferencia;

interface TransferenciaRepositoryInterface
{
    public function criar(array $dados): Transferencia;

    public function marcarSucesso(Transferencia $transferencia): void;

    public function marcarFalha(Transferencia $transferencia): void;

    public function atualizarMeta(string $transferenciaId, array $meta): void;
}
