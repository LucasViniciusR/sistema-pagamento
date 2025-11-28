<?php

namespace App\Services\Notificacao;

use App\Contracts\NotificadorDeTransferenciaInterface;
use App\Kafka\KafkaProducer;

class NotificadorKafka implements NotificadorDeTransferenciaInterface
{
    public function __construct(private KafkaProducer $producer) {}

    public function notificar(string $transferenciaId, float $valor, string $email): void
    {
        $this->producer->enviar('transferencias', [
            'transferencia_id' => $transferenciaId,
            'email' => $email,
            'valor' => $valor,
        ]);
    }
}
