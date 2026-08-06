<?php

namespace Tests\Feature\Hito2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Database\QueryException;

class PivotCategoriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_producto_puede_asociarse_a_varias_categorias()
    {
        $producto = Producto::factory()->create();
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();

        $producto->categorias()->attach([$cat1->id, $cat2->id]);

        $this->assertCount(2, $producto->fresh()->categorias);
        $this->assertTrue($producto->categorias->contains($cat1));
        $this->assertTrue($producto->categorias->contains($cat2));
    }

    public function test_no_se_puede_duplicar_el_mismo_par_producto_categoria()
    {
        $producto = Producto::factory()->create();
        $categoria = Categoria::factory()->create();

        $producto->categorias()->attach($categoria->id);

        $this->expectException(QueryException::class);
        $producto->categorias()->attach($categoria->id);
    }

    public function test_borrar_un_producto_elimina_sus_filas_del_pivot()
    {
        $producto = Producto::factory()->create();
        $categoria = Categoria::factory()->create();

        $producto->categorias()->attach($categoria->id);

        $this->assertDatabaseHas('categoria_producto', [
            'producto_id' => $producto->id,
            'categoria_id' => $categoria->id,
        ]);

        $producto->delete();

        $this->assertDatabaseMissing('categoria_producto', [
            'producto_id' => $producto->id,
            'categoria_id' => $categoria->id,
        ]);
    }

    public function test_borrar_una_categoria_elimina_sus_filas_del_pivot_pero_no_borra_los_productos()
    {
        $producto = Producto::factory()->create();
        $categoria = Categoria::factory()->create();

        $producto->categorias()->attach($categoria->id);

        $this->assertDatabaseHas('categoria_producto', [
            'producto_id' => $producto->id,
            'categoria_id' => $categoria->id,
        ]);

        $categoria->delete();

        $this->assertDatabaseMissing('categoria_producto', [
            'categoria_id' => $categoria->id,
        ]);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
        ]);
    }

    public function test_la_relacion_categorias_devuelve_lo_esperado()
    {
        $producto = Producto::factory()->create();
        $categoria = Categoria::factory()->create(['nombre' => 'Juegos de Mesa']);

        $producto->categorias()->attach($categoria->id);

        $categoriasObtenidas = $producto->categorias;

        $this->assertCount(1, $categoriasObtenidas);
        $this->assertEquals('Juegos de Mesa', $categoriasObtenidas->first()->nombre);
    }
}
