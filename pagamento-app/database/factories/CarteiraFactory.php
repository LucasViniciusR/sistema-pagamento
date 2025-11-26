<?php

namespace Database\Factories;

use App\Models\Carteira;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarteiraFactory extends Factory
{
    protected $model = Carteira::class;

    public function definition()
    {
        return [
            'usuario_id' => \App\Models\Usuario::factory(),
            'saldo' => 100.00,
        ];
    }
}
