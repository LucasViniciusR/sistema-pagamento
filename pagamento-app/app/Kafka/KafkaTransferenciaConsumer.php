<?php

namespace App\Kafka;

use App\Contracts\ServicoDeNotificacaoInterface;
use App\Contracts\TransferenciaRepositoryInterface;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class KafkaTransferenciaConsumer
{
    private KafkaConsumer $consumer;

    private ServicoDeNotificacaoInterface $notificador;

    private TransferenciaRepositoryInterface $transferenciaRepository;

    public function __construct(ServicoDeNotificacaoInterface $notificador, TransferenciaRepositoryInterface $transferenciaRepository, bool $criarConsumerReal = true)
    {
        $this->notificador = $notificador;
        $this->transferenciaRepository = $transferenciaRepository;

        $conf = new Conf;
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $conf->set('group.id', 'transferencias-consumer-v2');
        $conf->set('auto.offset.reset', 'earliest');

        if ($criarConsumerReal) {
            $this->consumer = new KafkaConsumer($conf);
            $this->consumer->subscribe(['transferencias']);
        }
    }

    public function processar()
    {
        while (true) {
            $mensagem = $this->consumer->consume(120 * 1000);

            if ($mensagem === null) {
                continue;
            }

            switch ($mensagem->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $payload = json_decode($mensagem->payload, true);
                    $this->processarTransferencia($payload);
                    break;

                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    break;

                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;

                default:
                    \Log::error('Kafka error: '.$mensagem->errstr());
                    break;
            }
        }
    }

    public function processarTransferencia(array $dados): void
    {
        try {
            $email = $dados['email'] ?? null;

            if ($email) {
                $mensagem = sprintf(
                    'Você recebeu uma transferência de R$%.2f.',
                    $dados['valor']
                );

                $this->notificador->enviar($email, $mensagem);

                $this->transferenciaRepository->atualizarMeta($dados['transferencia_id'], [
                    'notificacao_enviada' => true,
                ]);
            }
        } catch (\Exception $e) {
            $this->transferenciaRepository->atualizarMeta($dados['transferencia_id'], [
                'notificacao_enviada' => false,
                'erro_notificacao' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
