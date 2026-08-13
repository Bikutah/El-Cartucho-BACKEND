<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CronLiberarVencidosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.mercadopago.cron_secret', 'super_secret_cron_token');
    }

    /** @test */
    public function request_without_cron_secret_returns_401()
    {
        $response = $this->getJson('/ed/cron/liberar-vencidos');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'No autorizado']);
    }

    /** @test */
    public function request_with_malformed_authorization_header_returns_401()
    {
        // Header presente pero sin el prefijo "Bearer "
        $response = $this->getJson('/ed/cron/liberar-vencidos', [
            'Authorization' => 'super_secret_cron_token',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'No autorizado']);
    }

    /** @test */
    public function request_with_incorrect_cron_secret_returns_401()
    {
        $response = $this->getJson('/ed/cron/liberar-vencidos', [
            'Authorization' => 'Bearer wrong_secret_token',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'No autorizado']);
    }

    /** @test */
    public function request_with_correct_cron_secret_returns_200()
    {
        $response = $this->getJson('/ed/cron/liberar-vencidos', [
            'Authorization' => 'Bearer super_secret_cron_token',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'output']);
        $response->assertJson(['message' => 'Proceso de liberación completado']);
    }
}
