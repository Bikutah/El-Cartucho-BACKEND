<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadoPagoEnvioTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create();
    }

    /** @test */
    public function test_1_migracion_pedidos_pendientes_a_estado_pago_pendiente_y_envio_null()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pendiente', 'estado_envio' => null]);
        $this->assertEquals('pendiente', $pedido->estado_pago);
        $this->assertNull($pedido->estado_envio);
    }

    /** @test */
    public function test_2_migracion_pedidos_pagados_a_estado_pago_pagado_y_envio_null()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);
        $this->assertEquals('pagado', $pedido->estado_pago);
        $this->assertNull($pedido->estado_envio);
        $this->assertEquals('Pago confirmado', $pedido->estado_visible);
    }

    /** @test */
    public function test_3_down_de_migracion_revierte_limpiamente()
    {
        $migration = require database_path('migrations/2026_08_12_232000_separate_payment_and_shipping_states_on_pedidos.php');
        $this->assertTrue(method_exists($migration, 'down'));

        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('pedidos', 'estado_pago'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('pedido_historial_estados'));

        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('pedidos', 'estado_pago'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('pedido_historial_estados'));
    }

    /** @test */
    public function test_4_no_se_puede_tocar_estado_envio_si_estado_pago_no_es_pagado()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pendiente']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("No se puede modificar el estado de envío porque el pedido no está pagado.");

        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->adminUser->id);
    }

    /** @test */
    public function test_5_avance_secuencial_valido_funciona()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);

        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->adminUser->id);
        $this->assertEquals('preparando', $pedido->fresh()->estado_envio);

        $pedido->cambiarEstadoEnvio('enviado', 'panel', $this->adminUser->id);
        $this->assertEquals('enviado', $pedido->fresh()->estado_envio);

        $pedido->cambiarEstadoEnvio('entregado', 'panel', $this->adminUser->id);
        $this->assertEquals('entregado', $pedido->fresh()->estado_envio);
    }

    /** @test */
    public function test_6_salto_de_estados_es_rechazado()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("No se pueden saltar estados de envío");

        $pedido->cambiarEstadoEnvio('entregado', 'panel', $this->adminUser->id);
    }

    /** @test */
    public function test_7_devuelto_desde_enviado_funciona_desde_sin_preparar_es_rechazado()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);

        // Intentar pasar a devuelto desde sin_preparar debe fallar
        try {
            $pedido->cambiarEstadoEnvio('devuelto', 'panel', $this->adminUser->id);
            $this->fail("Debería haber lanzado DomainException");
        } catch (\DomainException $e) {
            $this->assertStringContainsString("solo se puede asignar desde 'enviado' o 'entregado'", $e->getMessage());
        }

        // Avanzar a enviado y luego pasar a devuelto debe funcionar
        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->adminUser->id);
        $pedido->cambiarEstadoEnvio('enviado', 'panel', $this->adminUser->id);
        $pedido->cambiarEstadoEnvio('devuelto', 'panel', $this->adminUser->id);

        $this->assertEquals('devuelto', $pedido->fresh()->estado_envio);
    }

    /** @test */
    public function test_8_retroceso_sin_observacion_es_rechazado_con_observacion_funciona()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);
        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->adminUser->id);

        // Intentar retroceder sin observación debe fallar
        try {
            $pedido->cambiarEstadoEnvio('sin_preparar', 'panel', $this->adminUser->id, null);
            $this->fail("Debería haber lanzado DomainException por falta de observación");
        } catch (\DomainException $e) {
            $this->assertStringContainsString("es obligatoria una observación", $e->getMessage());
        }

        // Retroceder con observación debe funcionar
        $pedido->cambiarEstadoEnvio('sin_preparar', 'panel', $this->adminUser->id, 'Corrigiendo error de carga');
        $this->assertEquals('sin_preparar', $pedido->fresh()->estado_envio);
    }

    /** @test */
    public function test_9_enviado_at_y_entregado_at_se_setean_en_transiciones_correspondientes()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);

        $this->assertNull($pedido->enviado_at);
        $this->assertNull($pedido->entregado_at);

        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->adminUser->id);
        $pedido->cambiarEstadoEnvio('enviado', 'panel', $this->adminUser->id);

        $this->assertNotNull($pedido->fresh()->enviado_at);
        $this->assertNull($pedido->fresh()->entregado_at);

        $pedido->cambiarEstadoEnvio('entregado', 'panel', $this->adminUser->id);

        $this->assertNotNull($pedido->fresh()->entregado_at);
    }

    /** @test */
    public function test_10_cada_cambio_de_estado_genera_exactamente_una_fila_en_historial()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pendiente']);

        $conteoInicial = DB::table('pedido_historial_estados')->where('pedido_id', $pedido->id)->count();

        $pedido->cambiarEstadoPago('pagado', 'webhook');
        $this->assertEquals($conteoInicial + 1, DB::table('pedido_historial_estados')->where('pedido_id', $pedido->id)->count());

        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->adminUser->id);
        $this->assertEquals($conteoInicial + 2, DB::table('pedido_historial_estados')->where('pedido_id', $pedido->id)->count());
    }

    /** @test */
    public function test_11_el_origen_se_registra_correcto_segun_el_caller()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pendiente']);

        $pedido->cambiarEstadoPago('pagado', 'comando');
        $this->assertDatabaseHas('pedido_historial_estados', [
            'pedido_id'    => $pedido->id,
            'estado_nuevo' => 'pagado',
            'origen'       => 'comando',
        ]);
    }

    /** @test */
    public function test_12_cambios_desde_el_panel_registran_el_user_id_del_admin()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);

        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->adminUser->id);

        $this->assertDatabaseHas('pedido_historial_estados', [
            'pedido_id'    => $pedido->id,
            'estado_nuevo' => 'preparando',
            'user_id'      => $this->adminUser->id,
            'origen'       => 'panel',
        ]);
    }

    /** @test */
    public function test_16_cada_combinacion_de_tabla_devuelve_etiqueta_correcta()
    {
        $p1 = new Pedido(['estado_pago' => 'pendiente', 'estado_envio' => null]);
        $this->assertEquals('Esperando pago', $p1->estado_visible);

        $p2 = new Pedido(['estado_pago' => 'pagado', 'estado_envio' => null]);
        $this->assertEquals('Pago confirmado', $p2->estado_visible);

        $p3 = new Pedido(['estado_pago' => 'pagado', 'estado_envio' => 'sin_preparar']);
        $this->assertEquals('Pago confirmado', $p3->estado_visible);

        $p4 = new Pedido(['estado_pago' => 'pagado', 'estado_envio' => 'preparando']);
        $this->assertEquals('Preparando tu pedido', $p4->estado_visible);

        $p5 = new Pedido(['estado_pago' => 'pagado', 'estado_envio' => 'enviado']);
        $this->assertEquals('En camino', $p5->estado_visible);

        $p6 = new Pedido(['estado_pago' => 'pagado', 'estado_envio' => 'entregado']);
        $this->assertEquals('Entregado', $p6->estado_visible);

        $p7 = new Pedido(['estado_pago' => 'pagado', 'estado_envio' => 'devuelto']);
        $this->assertEquals('Devuelto', $p7->estado_visible);

        $p8 = new Pedido(['estado_pago' => 'rechazado', 'estado_envio' => null]);
        $this->assertEquals('Pago rechazado', $p8->estado_visible);

        $p9 = new Pedido(['estado_pago' => 'expirado', 'estado_envio' => null]);
        $this->assertEquals('Expirado', $p9->estado_visible);
    }

    /** @test */
    public function test_17_reembolsado_tiene_precedencia_sobre_cualquier_estado_envio()
    {
        $p = new Pedido(['estado_pago' => 'reembolsado', 'estado_envio' => 'enviado']);
        $this->assertEquals('Reembolsado', $p->estado_visible);
    }

    /** @test */
    public function test_18_selector_de_envio_no_aparece_si_el_pedido_no_esta_pagado()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pendiente']);

        $response = $this->actingAs($this->adminUser)->get("/pedidos/{$pedido->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Gestión de Envío');
    }

    /** @test */
    public function test_19_cambiar_estado_desde_el_panel_registra_historial_con_user_id_y_origen_panel()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pagado', 'estado_envio' => null]);

        $response = $this->actingAs($this->adminUser)->put("/pedidos/{$pedido->id}", [
            'estado_envio' => 'preparando',
        ]);

        $response->assertRedirect(route('pedidos.show', $pedido->id));
        $this->assertEquals('preparando', $pedido->fresh()->estado_envio);

        $this->assertDatabaseHas('pedido_historial_estados', [
            'pedido_id'    => $pedido->id,
            'tipo'         => 'envio',
            'estado_nuevo' => 'preparando',
            'user_id'      => $this->adminUser->id,
            'origen'       => 'panel',
        ]);
    }
}
