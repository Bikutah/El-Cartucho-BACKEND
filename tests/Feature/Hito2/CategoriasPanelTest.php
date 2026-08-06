<?php

namespace Tests\Feature\Hito2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;

class CategoriasPanelTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
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
}
