<?php

namespace Database\Seeders;

use App\Models\Carteira;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $pagador = Usuario::create([
            'nome_completo' => 'João Pagador',
            'cpf' => '11111111111',
            'email' => 'pagador@example.com',
            'senha' => '123456',
        ]);

        Carteira::create([
            'usuario_id' => $pagador->id,
            'saldo' => 1000.00,
        ]);

        $recebedor = Usuario::create([
            'nome_completo' => 'Maria Recebedora',
            'cpf' => '22222222222',
            'email' => 'recebedor@example.com',
            'senha' => '123456',
        ]);

        Carteira::create([
            'usuario_id' => $recebedor->id,
            'saldo' => 100.00,
        ]);
    }
}
