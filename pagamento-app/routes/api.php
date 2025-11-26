<?php

use App\Http\Controllers\TransferenciaController;
use Illuminate\Support\Facades\Route;

Route::post('/transferencias', [TransferenciaController::class, 'transferir']);
