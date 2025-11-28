<?php

namespace App\Services;

use App\Contracts\NotificadorDeTransferenciaInterface;
use App\Contracts\ServicoDeAutorizacaoInterface;
use App\Contracts\TransferenciaRepositoryInterface;
use App\DTOs\TransferenciaDTO;
use App\Exceptions\SaldoInsuficienteException;
use App\Exceptions\TransferenciaMesmoUsuarioException;
use App\Exceptions\TransferenciaNaoAutorizadaException;
use App\Exceptions\TransferenciaNaoPermitidaException;
use App\Exceptions\UsuarioNaoEncontradoException;
use App\Exceptions\ValorInvalidoException;
use App\Repositories\CarteiraRepository;
use App\Repositories\UsuarioRepository;
use Illuminate\Support\Facades\DB;

class TransferenciaService
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private CarteiraRepository $carteiraRepository,
        private TransferenciaRepositoryInterface $transferenciaRepository,
        private ServicoDeAutorizacaoInterface $autorizador,
        private NotificadorDeTransferenciaInterface $notificadorDeTransferencia,
    ) {}

    public function transferir(TransferenciaDTO $transferenciaDTO)
    {
        if (floor($transferenciaDTO->valor * 100) != $transferenciaDTO->valor * 100) {
            throw new ValorInvalidoException;
        }

        if ($transferenciaDTO->valor < 0.01) {
            throw new ValorInvalidoException;
        }

        if ($transferenciaDTO->pagadorId === $transferenciaDTO->recebedorId) {
            throw new TransferenciaMesmoUsuarioException;
        }

        $pagador = $this->usuarioRepository->buscarPorId($transferenciaDTO->pagadorId);
        $recebedor = $this->usuarioRepository->buscarPorId($transferenciaDTO->recebedorId);

        if (! $pagador) {
            throw new UsuarioNaoEncontradoException('Pagador não encontrado');
        }

        if (! $recebedor) {
            throw new UsuarioNaoEncontradoException('Recebedor não encontrado');
        }

        if ($pagador->tipo === 'lojista') {
            throw new TransferenciaNaoPermitidaException;
        }

        return $this->executarTransferencia($transferenciaDTO, $pagador, $recebedor);
    }

    private function executarTransferencia(TransferenciaDTO $transferenciaDTO, $pagador, $recebedor)
    {
        $transferencia = null;
        try {
            return DB::transaction(function () use ($transferenciaDTO, $pagador, $recebedor, &$transferencia) {
                $carteiraPagador = $this->carteiraRepository->obterPorUsuarioComLock($pagador->id);
                $carteiraRecebedor = $this->carteiraRepository->obterPorUsuarioComLock($recebedor->id);

                if ($carteiraPagador->saldo < $transferenciaDTO->valor) {
                    throw new SaldoInsuficienteException;
                }

                if (! $this->autorizador->autorizar()) {
                    throw new TransferenciaNaoAutorizadaException;
                }

                $transferencia = $this->transferenciaRepository->criar([
                    'pagador_id' => $pagador->id,
                    'recebedor_id' => $recebedor->id,
                    'email_recebedor' => $recebedor->email,
                    'valor' => $transferenciaDTO->valor,
                    'status' => 'pendente',
                ]);

                $carteiraPagador->saldo -= $transferenciaDTO->valor;
                $carteiraRecebedor->saldo += $transferenciaDTO->valor;

                $this->carteiraRepository->salvar($carteiraPagador);
                $this->carteiraRepository->salvar($carteiraRecebedor);

                $this->transferenciaRepository->marcarSucesso($transferencia);

                return $transferencia;
            });
        } catch (\Exception $e) {
            if ($transferencia) {
                $this->transferenciaRepository->marcarFalha($transferencia);
            }
            \Log::error('Transferência falhou', [
                'erro' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function transferirENotificar(TransferenciaDTO $transferenciaDTO)
    {
        $transferencia = $this->transferir($transferenciaDTO);

        try {
            $this->notificadorDeTransferencia->notificar(
                $transferencia->_id,
                $transferenciaDTO->valor,
                $transferencia->email_recebedor
            );
        } catch (\Exception $e) {
            \Log::error('Falha ao notificar recebimento: '.$e->getMessage(), [
                'transferencia_id' => $transferencia->_id,
            ]);
            $this->transferenciaRepository->marcarFalha($transferencia);
            $transferencia->meta = json_encode(['erro' => $e->getMessage()]);
            $transferencia->save();
        }

        return $transferencia;
    }
}
