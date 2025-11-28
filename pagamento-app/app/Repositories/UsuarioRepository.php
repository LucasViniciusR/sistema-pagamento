<?php

namespace App\Repositories;

use App\Models\Usuario;

class UsuarioRepository
{
    public function buscarPorId(int $id): Usuario
    {
        return Usuario::findOrFail($id);
    }
}
