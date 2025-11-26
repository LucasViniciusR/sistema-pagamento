<?php

namespace App\Kafka;

use RdKafka\Conf;
use RdKafka\Producer;

class KafkaProducer
{
    private Producer $producer;

    public function __construct()
    {
        $conf = new Conf;
        $conf->set('metadata.broker.list', 'kafka:19092');

        $this->producer = new Producer($conf);
    }

    public function enviar(string $topico, array $payload): void
    {
        $topic = $this->producer->newTopic($topico);

        $topic->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            json_encode($payload)
        );

        $this->producer->flush(10 * 1000);
    }
}
