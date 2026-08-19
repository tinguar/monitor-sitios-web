<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthAndWhatsAppTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_sites_require_auth(): void
    {
        $this->getJson('/api/sites')
            ->assertStatus(401)
            ->assertJson(['error' => 'Inicia sesión para continuar']);
    }

    public function test_login_and_me(): void
    {
        User::factory()->create([
            'email' => 'administracion@tinguar.com',
            'password' => 'SecurePass!2345',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'administracion@tinguar.com',
            'password' => 'SecurePass!2345',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user' => ['email']]);
        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', 'administracion@tinguar.com');
    }

    public function test_rejects_bad_password(): void
    {
        User::factory()->create([
            'email' => 'administracion@tinguar.com',
            'password' => 'SecurePass!2345',
        ]);

        $this->postJson('/api/login', [
            'email' => 'administracion@tinguar.com',
            'password' => 'wrong-password-1',
        ])->assertStatus(401);
    }

    public function test_sends_whatsapp_template_test(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.api_version' => 'v25.0',
            'services.whatsapp.template_down' => 'monitor_sitio_caido',
            'services.whatsapp.template_language' => 'es',
        ]);

        $this->actingAsMonitor();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        $this->postJson('/api/whatsapp/test', [
            'template' => 'down',
            'country_code' => '593',
            'phone' => '0992889078',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('to', '593992889078');

        Http::assertSent(function ($request) {
            return $request['to'] === '593992889078'
                && $request['template']['name'] === 'monitor_sitio_caido';
        });
    }
}
