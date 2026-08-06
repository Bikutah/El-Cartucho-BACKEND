<?php

namespace Tests\Feature\Hito2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Support\Facades\DB;

class CategoriasPanelTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_get_productos_renderiza_200_y_no_contiene_directivas_blade_literales()
    {
        $cat = Categoria::factory()->create();
        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->categorias()->attach($cat->id);

        $response = $this->actingAs($this->user)->get('/productos');

        $response->assertStatus(200);
        $response->assertDontSee('@if');
        $response->assertDontSee('@foreach');
    }

    public function test_producto_con_dos_categorias_muestra_ambas_en_el_listado()
    {
        $cat1 = Categoria::factory()->create(['nombre' => 'Consolas Retro']);
        $cat2 = Categoria::factory()->create(['nombre' => 'Cartuchos']);
        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->categorias()->sync([$cat1->id, $cat2->id]);

        $response = $this->actingAs($this->user)->get('/productos');

        $response->assertStatus(200);
        $response->assertSee('Consolas Retro');
        $response->assertSee('Cartuchos');
    }

    public function test_producto_sin_categorias_no_rompe_el_listado()
    {
        Producto::factory()->create(['categoria_id' => null]);

        $response = $this->actingAs($this->user)->get('/productos');

        $response->assertStatus(200);
        $response->assertSee('Sin categoría');
    }

    public function test_get_subcategorias_renderiza_el_boton_de_eliminar_en_cada_fila()
    {
        $cat = Categoria::factory()->create();
        Subcategoria::factory()->create(['categoria_id' => $cat->id]);

        $response = $this->actingAs($this->user)->get('/subcategorias');

        $response->assertStatus(200);
        $response->assertSee('modalEliminar');
        $response->assertSee('title="Eliminar"', false);
    }

    public function test_get_producto_edit_trae_las_categorias_del_producto_preseleccionadas()
    {
        $cat1 = Categoria::factory()->create(['nombre' => 'Juegos CD']);
        $cat2 = Categoria::factory()->create(['nombre' => 'Accesorios']);
        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->categorias()->sync([$cat1->id, $cat2->id]);

        $response = $this->actingAs($this->user)->get('/productos/' . $prod->id . '/edit');

        $response->assertStatus(200);
        $response->assertSee('initial-categories');
        $response->assertSee((string)$cat1->id);
        $response->assertSee((string)$cat2->id);
    }

    public function test_borrar_categoria_desde_el_panel_devuelve_exito_y_no_borra_productos()
    {
        $cat = Categoria::factory()->create();
        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->categorias()->attach($cat->id);

        $response = $this->actingAs($this->user)
            ->delete('/categorias/' . $cat->id);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('categorias', ['id' => $cat->id]);
        $this->assertDatabaseHas('productos', ['id' => $prod->id]);
        $this->assertNull($prod->fresh()->categoria_id);
        $this->assertCount(0, $prod->fresh()->categorias);
    }

    public function test_borrar_subcategoria_desde_el_panel_devuelve_exito()
    {
        $cat = Categoria::factory()->create();
        $sub = Subcategoria::factory()->create(['categoria_id' => $cat->id]);
        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->subcategorias()->attach($sub->id);

        $response = $this->actingAs($this->user)
            ->delete('/subcategorias/' . $sub->id);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('subcategorias', ['id' => $sub->id]);
        $this->assertDatabaseHas('productos', ['id' => $prod->id]);
    }

    public function test_crear_producto_desde_el_panel_con_dos_categorias_las_guarda_ambas()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();

        $response = $this->actingAs($this->user)
            ->post('/productos', [
                'nombre' => 'Producto Múltiple',
                'descripcion' => 'Descripción de prueba',
                'precioUnitario' => 1200,
                'stock' => 15,
                'categorias' => [$cat1->id, $cat2->id],
            ]);

        $response->assertRedirect(route('productos.index'));

        $prod = Producto::where('nombre', 'Producto Múltiple')->first();
        $this->assertNotNull($prod);
        $this->assertCount(2, $prod->categorias);
        $this->assertTrue($prod->categorias->contains($cat1->id));
        $this->assertTrue($prod->categorias->contains($cat2->id));
    }

    public function test_crear_producto_desde_el_panel_sin_categorias_funciona()
    {
        $response = $this->actingAs($this->user)
            ->post('/productos', [
                'nombre' => 'Producto Libre',
                'descripcion' => 'Sin categorías',
                'precioUnitario' => 500,
                'stock' => 5,
            ]);

        $response->assertRedirect(route('productos.index'));

        $prod = Producto::where('nombre', 'Producto Libre')->first();
        $this->assertNotNull($prod);
        $this->assertCount(0, $prod->categorias);
        $this->assertNull($prod->categoria_id);
    }

    public function test_editar_producto_cambiando_solo_el_nombre_mantiene_sus_categorias()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();

        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->categorias()->sync([$cat1->id, $cat2->id]);

        $response = $this->actingAs($this->user)
            ->put('/productos/' . $prod->id, [
                'nombre' => 'Nombre Editado',
                'descripcion' => $prod->descripcion,
                'precioUnitario' => $prod->precioUnitario,
                'stock' => $prod->stock,
                'categorias' => [$cat1->id, $cat2->id],
            ]);

        $response->assertRedirect();
        $this->assertEquals('Nombre Editado', $prod->fresh()->nombre);
        $this->assertCount(2, $prod->fresh()->categorias);
    }

    public function test_editar_producto_quitando_una_categoria_las_sincroniza()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();

        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->categorias()->sync([$cat1->id, $cat2->id]);

        $response = $this->actingAs($this->user)
            ->put('/productos/' . $prod->id, [
                'nombre' => $prod->nombre,
                'descripcion' => $prod->descripcion,
                'precioUnitario' => $prod->precioUnitario,
                'stock' => $prod->stock,
                'categorias' => [$cat1->id],
            ]);

        $response->assertRedirect();
        $this->assertCount(1, $prod->fresh()->categorias);
        $this->assertTrue($prod->fresh()->categorias->contains($cat1->id));
        $this->assertFalse($prod->fresh()->categorias->contains($cat2->id));
    }

    public function test_n_plus_1_queries_listado_admin()
    {
        $cat = Categoria::factory()->create();
        
        for ($i = 0; $i < 40; $i++) {
            $p = Producto::factory()->create(['categoria_id' => null]);
            $p->categorias()->attach($cat->id);
        }

        DB::enableQueryLog();
        $this->actingAs($this->user)->get('/productos');
        $queriesListado = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Verificar que no haya queries excesivas
        $this->assertLessThan(15, $queriesListado);
    }
}
