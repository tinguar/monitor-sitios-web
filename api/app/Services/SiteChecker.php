<?php

namespace App\Services;

class SiteChecker
{
    public function check(string $url, int $timeoutSeconds = 15): array
    {
        $started = hrtime(true);
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeoutSeconds),
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_USERAGENT => 'MonitorSitios/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CERTINFO => true,
            CURLOPT_NOBODY => false,
            CURLOPT_HEADER => false,
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = $errno ? curl_error($ch) : null;
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $elapsedMs = (int) round((hrtime(true) - $started) / 1e6);
        $sslDaysLeft = $this->sslDaysLeft($ch, $url);
        curl_close($ch);

        $ok = $body !== false && $errno === 0 && $httpCode >= 200 && $httpCode < 400;

        return [
            'ok' => $ok,
            'http_code' => $httpCode ?: null,
            'response_ms' => $elapsedMs,
            'error' => $ok ? null : CheckMessage::describe($httpCode ?: null, $errno, $error),
            'ssl_days_left' => $sslDaysLeft,
        ];
    }

    private function sslDaysLeft(\CurlHandle $ch, string $url): ?int
    {
        if (! str_starts_with(strtolower($url), 'https://')) {
            return null;
        }

        $info = curl_getinfo($ch);
        $certinfo = $info['certinfo'][0] ?? null;
        $expireRaw = $certinfo['Expire date'] ?? $certinfo['expire date'] ?? null;
        if (! $expireRaw) {
            return $this->sslDaysLeftFromOpenssl($url);
        }

        $expires = strtotime($expireRaw);
        if ($expires === false) {
            return null;
        }

        return (int) floor(($expires - time()) / 86400);
    }

    private function sslDaysLeftFromOpenssl(string $url): ?int
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 443;
        if (! $host) {
            return null;
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $client = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            8,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (! $client) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);
        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        if (! $cert) {
            return null;
        }

        $parsed = openssl_x509_parse($cert);
        $expires = $parsed['validTo_time_t'] ?? null;
        if (! $expires) {
            return null;
        }

        return (int) floor(($expires - time()) / 86400);
    }
}
