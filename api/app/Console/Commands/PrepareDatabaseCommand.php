<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PDO;
use Throwable;

#[Signature('monitor:prepare')]
#[Description('Crea la base de datos MySQL si hace falta y corre las migraciones')]
class PrepareDatabaseCommand extends Command
{
    public function handle(): int
    {
        $connection = (string) config('database.default');

        if ($connection === 'mysql') {
            try {
                $this->ensureMysqlDatabase();
            } catch (Throwable $e) {
                $this->error('No se pudo crear/conectar a MySQL. ¿MAMP está encendido?');
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('admin:ensure');

        return self::SUCCESS;
    }

    private function ensureMysqlDatabase(): void
    {
        $config = config('database.connections.mysql');
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']);
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $name = str_replace('`', '``', (string) $config['database']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
}
