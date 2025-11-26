<?php

namespace App\Repositories;

use App\Models\Carteira;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CarteiraRepository
{
    public function obterPorUsuario(int $usuarioId): Carteira
    {
        return Carteira::where('usuario_id', $usuarioId)->firstOrFail();
    }

    public function obterPorUsuarioComLock(int $usuarioId): Carteira
    {
        $carteira = Carteira::where('usuario_id', $usuarioId)->lockForUpdate()->first();

        if (! $carteira) {
            throw new ModelNotFoundException('Carteira não encontrada');
        }

        return $carteira;
    }

    public function salvar(Carteira $carteira): void
    {
        $carteira->save();
    }
}
