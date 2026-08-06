<?php

namespace Tests\Feature\Hito2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\User;
use App\Http\Resources\ProductoResource;
use App\Http\Resources\ProductoDetalleResource;

class ProductoSinCategoriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_se_puede_crear_un_producto_sin_categorias()
    {
        $response = $this->post(route('productos.store'), [
            'nombre' => 'Producto Sin Categoria',
            'descripcion' => 'Descripcion test',
            'precioUnitario' => 1500,
            'stock' => 10,
        ]);

        $response->assertRedirect(route('productos.index'));

        $producto = Producto::where('nombre', 'Producto Sin Categoria')->first();
        $this->assertNotNull($producto);
        $this->assertCount(0, $producto->categorias);
        $this->assertNull($producto->categoria_id);
    }

    public function test_se_puede_crear_un_producto_sin_imagenes()
    {
        $response = $this->post(route('productos.store'), [
            'nombre' => 'Producto Sin Imagenes',
            'descripcion' => 'Descripcion test',
            'precioUnitario' => 2000,
            'stock' => 5,
        ]);

        $response->assertRedirect(route('productos.index'));

        $producto = Producto::where('nombre', 'Producto Sin Imagenes')->first();
        $this->assertNotNull($producto);
        $this->assertCount(0, $producto->imagenes);
    }

    public function test_primera_imagen_devuelve_la_url_de_stock_cuando_no_hay_imagenes()
    {
        $producto = Producto::factory()->create();
        $producto->imagenes()->delete();

        $primeraImagen = $producto->fresh()->primera_imagen;

        $this->assertNotNull($primeraImagen);
        $this->assertEquals(config('app.stock_image_url', '/placeholder.svg'), $primeraImagen->imagen_url);
    }

    public function test_el_resource_devuelve_categorias_array_vacio_nunca_null()
    {
        $producto = Producto::factory()->create(['categoria_id' => null]);
        $producto->categorias()->detach();

        $resourceArray = (new ProductoResource($producto->load(['categorias', 'subcategorias', 'imagenes'])))->toArray(request());
        $detalleArray = (new ProductoDetalleResource($producto->load(['categorias', 'subcategorias', 'imagenes'])))->toArray(request());

        $this->assertIsArray($resourceArray['categorias']);
        $this->assertEmpty($resourceArray['categorias']);
        $this->assertNotNull($resourceArray['categorias']);

        $this->assertIsArray($detalleArray['categorias']);
        $this->assertEmpty($detalleArray['categorias']);
        $this->assertNotNull($detalleArray['categorias']);
    }
}
