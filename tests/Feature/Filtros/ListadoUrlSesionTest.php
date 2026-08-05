<?php

namespace Tests\Feature\Filtros;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Pedido;

class ListadoUrlSesionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(\App\Models\User::factory()->create());
    }

    public function test_guarda_url_en_sesion_para_productos()
    {
        $response = $this->get('/productos?nombre=x&page=3');
        $response->assertStatus(200);
        $this->assertEquals(url('/productos?nombre=x&page=3'), session('listado_url.productos'));
    }

    public function test_guarda_url_en_sesion_para_categorias()
    {
        $response = $this->get('/categorias?nombre=x&page=3');
        $response->assertStatus(200);
        $this->assertEquals(url('/categorias?nombre=x&page=3'), session('listado_url.categorias'));
    }

    public function test_guarda_url_en_sesion_para_subcategorias()
    {
        $response = $this->get('/subcategorias?nombre=x&page=3');
        $response->assertStatus(200);
        $this->assertEquals(url('/subcategorias?nombre=x&page=3'), session('listado_url.subcategorias'));
    }

    public function test_guarda_url_en_sesion_para_pedidos()
    {
        $response = $this->get('/pedidos?estado=pendiente&page=3');
        $response->assertStatus(200);
        $this->assertEquals(url('/pedidos?estado=pendiente&page=3'), session('listado_url.pedidos'));
    }

    public function test_vista_edicion_renderiza_enlace_vuelta_correcto()
    {
        $producto = Producto::factory()->create();
        
        // Simular que venimos del listado con filtros
        session(['listado_url.productos' => url('/productos?nombre=x&page=3')]);

        $response = $this->get('/productos/' . $producto->id . '/edit');
        $response->assertStatus(200);

        // El breadcrumb o el link de volver debería tener la url de la sesión
        $response->assertSee(url('/productos?nombre=x&page=3'));
        file_put_contents('tests/_output/html.txt', $response->getContent());
    }
}
