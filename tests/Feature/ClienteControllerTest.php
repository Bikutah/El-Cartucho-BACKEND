<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClienteControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->actingAs($this->admin);
    }

    public function test_migracion_user_id_asocia_pedidos_y_deja_huerfanos(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'uid-con-pedidos']);

        $pedidoAsociado = Pedido::create([
            'firebase_uid' => 'uid-con-pedidos',
            'estado'       => 'pagado',
            'total'        => 500,
            'expira_at'    => now()->addHours(72),
        ]);

        $pedidoHuerfano = Pedido::create([
            'firebase_uid' => 'Max Verstappen',
            'estado'       => 'pendiente',
            'total'        => 100,
            'expira_at'    => now()->addHours(72),
        ]);

        // Ejecutar manualmente la lógica del SQL de la migración
        DB::statement("
            UPDATE pedidos
            SET user_id = (SELECT id FROM users WHERE users.firebase_uid = pedidos.firebase_uid)
            WHERE firebase_uid IS NOT NULL
        ");

        $this->assertEquals($user->id, $pedidoAsociado->fresh()->user_id);
        $this->assertNull($pedidoHuerfano->fresh()->user_id);
    }

    public function test_filtros_listado_clientes(): void
    {
        $user1 = User::factory()->create([
            'name'         => 'Carlos',
            'apellido'     => 'Tevez',
            'email'        => 'carlos@example.com',
            'ciudad'       => 'Buenos Aires',
            'firebase_uid' => 'uid-carlos',
        ]);
        $p1 = new Pedido(['user_id' => $user1->id, 'firebase_uid' => $user1->firebase_uid, 'estado' => 'pagado', 'total' => 1000, 'expira_at' => now()->addHours(72)]);
        $p1->created_at = now()->subDays(5);
        $p1->save();

        $user2 = User::factory()->create([
            'name'         => 'Lionel',
            'apellido'     => 'Messi',
            'email'        => 'lionel@example.com',
            'ciudad'       => 'Rosario',
            'firebase_uid' => 'uid-lionel',
        ]);
        $p2 = new Pedido(['user_id' => $user2->id, 'firebase_uid' => $user2->firebase_uid, 'estado' => 'pendiente', 'total' => 500, 'expira_at' => now()->addHours(72)]);
        $p2->created_at = now();
        $p2->save();

        // Filtro nombre_apellido
        $resp = $this->get('/clientes?nombre_apellido=Tevez');
        $resp->assertStatus(200);
        $resp->assertSee('Carlos Tevez');
        $resp->assertDontSee('Lionel Messi');

        // Filtro email
        $resp = $this->get('/clientes?email=lionel@example.com');
        $resp->assertStatus(200);
        $resp->assertSee('Lionel Messi');
        $resp->assertDontSee('Carlos Tevez');

        // Filtro ciudad
        $resp = $this->get('/clientes?ciudad=Rosario');
        $resp->assertStatus(200);
        $resp->assertSee('Lionel Messi');

        // Filtro ultimo_estado
        $resp = $this->get('/clientes?ultimo_estado=pagado');
        $resp->assertStatus(200);
        $resp->assertSee('Carlos Tevez');
        $resp->assertDontSee('Lionel Messi');

        // Filtro fechas
        $resp = $this->get('/clientes?fecha_desde=' . now()->subDays(10)->format('Y-m-d') . '&fecha_hasta=' . now()->subDays(2)->format('Y-m-d'));
        $resp->assertStatus(200);
        $resp->assertSee('Carlos Tevez');
        $resp->assertDontSee('Lionel Messi');
    }

    public function test_filtro_invalido_devuelve_422_nunca_500(): void
    {
        // Estado inválido
        $response = $this->getJson('/clientes?ultimo_estado=invalido');
        $response->assertStatus(422);

        // Fecha hasta menor a fecha desde
        $response = $this->getJson('/clientes?fecha_desde=2026-08-10&fecha_hasta=2026-08-01');
        $response->assertStatus(422);
    }

    public function test_n_plus_1_queries_listado_clientes_constante_entre_8_y_40_clientes(): void
    {
        // Escenario 1: 8 clientes
        User::factory()->count(8)->create(['firebase_uid' => fn() => 'uid-' . uniqid()])->each(function ($user) {
            Pedido::create(['user_id' => $user->id, 'firebase_uid' => $user->firebase_uid, 'estado' => 'pagado', 'total' => 100, 'expira_at' => now()->addHours(72)]);
        });

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/clientes')->assertStatus(200);
        $queries8 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Escenario 2: 40 clientes
        User::factory()->count(32)->create(['firebase_uid' => fn() => 'uid-' . uniqid()])->each(function ($user) {
            Pedido::create(['user_id' => $user->id, 'firebase_uid' => $user->firebase_uid, 'estado' => 'pagado', 'total' => 100, 'expira_at' => now()->addHours(72)]);
        });

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/clientes')->assertStatus(200);
        $queries40 = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals($queries8, $queries40, "La cantidad de queries N+1 debe ser constante ({$queries8} vs {$queries40})");
    }

    public function test_cliente_sin_pedidos_se_lista_y_muestra_detalle_sin_romper(): void
    {
        $clienteSinPedidos = User::factory()->create([
            'name'         => 'Sin',
            'apellido'     => 'Pedidos',
            'email'        => 'sinpedidos@example.com',
            'firebase_uid' => 'uid-sinpedidos',
        ]);

        $respList = $this->get('/clientes');
        $respList->assertStatus(200);
        $respList->assertSee('Sin Pedidos');
        $respList->assertSee('Sin pedidos');

        $respDetail = $this->get("/clientes/{$clienteSinPedidos->id}");
        $respDetail->assertStatus(200);
        $respDetail->assertSee('Este cliente no tiene pedidos registrados.');
        $respDetail->assertSee('sinpedidos@example.com');
    }

    public function test_pedidos_huerfanos_no_rompen_listado_de_pedidos_y_muestran_texto_neutro(): void
    {
        $pedidoHuerfano = Pedido::create([
            'firebase_uid' => 'Max Verstappen',
            'estado'       => 'pendiente',
            'total'        => 100,
            'expira_at'    => now()->addHours(72),
        ]);

        $resp = $this->get('/pedidos');
        $resp->assertStatus(200);
        $resp->assertSee('Sin cliente asociado');

        $respShow = $this->get("/pedidos/{$pedidoHuerfano->id}");
        $respShow->assertStatus(200);
        $respShow->assertSee('Sin cliente asociado');
        $respShow->assertSee('Max Verstappen');
    }
}
