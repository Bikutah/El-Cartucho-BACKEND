<?php

namespace Tests\Feature\Hito2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;

class CategoriasMultiplesTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_producto_con_dos_categorias_aparece_al_filtrar_por_cualquiera()
    {
        $cat1 = Categoria::factory()->create(['nombre' => 'Consolas']);
        $cat2 = Categoria::factory()->create(['nombre' => 'Retro']);

        $producto = Producto::factory()->create(['nombre' => 'SNES Classic']);
        $producto->categorias()->attach([$cat1->id, $cat2->id]);

        $res1 = $this->getJson('/ed/producto/listar?categorias[]=' . $cat1->id);
        $res1->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertEquals($producto->id, $res1->json('data.0.id'));

        $res2 = $this->getJson('/ed/producto/listar?categorias[]=' . $cat2->id);
        $res2->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertEquals($producto->id, $res2->json('data.0.id'));
    }

    public function test_filtrar_por_dos_categorias_devuelve_productos_de_ambas_sin_duplicados()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();

        $prod1 = Producto::factory()->create();
        $prod1->categorias()->attach($cat1->id);

        $prod2 = Producto::factory()->create();
        $prod2->categorias()->attach($cat2->id);

        $prodAmbos = Producto::factory()->create();
        $prodAmbos->categorias()->attach([$cat1->id, $cat2->id]);

        $res = $this->getJson("/ed/producto/listar?categorias[]={$cat1->id}&categorias[]={$cat2->id}");
        $res->assertStatus(200);

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertCount(3, $ids);
        $this->assertEquals(count($ids), count(array_unique($ids)));
    }

    public function test_un_producto_sin_categorias_aparece_en_el_listado_general()
    {
        $productoSinCat = Producto::factory()->create(['categoria_id' => null]);
        $productoSinCat->categorias()->detach();

        $res = $this->getJson('/ed/producto/listar');
        $res->assertStatus(200);

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($productoSinCat->id, $ids);
    }

    public function test_categoria_inexistente_devuelve_422()
    {
        $res = $this->getJson('/ed/producto/listar?categorias[]=999999');
        $res->assertStatus(422);
    }

    public function test_parametro_viejo_categoria_id_sigue_funcionando()
    {
        $cat = Categoria::factory()->create();
        $prod = Producto::factory()->create();
        $prod->categorias()->attach($cat->id);

        $res = $this->getJson('/ed/producto/listar?categoria_id=' . $cat->id);
        $res->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertEquals($prod->id, $res->json('data.0.id'));
    }

    public function test_q_combinado_con_categorias_no_trae_productos_de_otras_categorias()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();

        $prodCorrecto = Producto::factory()->create(['nombre' => 'Zelda Ocarina', 'descripcion' => 'Juego retro']);
        $prodCorrecto->categorias()->attach($cat1->id);

        $prodOtraCat = Producto::factory()->create(['nombre' => 'Zelda Majora', 'descripcion' => 'Juego retro']);
        $prodOtraCat->categorias()->attach($cat2->id);

        $res = $this->getJson("/ed/producto/listar?q=Zelda&categorias[]={$cat1->id}");
        $res->assertStatus(200);

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($prodCorrecto->id, $ids);
        $this->assertNotContains($prodOtraCat->id, $ids);
    }

    public function test_n_plus_1_queries_mantenido()
    {
        $cat = Categoria::factory()->create();
        Producto::factory()->count(40)->create()->each(function ($p) use ($cat) {
            $p->categorias()->attach($cat->id);
        });

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/ed/producto/listar?per_page=8');
        $queriesCount8 = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/ed/producto/listar?per_page=40');
        $queriesCount40 = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals($queriesCount8, $queriesCount40);
    }
}
