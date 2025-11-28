<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Contracts\TransferenciaRepositoryInterface;
use App\Kafka\KafkaTransferenciaConsumer;
use App\Services\Notificacao\ServicoDeNotificacao;
use App\Models\Transferencia;
use Mockery;

class KafkaTransferenciaConsumerTest extends TestCase
{
    private ServicoDeNotificacao $notificador;
    private TransferenciaRepositoryInterface $transferenciaRepository;
    private KafkaTransferenciaConsumer $consumer;
    private Transferencia $transferenciaFake;
    private array $dados;

    public function setUp(): void
    {
        parent::setUp();

        $this->notificador = Mockery::mock(ServicoDeNotificacao::class);
        $this->transferenciaRepository = Mockery::mock(TransferenciaRepositoryInterface::class);

        $this->transferenciaFake = new Transferencia();
        $this->transferenciaFake->_id = '123abc';

        $this->dados = [
            'transferencia' => $this->transferenciaFake,
            'valor' => 100,
            'email' => 'teste@email.com',
        ];

        $this->consumer = new KafkaTransferenciaConsumer(
            $this->notificador,
            $this->transferenciaRepository,
            false
        );
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_processarTransferencia_envia_notificacao_e_atualiza_sucesso()
    {
        $this->notificador->shouldReceive('enviar')
            ->once()
            ->with($this->dados['email'], 'Você recebeu uma transferência de R$100.00.');

        $this->transferenciaRepository->shouldReceive('marcarSucesso')
            ->once()
            ->with($this->transferenciaFake);

        $this->transferenciaRepository->shouldReceive('marcarFalha')->never();

        $this->consumer->processarTransferencia($this->dados);

        $this->assertTrue(true);
    }

    public function test_processarTransferencia_trata_excecao_e_atualiza_falha()
    {
        $this->notificador->shouldReceive('enviar')
            ->once()
            ->andThrow(new \Exception('Falha ao enviar'));

        $this->transferenciaRepository->shouldReceive('marcarFalha')
            ->once()
            ->with($this->transferenciaFake);

        $this->transferenciaRepository->shouldReceive('marcarSucesso')->never();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Falha ao enviar');

        $this->consumer->processarTransferencia($this->dados);
    }
}
