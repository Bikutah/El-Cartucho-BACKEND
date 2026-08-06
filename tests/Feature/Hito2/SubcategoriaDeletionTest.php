<?php

namespace Tests\Feature\Hito2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SubcategoriaDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_escenario_a_borrar_subcategoria_sin_productos()
    {
        $cat = Categoria::factory()->create();
        $sub = Subcategoria::factory()->create(['categoria_id' => $cat->id]);

        $prodCountBefore = Producto::count();
        $response = $this->actingAs($this->user)->delete('/subcategorias/' . $sub->id);
        $prodCountAfter = Producto::count();

        $response->assertStatus(302);
        $this->assertDatabaseMissing('subcategorias', ['id' => $sub->id]);
        $this->assertEquals($prodCountBefore, $prodCountAfter);
    }

    public function test_escenario_b_borrar_subcategoria_con_productos()
    {
        $cat = Categoria::factory()->create();
        $sub = Subcategoria::factory()->create(['categoria_id' => $cat->id]);
        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->subcategorias()->attach($sub->id);

        $prodCountBefore = Producto::count();
        $response = $this->actingAs($this->user)->delete('/subcategorias/' . $sub->id);
        $prodCountAfter = Producto::count();

        $prodFresh = $prod->fresh();
        $hasSubcat = $prodFresh->subcategorias->contains($sub->id);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('subcategorias', ['id' => $sub->id]);
        $this->assertEquals($prodCountBefore, $prodCountAfter);
        $this->assertFalse($hasSubcat);
    }

    public function test_escenario_c_borrar_categoria_sin_productos()
    {
        $cat = Categoria::factory()->create();

        $prodCountBefore = Producto::count();
        $response = $this->actingAs($this->user)->delete('/categorias/' . $cat->id);
        $prodCountAfter = Producto::count();

        $response->assertStatus(302);
        $this->assertDatabaseMissing('categorias', ['id' => $cat->id]);
        $this->assertEquals($prodCountBefore, $prodCountAfter);
    }

    public function test_escenario_d_borrar_categoria_con_subcategorias_y_productos()
    {
        $cat = Categoria::factory()->create();
        $sub = Subcategoria::factory()->create(['categoria_id' => $cat->id]);
        $prod = Producto::factory()->create(['categoria_id' => $cat->id]);
        $prod->categorias()->syncWithoutDetaching([$cat->id]);
        $prod->subcategorias()->syncWithoutDetaching([$sub->id]);

        $prodCountBefore = Producto::count();
        $response = $this->actingAs($this->user)->delete('/categorias/' . $cat->id);
        $prodCountAfter = Producto::count();

        $prodFresh = $prod->fresh();

        $response->assertStatus(302);
        $this->assertDatabaseMissing('categorias', ['id' => $cat->id]);
        $this->assertDatabaseMissing('subcategorias', ['id' => $sub->id]);
        $this->assertEquals($prodCountBefore, $prodCountAfter);
        $this->assertNull($prodFresh->categoria_id);
    }

    public function test_producto_con_dos_subcategorias_se_borra_una_y_queda_con_una()
    {
        $cat = Categoria::factory()->create();
        $sub1 = Subcategoria::factory()->create(['categoria_id' => $cat->id]);
        $sub2 = Subcategoria::factory()->create(['categoria_id' => $cat->id]);

        $prod = Producto::factory()->create(['categoria_id' => null]);
        $prod->subcategorias()->sync([$sub1->id, $sub2->id]);

        $this->assertCount(2, $prod->subcategorias);

        $response = $this->actingAs($this->user)->delete('/subcategorias/' . $sub1->id);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('subcategorias', ['id' => $sub1->id]);
        $this->assertDatabaseHas('subcategorias', ['id' => $sub2->id]);

        $prodFresh = $prod->fresh();
        $this->assertCount(1, $prodFresh->subcategorias);
        $this->assertTrue($prodFresh->subcategorias->contains($sub2->id));
        $this->assertFalse($prodFresh->subcategorias->contains($sub1->id));
    }

    public function test_borrar_categoria_con_tres_subcategorias_las_tres_desaparecen_y_productos_subsisten()
    {
        $cat = Categoria::factory()->create();
        $subs = Subcategoria::factory()->count(3)->create(['categoria_id' => $cat->id]);

        $prod = Producto::factory()->create(['categoria_id' => $cat->id]);
        $prod->categorias()->syncWithoutDetaching([$cat->id]);
        foreach ($subs as $s) {
            $prod->subcategorias()->syncWithoutDetaching([$s->id]);
        }

        $response = $this->actingAs($this->user)->delete('/categorias/' . $cat->id);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('categorias', ['id' => $cat->id]);
        foreach ($subs as $s) {
            $this->assertDatabaseMissing('subcategorias', ['id' => $s->id]);
        }
        $this->assertNotNull($prod->fresh());
    }

    public function test_verificar_fk_producto_subcategoria_en_esquema()
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            $constraints = DB::select("SELECT conname, confdeltype FROM pg_constraint WHERE conrelid = 'producto_subcategoria'::regclass AND contype = 'f';");
            $this->assertNotEmpty($constraints);
            foreach ($constraints as $c) {
                $this->assertEquals('c', $c->confdeltype, "FK {$c->conname} debe tener confdeltype = 'c' (CASCADE)");
            }
        } else {
            $this->assertTrue(Schema::hasTable('producto_subcategoria'));
        }
    }
}
