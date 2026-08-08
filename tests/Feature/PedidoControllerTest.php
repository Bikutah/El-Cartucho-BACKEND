<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PedidoControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKeyPem;
    private string $certPem;
    private string $kid = 'test-kid-pedido';
    private string $projectId = 'el-cartucho-test-project';
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mercadopago.access_token', 'test_access_token');
        Config::set('mercadopago.front_url', 'http://localhost:3000');
        Config::set('mercadopago.notification_url', 'http://localhost/webhook');
        Config::set('mercadopago.expiration_hours', 72);

        $openSslConfig = [
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        if (file_exists('C:\lenguajes\php\extras\ssl\openssl.cnf')) {
            $openSslConfig['config'] = 'C:\lenguajes\php\extras\ssl\openssl.cnf';
        }

        $dn = ["countryName" => "AR", "organizationName" => "Test", "commonName" => "Test"];
        $res = openssl_pkey_new($openSslConfig);
        openssl_pkey_export($res, $keyOut, null, $openSslConfig);
        $this->privateKeyPem = $keyOut;

        $csr = openssl_csr_new($dn, $res, $openSslConfig);
        $cert = openssl_csr_sign($csr, null, $res, 365, $openSslConfig);
        openssl_x509_export($cert, $certOut);
        $this->certPem = $certOut;

        $this->user = User::factory()->create(['firebase_uid' => 'test-pedido-uid']);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([
                $this->kid => $this->certPem,
            ], 200, ['Cache-Control' => 'max-age=3600']),
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'init_point' => 'https://mercadopago.com/checkout/pay',
                'id'         => 'pref_12345',
            ], 200),
        ]);
    }

    private function tokenHeader(): array
    {
        $payload = [
            'iss'   => "https://securetoken.google.com/{$this->projectId}",
            'aud'   => $this->projectId,
            'sub'   => $this->user->firebase_uid,
            'email' => $this->user->email,
            'name'  => $this->user->name,
            'iat'   => time() - 10,
            'exp'   => time() + 3600,
        ];
        $token = JWT::encode($payload, $this->privateKeyPem, 'RS256', $this->kid);
        return ['Authorization' => "Bearer {$token}"];
    }

    /** @test */
    public function creating_order_with_quantity_exceeding_stock_fails_with_409()
    {
        $producto = Producto::factory()->create(['stock' => 5, 'precioUnitario' => 100.0]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 6, // Excede stock de 5
                    ]
                ]
            ]);

        $response->assertStatus(409);
        $response->assertJsonStructure(['error', 'message']);
        $this->assertEquals(5, $producto->fresh()->stock); // El stock no se modifica
    }

    /** @test */
    public function concurrent_orders_on_last_item_one_succeeds_other_fails()
    {
        $producto = Producto::factory()->create(['stock' => 1, 'precioUnitario' => 100.0]);

        // Primer pedido por el único elemento
        $response1 = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response1->assertStatus(201);
        $this->assertEquals(0, $producto->fresh()->stock); // Queda en 0

        // Segundo pedido concurrente (ya no hay stock)
        $response2 = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response2->assertStatus(409); // Falla con 409
    }

    /** @test */
    public function order_creation_with_web_session_but_no_firebase_token_returns_401()
    {
        $producto = Producto::factory()->create(['stock' => 5, 'precioUnitario' => 100.0]);

        // Iniciar sesión en Laravel (sesión web / actingAs) pero SIN enviar header Authorization Bearer
        $response = $this->actingAs($this->user)
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Token de autorización ausente.']);
    }
}
