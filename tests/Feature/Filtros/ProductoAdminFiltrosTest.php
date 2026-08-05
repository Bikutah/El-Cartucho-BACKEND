<?php

namespace Tests\Feature\Filtros;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Categoria;

class ProductoAdminFiltrosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(\App\Models\User::factory()->create());
    }

    public function test_filtro_stock_exacto()
    {
        Producto::factory()->create(['stock' => 5]);
        Producto::factory()->create(['stock' => 15]);
        Producto::factory()->create(['stock' => 25]);
        Producto::factory()->create(['stock' => 50]);

        $response = $this->get('/productos?stock=5');
        
        $response->assertStatus(200);
        $response->assertSee('value="5"', false);
        
        // El contenido del paginator debería tener sólo 1 item
        $productos = $response->viewData('productos');
        $this->assertCount(1, $productos);
        $this->assertEquals(5, $productos->first()->stock);
    }

    public function test_filtro_stock_abc_no_produce_500()
    {
        $response = $this->get('/productos?stock=abc');
        $response->assertStatus(302); // Fallo de validación (redirecciona o tira error de validación)
        // O si devuelve errores de sesión
        $response->assertSessionHasErrors('stock');
    }

    public function test_filtro_por_categoria_no_trae_parecidos()
    {
        $cat1 = Categoria::factory()->create(['nombre' => 'Consolas']);
        $cat2 = Categoria::factory()->create(['nombre' => 'Consolas retro']);

        Producto::factory()->create(['categoria_id' => $cat1->id]);
        Producto::factory()->create(['categoria_id' => $cat2->id]);

        $response = $this->get('/productos?categoria_id=' . $cat1->id);
        
        $response->assertStatus(200);
        $productos = $response->viewData('productos');
        
        $this->assertCount(1, $productos);
        $this->assertEquals($cat1->id, $productos->first()->categoria_id);
    }

    public function test_action_del_form_no_contiene_http()
    {
        $response = $this->get('/productos');
        $response->assertStatus(200);
        
        // Verifica que el action sea "" o no contenga http://localhost
        $response->assertDontSee('action="http://');
        $response->assertDontSee('action="https://');
        // El action real ahora es action="" en el código
        $response->assertSee('action=""', false);
    }
}
