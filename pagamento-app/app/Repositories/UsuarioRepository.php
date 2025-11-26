<?php

namespace App\Repositories;

use App\Models\Usuario;

class UsuarioRepository
{
    public function buscarPorId(int $id): Usuario
    {
        return Usuario::with('carteira')->findOrFail($id);
    }
}
