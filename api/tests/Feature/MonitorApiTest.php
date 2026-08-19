<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Site;
use App\Services\SiteChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MonitorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(SiteChecker::class, function ($mock) {
            $mock->shouldReceive('check')->andReturn([
                'ok' => true,
                'http_code' => 200,
                'response_ms' => 120,
                'error' => null,
                'ssl_days_left' => 80,
            ]);
        });

        $this->actingAsMonitor();
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api')->assertOk()->assertJson([
            'name' => 'Monitor de sitios',
            'status' => 'ok',
        ]);
    }

    public function test_lists_sites(): void
    {
        $this->getJson('/api/sites')
            ->assertOk()
            ->assertJsonStructure([
                'sites',
                'slow_threshold_ms',
                'fail_threshold',
                'check_interval_minutes',
                'whatsapp_configured',
            ])
            ->assertJsonPath('sites', []);
    }

    public function test_creates_and_deletes_a_site(): void
    {
        $created = $this->postJson('/api/sites', [
            'name' => 'Example',
            'url' => 'https://example.com',
            'country_code' => '593',
            'phone' => '0991234567',
        ]);

        $created->assertCreated()
            ->assertJsonPath('name', 'Example')
            ->assertJsonPath('url', 'https://example.com')
            ->assertJsonPath('whatsapp_e164', '593991234567')
            ->assertJsonPath('status', 'up');

        $id = $created->json('id');

        $this->getJson('/api/sites')
            ->assertOk()
            ->assertJsonPath('sites.0.name', 'Example');

        $this->deleteJson("/api/sites/{$id}")
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('sites', ['id' => $id]);
    }

    public function test_rejects_empty_site(): void
    {
        $this->postJson('/api/sites', ['name' => '', 'url' => ''])
            ->assertStatus(422)
            ->assertJson(['error' => 'Nombre y URL son obligatorios']);
    }

    public function test_rejects_missing_whatsapp(): void
    {
        $this->postJson('/api/sites', [
            'name' => 'Example',
            'url' => 'https://example.com',
        ])->assertStatus(422)
            ->assertJson(['error' => 'Código de país y número de WhatsApp son obligatorios']);
    }

    public function test_rejects_duplicate_url(): void
    {
        Site::query()->create([
            'name' => 'Uno',
            'url' => 'https://example.com',
            'created_at' => gmdate('c'),
        ]);

        $this->postJson('/api/sites', [
            'name' => 'Dos',
            'url' => 'https://example.com',
            'country_code' => '593',
            'phone' => '0991234567',
        ])->assertStatus(409)->assertJson(['error' => 'Ese sitio ya está registrado']);
    }

    public function test_unknown_site_returns_not_found(): void
    {
        $this->deleteJson('/api/sites/999')
            ->assertStatus(404)
            ->assertJson(['error' => 'Sitio no encontrado']);
    }

    public function test_updates_check_interval(): void
    {
        $this->putJson('/api/settings', [
            'check_interval_minutes' => '5',
        ])->assertOk()->assertJsonPath('check_interval_minutes', 5);

        $this->getJson('/api/sites')
            ->assertOk()
            ->assertJsonPath('check_interval_minutes', 5);
    }

    public function test_sends_whatsapp_down_template(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.api_version' => 'v25.0',
            'services.whatsapp.template_down' => 'monitor_sitio_caido',
            'services.whatsapp.template_language' => 'es',
        ]);

        $site = Site::query()->create([
            'name' => 'Tinguar',
            'url' => 'https://example.com',
            'country_code' => '593',
            'phone' => '0991234567',
            'whatsapp_e164' => '593991234567',
            'last_http_code' => 500,
            'last_response_ms' => 1800,
            'last_error' => 'HTTP 500',
            'created_at' => gmdate('c'),
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        $this->assertTrue(app(\App\Services\WhatsAppNotifier::class)->notifySite($site, 'down'));

        Http::assertSent(function ($request) {
            $params = $request['template']['components'][0]['parameters'] ?? [];

            return $request->url() === 'https://graph.facebook.com/v25.0/123456/messages'
                && $request['to'] === '593991234567'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'monitor_sitio_caido'
                && ($params[2]['text'] ?? '') === 'El sitio tiene un error interno y no puede mostrar la página.'
                && ($params[3]['text'] ?? '') === 'HTTP 500'
                && ($params[4]['text'] ?? '') === '1.8 segundos';
        });
    }

    public function test_sends_digest_once_per_site(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.api_version' => 'v25.0',
            'services.whatsapp.template_digest' => 'monitor_resumen',
            'services.whatsapp.template_language' => 'es',
        ]);

        Site::query()->create([
            'name' => 'Tinguar',
            'url' => 'https://tinguar.com',
            'whatsapp_e164' => '593991234567',
            'last_status' => 'up',
            'created_at' => gmdate('c'),
        ]);
        Site::query()->create([
            'name' => 'Dos',
            'url' => 'https://two.example',
            'whatsapp_e164' => '593991234567',
            'last_status' => 'down',
            'created_at' => gmdate('c'),
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.digest']],
            ], 200),
        ]);

        $result = app(\App\Services\SiteMonitor::class)->sendDigests();
        $this->assertSame(2, $result['sent']);

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            $params = $request['template']['components'][0]['parameters'] ?? [];

            return $request['template']['name'] === 'monitor_resumen'
                && $request['to'] === '593991234567'
                && ($params[0]['text'] ?? null) === 'Tinguar'
                && str_contains((string) ($params[1]['text'] ?? ''), 'funcional al 100')
                && ($params[2]['text'] ?? null) === 'https://tinguar.com';
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
