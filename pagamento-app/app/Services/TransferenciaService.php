<?php

namespace App\Services;

use App\Contracts\NotificadorDeTransferencia;
use App\Repositories\CarteiraRepository;
use App\Repositories\TransferenciaRepository;
use App\Repositories\UsuarioRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferenciaService
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private CarteiraRepository $carteiraRepository,
        private TransferenciaRepository $transferenciaRepository,
        private ServicoAutorizacaoExterno $autorizador,
        private NotificadorDeTransferencia $notificadorDeTransferencia,
    ) {}

    public function transferir(float $valor, int $pagadorId, int $recebedorId)
    {
        if ($pagadorId === $recebedorId) {
            throw new SameUserTransferException;
        }

        $pagador = $this->usuarioRepository->buscarPorId($pagadorId);
        $recebedor = $this->usuarioRepository->buscarPorId($recebedorId);

        if (! $pagador) {
            throw new UserNotFoundException('Pagador não encontrado');
        }

        if (! $recebedor) {
            throw new UserNotFoundException('Recebedor não encontrado');
        }

        if ($pagador->tipo === 'lojista') {
            throw new Exception('Lojistas não podem enviar dinheiro.');
        }

        $transferencia = null;

        try {
            return DB::transaction(function () use ($valor, $pagador, $recebedor) {
                $carteiraPagador = $this->carteiraRepository->obterPorUsuarioComLock($pagador->id);
                $carteiraRecebedor = $this->carteiraRepository->obterPorUsuarioComLock($recebedor->id);

                if ($carteiraPagador->saldo < $valor) {
                    throw new InsufficientBalanceException;
                }

                if (! $this->autorizador->autorizar()) {
                    throw new TransferNotAuthorizedException;
                }

                $transferencia = $this->transferenciaRepository->criar([
                    'pagador_id' => $pagador->id,
                    'recebedor_id' => $recebedor->id,
                    'email_recebedor' => $recebedor->email,
                    'valor' => $valor,
                    'status' => 'pendente',
                ]);

                $carteiraPagador->saldo -= $valor;
                $carteiraRecebedor->saldo += $valor;

                $this->carteiraRepository->salvar($carteiraPagador);
                $this->carteiraRepository->salvar($carteiraRecebedor);

                $this->transferenciaRepository->marcarSucesso($transferencia);

                return $transferencia;
            });
        } catch (\Exception $e) {
            if ($transferencia) {
                $transferencia->marcarFalha($e->getMessage());
            }
            Log::error('Transferência falhou', [
                'erro' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function transferirENotificar(float $valor, int $pagadorId, int $recebedorId)
    {
        $transferencia = $this->transferir($valor, $pagadorId, $recebedorId);

        try {
            $this->notificadorDeTransferencia->notificar(
                $transferencia->id,
                $valor,
                $transferencia->email_recebedor
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao notificar recebimento: '.$e->getMessage(), [
                'transferencia_id' => $transferencia->id,
            ]);

            $transferencia->meta = json_encode(['notificacao' => 'falha']);
            $transferencia->save();
        }

        return $transferencia;
    }
}
