<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envía plantillas por WhatsApp Cloud API.
 * No recibe webhooks: esos viven en POS (mismo WABA).
 */
class WhatsAppCloudClient
{
    public function isConfigured(): bool
    {
        if (! (bool) config('services.whatsapp.enabled')) {
            return false;
        }

        $token = (string) config('services.whatsapp.access_token');
        $phoneId = (string) config('services.whatsapp.phone_number_id');

        return $token !== '' && $phoneId !== '';
    }

    /**
     * @param  list<string>  $bodyParams
     * @return array{wamid: ?string, response: array<string, mixed>}
     */
    public function sendTemplate(
        string $toDigits,
        string $templateName,
        string $languageCode,
        array $bodyParams = [],
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toDigits,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];

        if ($bodyParams !== []) {
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    static fn (string $text): array => [
                        'type' => 'text',
                        'text' => mb_substr($text !== '' ? $text : '-', 0, 1024),
                    ],
                    $bodyParams,
                ),
            ]];
        }

        $response = $this->postMessages($payload);
        $json = $response->json() ?? [];
        if (! $response->successful()) {
            $message = is_array($json)
                ? (string) data_get($json, 'error.message', $response->body())
                : $response->body();
            throw new RuntimeException($message !== '' ? $message : 'WhatsApp rechazó el envío');
        }

        $wamid = data_get($json, 'messages.0.id');

        return [
            'wamid' => is_string($wamid) ? $wamid : null,
            'response' => is_array($json) ? $json : ['raw' => $response->body()],
        ];
    }

    /** @param  array<string, mixed>  $payload */
    private function postMessages(array $payload): Response
    {
        $version = (string) config('services.whatsapp.api_version', 'v25.0');
        $phoneId = (string) config('services.whatsapp.phone_number_id');
        $token = (string) config('services.whatsapp.access_token');

        return Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", $payload);
    }
}
