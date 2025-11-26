<?php

namespace App\Http\Controllers;

use App\Services\TransferenciaService;
use Illuminate\Http\Request;

class TransferenciaController extends Controller
{
    public function __construct(private TransferenciaService $service) {}

    public function transferir(Request $request)
    {
        $dados = $request->validate([
            'value' => 'required|numeric|min:0.01',
            'payer' => 'required|integer|exists:usuarios,id',
            'payee' => 'required|integer|exists:usuarios,id|different:payer',
        ]);

        $transferencia = $this->service->transferirENotificar(
            (float) $dados['value'],
            (int) $dados['payer'],
            (int) $dados['payee']
        );

        return response()->json([
            'sucesso' => true,
            'transferencia_id' => $transferencia->id,
        ], 200);
    }
}
