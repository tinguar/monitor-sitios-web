<?php

namespace App\Services;

class CheckMessage
{
    public static function describe(?int $httpCode, int $curlErrno = 0, ?string $curlError = null): string
    {
        if ($curlErrno !== 0) {
            return self::fromCurl($curlErrno, $curlError);
        }

        if ($httpCode && $httpCode > 0) {
            return self::fromHttp($httpCode);
        }

        return self::fromRawError($curlError) ?? 'No se detectó actividad, el sitio no respondió.';
    }

    public static function explain(?int $httpCode, ?string $storedError): string
    {
        if ($httpCode && $httpCode > 0) {
            return self::fromHttp($httpCode);
        }

        if (is_string($storedError) && preg_match('/HTTP\s+(\d+)/i', $storedError, $match) === 1) {
            return self::fromHttp((int) $match[1]);
        }

        return self::fromRawError($storedError) ?? 'No se detectó actividad, el sitio no respondió.';
    }

    public static function technical(?int $httpCode, ?string $storedError = null): string
    {
        if ($httpCode && $httpCode > 0) {
            return 'HTTP '.$httpCode;
        }

        if (is_string($storedError) && preg_match('/HTTP\s+(\d+)/i', $storedError, $match) === 1) {
            return 'HTTP '.$match[1];
        }

        return 'sin código HTTP';
    }

    public static function okResponse(): string
    {
        return 'El sitio volvió a responder bien.';
    }

    public static function digestPhrase(string $status): string
    {
        return match ($status) {
            'up' => 'está activo, sin novedades, funcional al 100%',
            'slow' => 'está activo, pero responde más lento de lo normal',
            'down' => 'está desconectado y necesita revisión',
            'retrying' => 'no respondió en el último chequeo y se sigue intentando',
            default => 'aún no tiene un estado claro',
        };
    }

    public static function duration(?int $ms): string
    {
        if ($ms === null) {
            return 'sin dato de tiempo';
        }

        if ($ms < 1000) {
            return 'menos de 1 segundo';
        }

        $seconds = round($ms / 1000, 1);

        return $seconds.' segundos';
    }

    public static function fromHttp(int $code): string
    {
        return match (true) {
            $code === 400 => 'El sitio rechazó la visita.',
            $code === 401 => 'El sitio pide usuario y contraseña.',
            $code === 403 => 'El acceso al sitio está bloqueado.',
            $code === 404 => 'La página no se encontró.',
            $code === 408, $code === 504 => 'El sitio tardó demasiado en responder.',
            $code === 410 => 'La página ya no existe.',
            $code === 429 => 'El sitio recibió demasiadas consultas y se protegió.',
            $code === 500 => 'El sitio tiene un error interno y no puede mostrar la página.',
            $code === 502 => 'El servidor del sitio no pudo completar la visita.',
            $code === 503 => 'El sitio está en mantenimiento o saturado.',
            $code >= 500 && $code < 600 => 'El sitio tiene un problema en el servidor.',
            $code >= 400 && $code < 500 => 'El sitio rechazó la visita.',
            $code >= 200 && $code < 400 => 'El sitio respondió bien.',
            default => 'No se detectó actividad, el sitio no respondió.',
        };
    }

    public static function fromCurl(int $errno, ?string $curlError = null): string
    {
        return match ($errno) {
            CURLE_COULDNT_RESOLVE_HOST => 'No se encontró la dirección del sitio.',
            CURLE_COULDNT_CONNECT => 'No se pudo conectar con el sitio.',
            CURLE_OPERATION_TIMEDOUT => 'El sitio tardó demasiado en responder.',
            CURLE_SSL_CONNECT_ERROR, CURLE_SSL_CERTPROBLEM, CURLE_SSL_CACERT => 'Hay un problema con el certificado de seguridad del sitio.',
            CURLE_TOO_MANY_REDIRECTS => 'El sitio redirige demasiadas veces y no termina de abrir.',
            CURLE_GOT_NOTHING, CURLE_RECV_ERROR => 'El sitio no envió respuesta.',
            CURLE_SEND_ERROR => 'No se pudo enviar la consulta al sitio.',
            CURLE_URL_MALFORMAT => 'La dirección del sitio no es válida.',
            default => self::fromRawError($curlError) ?? 'No se detectó actividad, el sitio no respondió.',
        };
    }

    public static function fromRawError(?string $error): ?string
    {
        if ($error === null || trim($error) === '') {
            return null;
        }

        $text = strtolower($error);

        if (str_contains($text, 'timed out') || str_contains($text, 'timeout')) {
            return 'El sitio tardó demasiado en responder.';
        }
        if (str_contains($text, 'could not resolve') || str_contains($text, 'not resolve')) {
            return 'No se encontró la dirección del sitio.';
        }
        if (str_contains($text, 'connection refused') || str_contains($text, 'couldn\'t connect')) {
            return 'No se pudo conectar con el sitio.';
        }
        if (str_contains($text, 'ssl') || str_contains($text, 'certificate')) {
            return 'Hay un problema con el certificado de seguridad del sitio.';
        }
        if (preg_match('/HTTP\s+(\d+)/i', $error, $match) === 1) {
            return self::fromHttp((int) $match[1]);
        }
        if (str_contains($text, 'no se detectó') || str_contains($text, 'el sitio')) {
            return $error;
        }

        return 'No se detectó actividad, el sitio no respondió.';
    }
}
