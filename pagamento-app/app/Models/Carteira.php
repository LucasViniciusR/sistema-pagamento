<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carteira extends Model
{
    use HasFactory;

    protected $fillable = ['usuario_id', 'saldo'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
