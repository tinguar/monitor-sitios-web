<?php

namespace App\Console\Commands;

use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:ensure')]
#[Description('Crea o actualiza el usuario administrador del panel')]
class EnsureAdminCommand extends Command
{
    public function handle(): int
    {
        if ($this->laravel->configurationIsCached()) {
            $this->callSilently('config:clear');
        }

        $envFile = $this->laravel->environmentFilePath();
        if (is_file($envFile)) {
            Dotenv::createImmutable(
                $this->laravel->environmentPath(),
                $this->laravel->environmentFile()
            )->safeLoad();
        }

        $email = strtolower(trim((string) env('ADMIN_EMAIL', config('monitor.admin_email'))));
        $password = (string) env('ADMIN_PASSWORD', config('monitor.admin_password'));

        if ($email === '' || $password === '') {
            $this->error('En el .env del servidor (junto a artisan) deben existir:');
            $this->line('ADMIN_EMAIL=administracion@tinguar.com');
            $this->line('ADMIN_PASSWORD="tu-clave"');
            $this->line('Luego: php artisan config:clear && php artisan admin:ensure && php artisan config:cache');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('ADMIN_EMAIL no es un correo válido');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('ADMIN_PASSWORD debe tener al menos 12 caracteres');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administración',
                'password' => $password,
            ],
        );

        $this->info('Administrador listo: '.$email);

        return self::SUCCESS;
    }
}
