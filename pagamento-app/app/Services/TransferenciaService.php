<?php

namespace App\Services;

use App\Contracts\AutorizadorDeTransferenciaInterface;
use App\Contracts\NotificadorDeTransferenciaInterface;
use App\Contracts\TransferenciaRepositoryInterface;
use App\DTOs\TransferenciaDTO;
use App\Repositories\CarteiraRepository;
use App\Repositories\UsuarioRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferenciaService
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private CarteiraRepository $carteiraRepository,
        private TransferenciaRepositoryInterface $transferenciaRepository,
        private AutorizadorDeTransferenciaInterface $autorizador,
        private NotificadorDeTransferenciaInterface $notificadorDeTransferencia,
    ) {}

    public function transferir(float $valor, int $pagadorId, int $recebedorId)
    {
        $dto = new TransferenciaDTO(
            valor: $valor,
            pagadorId: $pagadorId,
            recebedorId: $recebedorId,
        );
        if ($dto->valor < 0.01) {
            throw new ValorInvalidoException();
        }

        if ($dto->pagadorId === $dto->recebedorId) {
            throw new TransferenciaMesmoUsuarioException();
        }

        $pagador = $this->usuarioRepository->buscarPorId($dto->pagadorId);
        $recebedor = $this->usuarioRepository->buscarPorId($dto->recebedorId);

        if (! $pagador) {
            throw new UsuarioNaoEncontradoException('Pagador não encontrado');
        }

        if (! $recebedor) {
            throw new UsuarioNaoEncontradoException('Recebedor não encontrado');
        }

        if ($pagador->tipo === 'lojista') {
            throw new TransferenciaNaoPermitidaException();
        }

        return $this->executarTransferencia($dto, $pagador, $recebedor);
    }

    private function executarTransferencia(TransferenciaDTO $dto, $pagador, $recebedor)
    {
        $transferencia = null;
        try {
            return DB::transaction(function () use ($dto, $pagador, $recebedor) {
                $carteiraPagador = $this->carteiraRepository->obterPorUsuarioComLock($pagador->id);
                $carteiraRecebedor = $this->carteiraRepository->obterPorUsuarioComLock($recebedor->id);

                if ($carteiraPagador->saldo < $dto->valor) {
                    throw new SaldoInsuficienteException;
                }

                if (! $this->autorizador->autorizar()) {
                    throw new TransferenciaNaoAutorizadaException;
                }

                $transferencia = $this->transferenciaRepository->criar([
                    'pagador_id' => $pagador->id,
                    'recebedor_id' => $recebedor->id,
                    'email_recebedor' => $recebedor->email,
                    'valor' => $dto->valor,
                    'status' => 'pendente',
                ]);

                $carteiraPagador->saldo -= $dto->valor;
                $carteiraRecebedor->saldo += $dto->valor;

                $this->carteiraRepository->salvar($carteiraPagador);
                $this->carteiraRepository->salvar($carteiraRecebedor);

                $this->transferenciaRepository->marcarSucesso($transferencia);

                return $transferencia;
            });
        } catch (Exception $e) {
            if ($transferencia) {
                $this->transferenciaRepository->marcarFalha($transferencia);
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
                $transferencia->_id,
                $valor,
                $transferencia->email_recebedor
            );
        } catch (Exception $e) {
            Log::error('Falha ao notificar recebimento: '.$e->getMessage(), [
                'transferencia_id' => $transferencia->_id,
            ]);
            $this->transferenciaRepository->marcarFalha($transferencia);
            $transferencia->meta = json_encode(['erro' => $e->getMessage()]);
            $transferencia->save();
        }

        return $transferencia;
    }
}
