<?php

namespace Tests\Feature\Filtros;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Imagen;
use Illuminate\Support\Facades\DB;

class BuscarProductosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_q_con_termino_existente_devuelve_solo_coincidencias()
    {
        Producto::factory()->create(['nombre' => 'Teclado Mecanico', 'descripcion' => '...']);
        Producto::factory()->create(['nombre' => 'Mouse', 'descripcion' => 'Un teclado de regalo']);
        Producto::factory()->create(['nombre' => 'Monitor', 'descripcion' => '144hz']);

        $response = $this->getJson('/ed/producto/listar?q=teclado');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(2, $data);
    }

    public function test_q_vacio_no_filtra()
    {
        Producto::factory()->count(5)->create();

        $response = $this->getJson('/ed/producto/listar?q=');
        
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_q_y_categoria_no_devuelve_productos_de_otra_categoria()
    {
        $cat1 = Categoria::factory()->create();
        $cat2 = Categoria::factory()->create();

        // Producto en cat1 que coincide con la búsqueda
        Producto::factory()->create([
            'nombre' => 'TérminoX',
            'categoria_id' => $cat1->id
        ]);

        // Producto en cat2 que coincide con la búsqueda
        Producto::factory()->create([
            'nombre' => 'TérminoX',
            'categoria_id' => $cat2->id
        ]);

        $response = $this->getJson('/ed/producto/listar?q=TérminoX&categoria_id=' . $cat1->id);

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals($cat1->nombre, $data[0]['categoria']);
    }

    public function test_parametros_invalidos_devuelven_422()
    {
        // orden, dir, precios
        $this->getJson('/ed/producto/listar?orden=stock')->assertStatus(422);
        $this->getJson('/ed/producto/listar?orden=DROP')->assertStatus(422);
        $this->getJson('/ed/producto/listar?dir=random')->assertStatus(422);
        $this->getJson('/ed/producto/listar?precio_min=100&precio_max=10')->assertStatus(422);
        $this->getJson('/ed/producto/listar?per_page=99999')->assertStatus(422);
        $this->getJson('/ed/producto/listar?per_page=0')->assertStatus(422);
        $this->getJson('/ed/producto/listar?categoria_id=999999')->assertStatus(422);
    }

    public function test_q_con_script_devuelve_200()
    {
        $response = $this->getJson('/ed/producto/listar?q=<script>alert(1)</script>');
        $response->assertStatus(200);
    }

    public function test_meta_y_links_conservan_ambos_filtros()
    {
        $cat = Categoria::factory()->create();
        Producto::factory()->count(15)->create([
            'nombre' => 'Producto X',
            'categoria_id' => $cat->id
        ]);

        $response = $this->getJson('/ed/producto/listar?q=x&categoria_id=' . $cat->id . '&page=2');
        $response->assertStatus(200);

        $links = $response->json('links');
        $meta = $response->json('meta');

        $this->assertStringContainsString('q=x', $links['first']);
        $this->assertStringContainsString('categoria_id=' . $cat->id, $links['first']);
        
        $this->assertStringContainsString('q=x', $meta['links'][0]['url'] ?? $meta['links'][1]['url']);
    }

    public function test_orden_precio()
    {
        Producto::factory()->create(['precioUnitario' => 100]);
        Producto::factory()->create(['precioUnitario' => 50]);
        Producto::factory()->create(['precioUnitario' => 200]);

        // Asc
        $response = $this->getJson('/ed/producto/listar?orden=precio&dir=asc');
        $data = $response->json('data');
        $this->assertEquals(50, $data[0]['precio']);
        $this->assertEquals(100, $data[1]['precio']);
        $this->assertEquals(200, $data[2]['precio']);

        // Desc
        $response = $this->getJson('/ed/producto/listar?orden=precio&dir=desc');
        $data = $response->json('data');
        $this->assertEquals(200, $data[0]['precio']);
        $this->assertEquals(100, $data[1]['precio']);
        $this->assertEquals(50, $data[2]['precio']);
    }

    public function test_n_plus_1_queries()
    {
        $cat = Categoria::factory()->create();
        
        for ($i = 0; $i < 50; $i++) {
            $p = Producto::factory()->create(['categoria_id' => $cat->id]);
            Imagen::create([
                'producto_id' => $p->id,
                'imagen_url' => 'http://example.com/img.jpg',
                'imagen_public_id' => 'img_xyz'
            ]);
        }

        DB::enableQueryLog();
        $this->getJson('/ed/producto/listar?per_page=8');
        $queries8 = count(DB::getQueryLog());
        DB::flushQueryLog();

        $this->getJson('/ed/producto/listar?per_page=40');
        $queries40 = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals($queries8, $queries40);
    }
}
