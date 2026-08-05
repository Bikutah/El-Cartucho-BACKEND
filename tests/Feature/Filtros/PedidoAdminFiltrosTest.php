<?php

namespace Tests\Feature\Filtros;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class PedidoAdminFiltrosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_filtro_total_min_abc_no_produce_500()
    {
        $response = $this->get('/pedidos?total_min=abc');
        
        // Verifica que no haya un 500 y que la validaciÃ³n falle (302 redirect o 422 con errores de sesiÃ³n)
        $response->assertStatus(302);
        $response->assertSessionHasErrors('total_min');
    }
}
