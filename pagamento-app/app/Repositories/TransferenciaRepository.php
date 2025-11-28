<?php

namespace App\Repositories;

use App\Contracts\TransferenciaRepositoryInterface;
use App\Models\Transferencia;

class TransferenciaRepository implements TransferenciaRepositoryInterface
{
    public function criar(array $dados): Transferencia
    {
        return Transferencia::create($dados);
    }

    public function marcarSucesso(Transferencia $transferencia): void
    {
        $transferencia->status = 'sucesso';
        $transferencia->save();
    }

    public function marcarFalha(Transferencia $transferencia): void
    {
        $transferencia->status = 'falha';
        $transferencia->save();
    }
}
