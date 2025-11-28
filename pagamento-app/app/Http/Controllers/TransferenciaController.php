<?php

namespace App\Http\Controllers;

use App\Services\TransferenciaService;
use App\Http\Requests\TransferenciaRequest;

class TransferenciaController extends Controller
{
    public function __construct(private TransferenciaService $service) {}

    public function transferir(TransferenciaRequest $request)
    {
        try {
            $dados = $request->validated();

            $transferencia = $this->service->transferirENotificar(
                (float) $dados['value'],
                (int) $dados['payer'],
                (int) $dados['payee']
            );

            return response()->json([
                'sucesso' => true,
                'transferencia_id' => $transferencia->_id,
            ], 200);
        } catch (TransferenciaNaoPermitidaException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);

        } catch (SaldoInsuficienteException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (TransferenciaNaoAutorizadaException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);

        } catch (UsuarioNaoEncontradoException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);

        } catch (TransferenciaMesmoUsuarioException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Erro ao processar transferência: '.$e->getMessage());
            return response()->json([
                'message' => 'Erro ao processar transferência',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
