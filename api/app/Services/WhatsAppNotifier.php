<?php

namespace App\Services;

use App\Models\Site;
use Throwable;

class WhatsAppNotifier
{
    public function __construct(
        private WhatsAppCloudClient $cloud,
    ) {}

    public function configured(): bool
    {
        return $this->cloud->isConfigured();
    }

    public function e164For(Site $site): ?string
    {
        $stored = preg_replace('/\D+/', '', (string) $site->whatsapp_e164) ?? '';
        if ($stored !== '') {
            return $stored;
        }

        $normalized = $this->normalizePhone(
            (string) $site->country_code,
            (string) $site->phone,
        );

        return $normalized !== '' ? $normalized : null;
    }

    public function normalizePhone(string $countryCode, string $national): string
    {
        $cc = preg_replace('/\D+/', '', $countryCode) ?? '';
        $digits = preg_replace('/\D+/', '', $national) ?? '';
        if ($cc === '' || $digits === '') {
            return '';
        }

        if (str_starts_with($digits, $cc) && strlen($digits) >= strlen($cc) + 7) {
            return $digits;
        }

        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return '';
        }

        return $cc.$digits;
    }

    public function notifySite(Site $site, string $event): bool
    {
        $to = $this->e164For($site);
        if (! $to) {
            return false;
        }

        $name = (string) $site->name;
        $url = (string) $site->url;
        $tiempo = CheckMessage::duration($site->last_response_ms !== null ? (int) $site->last_response_ms : null);
        $detalle = CheckMessage::explain(
            $site->last_http_code !== null ? (int) $site->last_http_code : null,
            $site->last_error,
        );
        $tecnico = CheckMessage::technical(
            $site->last_http_code !== null ? (int) $site->last_http_code : null,
            $site->last_error,
        );

        if ($event === 'down') {
            return $this->sendNamed(
                $to,
                (string) config('services.whatsapp.template_down', 'monitor_sitio_caido'),
                [$name, $url, $detalle, $tecnico, $tiempo],
            );
        }

        return $this->sendNamed(
            $to,
            (string) config('services.whatsapp.template_up', 'monitor_sitio_activo'),
            [$name, $url, CheckMessage::okResponse(), $tiempo],
        );
    }

    public function sendTest(string $countryCode, string $national, string $kind): array
    {
        $to = $this->normalizePhone($countryCode, $national);
        if ($to === '') {
            throw new \InvalidArgumentException('Código de país y número son obligatorios');
        }

        if (! $this->cloud->isConfigured()) {
            throw new \RuntimeException('WhatsApp no está configurado en el servidor');
        }

        [$template, $params] = match ($kind) {
            'down' => [
                (string) config('services.whatsapp.template_down', 'monitor_sitio_caido'),
                [
                    'Sitio de prueba',
                    'https://tinguar.com',
                    CheckMessage::fromHttp(500),
                    'HTTP 500',
                    '1.8 segundos',
                ],
            ],
            'up' => [
                (string) config('services.whatsapp.template_up', 'monitor_sitio_activo'),
                [
                    'Sitio de prueba',
                    'https://tinguar.com',
                    CheckMessage::okResponse(),
                    '0.4 segundos',
                ],
            ],
            'digest' => [
                (string) config('services.whatsapp.template_digest', 'monitor_resumen'),
                [
                    'Tinguar',
                    CheckMessage::digestPhrase('up'),
                    'https://tinguar.com',
                ],
            ],
            default => throw new \InvalidArgumentException('Plantilla no válida'),
        };

        return $this->cloud->sendTemplate(
            $to,
            $template,
            (string) config('services.whatsapp.template_language', 'es'),
            $params,
        );
    }

    /** @param  array<string, mixed>  $presented */
    public function notifyDigestSite(Site $site, array $presented): bool
    {
        $to = $this->e164For($site);
        if (! $to) {
            return false;
        }

        $name = (string) ($presented['name'] ?? $site->name);
        $url = (string) ($presented['url'] ?? $site->url);
        $phrase = CheckMessage::digestPhrase((string) ($presented['status'] ?? 'unknown'));

        return $this->sendNamed(
            $to,
            (string) config('services.whatsapp.template_digest', 'monitor_resumen'),
            [$name, $phrase, $url],
        );
    }

    /** @param  list<string>  $bodyParams */
    private function sendNamed(string $to, string $template, array $bodyParams): bool
    {
        if (! $this->cloud->isConfigured() || $to === '' || $template === '') {
            return false;
        }

        try {
            $this->cloud->sendTemplate(
                $to,
                $template,
                (string) config('services.whatsapp.template_language', 'es'),
                $bodyParams,
            );

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
