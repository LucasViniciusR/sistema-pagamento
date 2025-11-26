<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    protected $table = 'transferencias';

    protected $fillable = [
        'pagador_id',
        'recebedor_id',
        'valor',
        'status',
        'meta',
    ];
}
