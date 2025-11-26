<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Transferencia extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'transferencias';

    protected $fillable = [
        'pagador_id',
        'recebedor_id',
        'email_recebedor',
        'valor',
        'status',
        'meta',
    ];
}
