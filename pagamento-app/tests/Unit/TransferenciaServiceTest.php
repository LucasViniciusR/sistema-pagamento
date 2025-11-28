<?php

namespace Tests\Unit\Services;

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
use App\Models\Usuario;
use App\Models\Carteira;
use App\Models\Transferencia;
use App\Repositories\CarteiraRepository;
use App\Repositories\UsuarioRepository;
use App\Services\TransferenciaService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class TransferenciaServiceTest extends TestCase
{
    private $usuarioRepository;
    private $carteiraRepository;
    private $transferenciaRepository;
    private $autorizador;
    private $notificador;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuarioRepository = Mockery::mock(UsuarioRepository::class);
        $this->carteiraRepository = Mockery::mock(CarteiraRepository::class);
        $this->transferenciaRepository = Mockery::mock(TransferenciaRepositoryInterface::class);
        $this->autorizador = Mockery::mock(ServicoDeAutorizacaoInterface::class);
        $this->notificador = Mockery::mock(NotificadorDeTransferenciaInterface::class);

        $this->service = new TransferenciaService(
            $this->usuarioRepository,
            $this->carteiraRepository,
            $this->transferenciaRepository,
            $this->autorizador,
            $this->notificador
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_lancar_excecao_quando_valor_menor_que_minimo()
    {
        $this->expectException(ValorInvalidoException::class);
        $this->expectExceptionMessage('O valor da transferência deve ser pelo menos 0.01');
        $this->expectExceptionCode(422);

        $this->service->transferir(new TransferenciaDTO(
            valor: 0.00,
            pagadorId: 1,
            recebedorId: 2,
        ));
    }

    public function test_lancar_excecao_quando_transferir_para_mesmo_usuario()
    {
        $this->expectException(TransferenciaMesmoUsuarioException::class);
        $this->expectExceptionMessage('Não é possível transferir para si mesmo');
        $this->expectExceptionCode(422);

        $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 1,
        ));
    }

    public function test_lancar_excecao_quando_pagador_nao_encontrado()
    {
        $this->expectException(UsuarioNaoEncontradoException::class);
        $this->expectExceptionMessage('Pagador não encontrado');
        $this->expectExceptionCode(404);

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->with(1)
            ->once()
            ->andReturn(null);

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->with(2)
            ->once()
            ->andReturn($recebedor);

        $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));
    }

    public function test_lancar_excecao_quando_recebedor_nao_encontrado()
    {
        $this->expectException(UsuarioNaoEncontradoException::class);
        $this->expectExceptionMessage('Recebedor não encontrado');
        $this->expectExceptionCode(404);

        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'comum';

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->with(1)
            ->once()
            ->andReturn($pagador);

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->with(2)
            ->once()
            ->andReturn(null);

        $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));
    }

    public function test_lancar_excecao_quando_pagador_e_lojista()
    {
        $this->expectException(TransferenciaNaoPermitidaException::class);
        $this->expectExceptionMessage('Lojistas não podem realizar transferências');
        $this->expectExceptionCode(403);

        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'lojista';

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->with(1)
            ->once()
            ->andReturn($pagador);

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->with(2)
            ->once()
            ->andReturn($recebedor);

        $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));
    }

    public function test_lancar_excecao_quando_saldo_insuficiente()
    {
        $this->expectException(SaldoInsuficienteException::class);
        $this->expectExceptionMessage('Saldo insuficiente para realizar a transferência');
        $this->expectExceptionCode(422);

        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'comum';

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';
        $recebedor->email = 'recebedor@test.com';

        $carteiraPagador = Mockery::mock(Carteira::class)->makePartial();
        $carteiraPagador->saldo = 5.00;

        $carteiraRecebedor = Mockery::mock(Carteira::class)->makePartial();
        $carteiraRecebedor->saldo = 0.00;

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->andReturn($pagador, $recebedor);

        $this->carteiraRepository
            ->shouldReceive('obterPorUsuarioComLock')
            ->with(1)
            ->once()
            ->andReturn($carteiraPagador);

        $this->carteiraRepository
            ->shouldReceive('obterPorUsuarioComLock')
            ->with(2)
            ->once()
            ->andReturn($carteiraRecebedor);

        Log::shouldReceive('error')->once();

        $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));
    }

    public function test_lancar_excecao_quando_transferencia_nao_autorizada()
    {
        $this->expectException(TransferenciaNaoAutorizadaException::class);
        $this->expectExceptionMessage('Transferência não autorizada pelo serviço externo');
        $this->expectExceptionCode(403);

        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'comum';

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';
        $recebedor->email = 'recebedor@test.com';

        $carteiraPagador = Mockery::mock(Carteira::class)->makePartial();
        $carteiraPagador->saldo = 100.00;

        $carteiraRecebedor = Mockery::mock(Carteira::class)->makePartial();
        $carteiraRecebedor->saldo = 0.00;

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->andReturn($pagador, $recebedor);

        $this->carteiraRepository
            ->shouldReceive('obterPorUsuarioComLock')
            ->andReturn($carteiraPagador, $carteiraRecebedor);

        $this->autorizador
            ->shouldReceive('autorizar')
            ->once()
            ->andReturn(false);

        Log::shouldReceive('error')->once();

        $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));
    }

    public function test_realizar_transferencia_com_sucesso()
    {
        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'comum';

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';
        $recebedor->email = 'recebedor@test.com';

        $carteiraPagador = Mockery::mock(Carteira::class)->makePartial();
        $carteiraPagador->saldo = 100.00;

        $carteiraRecebedor = Mockery::mock(Carteira::class)->makePartial();
        $carteiraRecebedor->saldo = 50.00;

        $transferencia = Mockery::mock(Transferencia::class)->makePartial();
        $transferencia->_id = 1;
        $transferencia->status = 'sucesso';

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->andReturn($pagador, $recebedor);

        $this->carteiraRepository
            ->shouldReceive('obterPorUsuarioComLock')
            ->andReturn($carteiraPagador, $carteiraRecebedor);

        $this->autorizador
            ->shouldReceive('autorizar')
            ->once()
            ->andReturn(true);

        $this->transferenciaRepository
            ->shouldReceive('criar')
            ->once()
            ->with([
                'pagador_id' => 1,
                'recebedor_id' => 2,
                'email_recebedor' => 'recebedor@test.com',
                'valor' => 10.00,
                'status' => 'pendente',
            ])
            ->andReturn($transferencia);

        $this->carteiraRepository
            ->shouldReceive('salvar')
            ->twice();

        $this->transferenciaRepository
            ->shouldReceive('marcarSucesso')
            ->once()
            ->with($transferencia);

        $resultado = $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));

        $this->assertEquals(90.00, $carteiraPagador->saldo);
        $this->assertEquals(60.00, $carteiraRecebedor->saldo);
        $this->assertEquals($transferencia, $resultado);
    }

    public function test_marcar_transferencia_como_falha_em_caso_de_erro()
    {
        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'comum';

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';
        $recebedor->email = 'recebedor@test.com';

        $carteiraPagador = Mockery::mock(Carteira::class)->makePartial();
        $carteiraPagador->saldo = 100.00;

        $carteiraRecebedor = Mockery::mock(Carteira::class)->makePartial();
        $carteiraRecebedor->saldo = 50.00;

        $transferencia = Mockery::mock(Transferencia::class)->makePartial();
        $transferencia->_id = 1;

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->andReturn($pagador, $recebedor);

        $this->carteiraRepository
            ->shouldReceive('obterPorUsuarioComLock')
            ->andReturn($carteiraPagador, $carteiraRecebedor);

        $this->autorizador
            ->shouldReceive('autorizar')
            ->once()
            ->andReturn(true);

        $this->transferenciaRepository
            ->shouldReceive('criar')
            ->once()
            ->andReturn($transferencia);

        $this->carteiraRepository
            ->shouldReceive('salvar')
            ->once()
            ->andThrow(new \Exception('Erro ao salvar'));

        $this->transferenciaRepository
            ->shouldReceive('marcarFalha')
            ->once()
            ->with($transferencia);

        Log::shouldReceive('error')->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erro ao salvar');

        $this->service->transferir(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));
    }

    public function test_transferir_e_notificar_com_sucesso()
    {
        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'comum';

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';
        $recebedor->email = 'recebedor@test.com';

        $carteiraPagador = Mockery::mock(Carteira::class)->makePartial();
        $carteiraPagador->saldo = 100.00;

        $carteiraRecebedor = Mockery::mock(Carteira::class)->makePartial();
        $carteiraRecebedor->saldo = 50.00;

        $transferencia = Mockery::mock(Transferencia::class)->makePartial();
        $transferencia->_id = 1;
        $transferencia->email_recebedor = 'recebedor@test.com';
        $transferencia->status = 'sucesso';

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->andReturn($pagador, $recebedor);

        $this->carteiraRepository
            ->shouldReceive('obterPorUsuarioComLock')
            ->andReturn($carteiraPagador, $carteiraRecebedor);

        $this->autorizador
            ->shouldReceive('autorizar')
            ->once()
            ->andReturn(true);

        $this->transferenciaRepository
            ->shouldReceive('criar')
            ->once()
            ->andReturn($transferencia);

        $this->carteiraRepository
            ->shouldReceive('salvar')
            ->twice();

        $this->transferenciaRepository
            ->shouldReceive('marcarSucesso')
            ->once();

        $this->notificador
            ->shouldReceive('notificar')
            ->once()
            ->with(1, 10.00, 'recebedor@test.com');

        $resultado = $this->service->transferirENotificar(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));

        $this->assertEquals($transferencia, $resultado);
    }

    public function test_marcar_falha_quando_notificacao_falha()
    {
        $pagador = Mockery::mock(Usuario::class)->makePartial();
        $pagador->id = 1;
        $pagador->tipo = 'comum';

        $recebedor = Mockery::mock(Usuario::class)->makePartial();
        $recebedor->id = 2;
        $recebedor->tipo = 'comum';
        $recebedor->email = 'recebedor@test.com';

        $carteiraPagador = Mockery::mock(Carteira::class)->makePartial();
        $carteiraPagador->saldo = 100.00;

        $carteiraRecebedor = Mockery::mock(Carteira::class)->makePartial();
        $carteiraRecebedor->saldo = 50.00;

        $transferencia = Mockery::mock(Transferencia::class)->makePartial();
        $transferencia->_id = 1;
        $transferencia->email_recebedor = 'recebedor@test.com';
        $transferencia->shouldReceive('save')->once();

        $this->usuarioRepository
            ->shouldReceive('buscarPorId')
            ->andReturn($pagador, $recebedor);

        $this->carteiraRepository
            ->shouldReceive('obterPorUsuarioComLock')
            ->andReturn($carteiraPagador, $carteiraRecebedor);

        $this->autorizador
            ->shouldReceive('autorizar')
            ->once()
            ->andReturn(true);

        $this->transferenciaRepository
            ->shouldReceive('criar')
            ->once()
            ->andReturn($transferencia);

        $this->carteiraRepository
            ->shouldReceive('salvar')
            ->twice();

        $this->transferenciaRepository
            ->shouldReceive('marcarSucesso')
            ->once();

        $this->notificador
            ->shouldReceive('notificar')
            ->once()
            ->andThrow(new \Exception('Falha na notificação'));

        $this->transferenciaRepository
            ->shouldReceive('marcarFalha')
            ->once()
            ->with($transferencia);

        Log::shouldReceive('error')->once();

        $resultado = $this->service->transferirENotificar(new TransferenciaDTO(
            valor: 10.00,
            pagadorId: 1,
            recebedorId: 2,
        ));

        $this->assertEquals($transferencia, $resultado);
    }
}