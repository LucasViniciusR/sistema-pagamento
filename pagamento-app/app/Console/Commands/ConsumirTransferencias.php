<?php

namespace App\Console\Commands;

use App\Kafka\KafkaTransferenciaConsumer;
use Illuminate\Console\Command;

class ConsumirTransferencias extends Command
{
    protected $signature = 'kafka:consumir-transferencias';

    protected $description = 'Consome eventos do tópico transferencias';

    public function handle()
    {
        $consumer = app(KafkaTransferenciaConsumer::class);
        $consumer->processar();

        return 0;
    }
}
