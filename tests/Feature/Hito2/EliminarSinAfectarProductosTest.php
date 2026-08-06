<?php

namespace Tests\Feature\Hito2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\User;

class EliminarSinAfectarProductosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_borrar_una_categoria_con_productos_no_borra_ningun_producto()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();
        $prod = Producto::factory()->create();

        $prod->categorias()->attach([$cat1->id, $cat2->id]);

        $this->delete(route('categorias.destroy', $cat1));

        $this->assertDatabaseHas('productos', ['id' => $prod->id]);
        $this->assertDatabaseMissing('categorias', ['id' => $cat1->id]);
    }

    public function test_productos_quedan_con_una_categoria_menos_y_siguen_listandose()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();
        $prod = Producto::factory()->create(['categoria_id' => null]);

        $prod->categorias()->attach([$cat1->id, $cat2->id]);

        $this->delete(route('categorias.destroy', $cat1));

        $this->assertCount(1, $prod->fresh()->categorias);
        $this->assertTrue($prod->fresh()->categorias->contains($cat2));

        $res = $this->getJson('/ed/producto/listar');
        $res->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($prod->id, $ids);
    }

    public function test_borrar_una_subcategoria_no_borra_el_producto()
    {
        $cat = Categoria::factory()->create();
        $sub = Subcategoria::factory()->create(['categoria_id' => $cat->id]);
        $prod = Producto::factory()->create();

        $prod->subcategorias()->attach($sub->id);

        $this->delete(route('subcategorias.destroy', $sub));

        $this->assertDatabaseHas('productos', ['id' => $prod->id]);
        $this->assertDatabaseMissing('subcategorias', ['id' => $sub->id]);
        $this->assertCount(0, $prod->fresh()->subcategorias);
    }

    public function test_borrar_un_producto_limpia_ambos_pivotes()
    {
        $cat = Categoria::factory()->create();
        $sub = Subcategoria::factory()->create(['categoria_id' => $cat->id]);
        $prod = Producto::factory()->create();

        $prod->categorias()->attach($cat->id);
        $prod->subcategorias()->attach($sub->id);

        $this->delete(route('productos.destroy', $prod));

        $this->assertDatabaseMissing('categoria_producto', ['producto_id' => $prod->id]);
        $this->assertDatabaseMissing('producto_subcategoria', ['producto_id' => $prod->id]);
        $this->assertDatabaseMissing('productos', ['id' => $prod->id]);
    }
}
