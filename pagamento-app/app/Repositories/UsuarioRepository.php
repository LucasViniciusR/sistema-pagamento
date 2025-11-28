<?php

namespace App\Repositories;

use App\Models\Usuario;

class UsuarioRepository
{
    public function buscarPorId(int $idUsuario): ?Usuario
    {
        return Usuario::find($idUsuario);
    }
}
