<?php

namespace Tests\Unit;

use App\Contracts\ServicoDeNotificacaoInterface;
use App\Contracts\TransferenciaRepositoryInterface;
use App\Kafka\KafkaTransferenciaConsumer;
use Mockery;
use Tests\TestCase;

class KafkaTransferenciaConsumerTest extends TestCase
{
    private ServicoDeNotificacaoInterface $notificador;

    private TransferenciaRepositoryInterface $transferenciaRepository;

    private KafkaTransferenciaConsumer $consumer;

    private array $dados;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificador = Mockery::mock(ServicoDeNotificacaoInterface::class);
        $this->transferenciaRepository = Mockery::mock(TransferenciaRepositoryInterface::class);
        $consumerMock = Mockery::mock(KafkaConsumer::class);

        $this->dados = [
            'transferencia_id' => '123abc',
            'valor' => 100,
            'email' => 'teste@email.com',
        ];

        $this->consumer = new KafkaTransferenciaConsumer(
            $this->notificador,
            $this->transferenciaRepository,
            false
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_processar_transferencia_envia_notificacao_e_atualiza_sucesso()
    {
        $this->notificador->shouldReceive('enviar')
            ->once()
            ->with($this->dados['email'], 'Você recebeu uma transferência de R$100.00.');

        $this->transferenciaRepository->shouldReceive('atualizarMeta')
            ->once()
            ->with('123abc', [
                'notificacao_enviada' => true,
            ]);

        $this->consumer->processarTransferencia($this->dados);

        $this->assertTrue(true);
    }

    public function test_processar_transferencia_trata_excecao_e_atualiza_falha()
    {
        $this->notificador->shouldReceive('enviar')
            ->once()
            ->andThrow(new \Exception('Falha ao enviar'));

        $this->transferenciaRepository->shouldReceive('atualizarMeta')
            ->once()
            ->with('123abc', [
                'notificacao_enviada' => false,
                'erro_notificacao' => 'Falha ao enviar',
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Falha ao enviar');

        $this->consumer->processarTransferencia($this->dados);
    }
}
