<?php

namespace Tests\Feature;

use App\Models\Carteira;
use App\Models\Transferencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransferenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_transferencia_caminho_feliz()
    {
        Http::fake([
            'util.devi.tools/api/v2/authorize' => Http::response(['data' => ['authorization' => true]], 200),
            'util.devi.tools/api/v1/notify' => Http::response(['status' => 'ok'], 200),
        ]);

        $pagador = Usuario::factory()->create(['tipo' => 'comum']);
        $recebedor = Usuario::factory()->create(['tipo' => 'comum']);

        Carteira::factory()->create(['usuario_id' => $pagador->id, 'saldo' => 200]);
        Carteira::factory()->create(['usuario_id' => $recebedor->id, 'saldo' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $pagador->id,
            'payee' => $recebedor->id,
        ]);

        $response->assertStatus(201)->assertJson(["mensagem" => "Transferência realizada com sucesso",]);

        $this->assertDatabaseHas('carteiras', [
            'usuario_id' => $pagador->id,
            'saldo' => 100.00,
        ]);

        $this->assertDatabaseHas('carteiras', [
            'usuario_id' => $recebedor->id,
            'saldo' => 100.00,
        ]);

        $transferencia = Transferencia::where('pagador_id', $pagador->id)
            ->where('recebedor_id', $recebedor->id)
            ->first();

        $this->assertNotNull($transferencia);
        $this->assertEquals(100, $transferencia->valor);
        $this->assertEquals('sucesso', $transferencia->status);
    }
}
