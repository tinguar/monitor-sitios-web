<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'Solo CLI';
    exit(1);
}

passthru(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/artisan').' sites:check',
    $code
);

exit($code);
