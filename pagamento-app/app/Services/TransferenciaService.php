<?php

namespace App\Services;

use App\Repositories\CarteiraRepository;
use App\Repositories\TransferenciaRepository;
use App\Repositories\UsuarioRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferenciaService
{
    public function __construct(
        private UsuarioRepository $usuarioRepo,
        private CarteiraRepository $carteiraRepo,
        private TransferenciaRepository $transferenciaRepo,
        private ServicoAutorizacaoExterno $autorizador,
        private ServicoDeNotificacao $notificador
    ) {}

    public function transferir(float $valor, int $pagadorId, int $recebedorId)
    {
        $pagador = $this->usuarioRepo->buscarPorId($pagadorId);
        $recebedor = $this->usuarioRepo->buscarPorId($recebedorId);

        if ($pagador->tipo === 'lojista') {
            throw new Exception('Lojistas não podem enviar dinheiro.');
        }

        if (! $this->autorizador->autorizar()) {
            throw new Exception('Transferência não autorizada pelo serviço externo.');
        }

        return DB::transaction(function () use ($valor, $pagador, $recebedor) {

            $carteiraPagador = $this->carteiraRepo->obterPorUsuarioComLock($pagador->id);
            $carteiraRecebedor = $this->carteiraRepo->obterPorUsuarioComLock($recebedor->id);

            if ($carteiraPagador->saldo < $valor) {
                throw new Exception('Saldo insuficiente.');
            }

            $transferencia = $this->transferenciaRepo->criar([
                'pagador_id' => $pagador->id,
                'recebedor_id' => $recebedor->id,
                'valor' => $valor,
                'status' => 'pendente',
            ]);

            $carteiraPagador->saldo -= $valor;
            $carteiraRecebedor->saldo += $valor;

            $this->carteiraRepo->salvar($carteiraPagador);
            $this->carteiraRepo->salvar($carteiraRecebedor);

            $this->transferenciaRepo->marcarSucesso($transferencia);

            return $transferencia;
        }, 5);
    }

    public function transferirENotificar(float $valor, int $pagadorId, int $recebedorId)
    {
        $transferencia = $this->transferir($valor, $pagadorId, $recebedorId);

        try {
            $recebedor = $this->usuarioRepo->buscarPorId($recebedorId);
            $this->notificador->enviar($recebedor->email, "Você recebeu R$ {$valor}");
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
