<?php

namespace App\Http\Controllers;

use App\DTOs\TransferenciaDTO;
use App\Http\Requests\TransferenciaRequest;
use App\Services\TransferenciaService;
use App\Exceptions\SaldoInsuficienteException;
use App\Exceptions\TransferenciaMesmoUsuarioException;
use App\Exceptions\TransferenciaNaoAutorizadaException;
use App\Exceptions\TransferenciaNaoPermitidaException;
use App\Exceptions\UsuarioNaoEncontradoException;

class TransferenciaController extends Controller
{
    public function __construct(private TransferenciaService $service) {}

    public function transferir(TransferenciaRequest $request)
    {
        try {
            $dados = $request->validated();

            $transferenciaDto = new TransferenciaDTO(
                valor: (float) $dados['value'],
                pagadorId: (int) $dados['payer'],
                recebedorId: (int) $dados['payee'],
            );

            $transferencia = $this->service->transferirENotificar($transferenciaDto);

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
