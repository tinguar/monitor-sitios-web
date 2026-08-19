<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\SiteMonitor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sites:digest {--force : Envía aunque el resumen esté desactivado}')]
#[Description('Envía el resumen de condiciones por WhatsApp (cada 6 horas)')]
class DigestSitesCommand extends Command
{
    public function handle(SiteMonitor $monitor): int
    {
        if (! $this->option('force') && ! Setting::digestEnabled()) {
            $this->comment('Resumen cada 6 horas desactivado.');

            return self::SUCCESS;
        }

        $monitor->runChecks();
        $digest = $monitor->sendDigests();

        $this->info(sprintf(
            '[%s] Resumen enviado a %d sitios (%d números)',
            now()->toIso8601String(),
            $digest['sent'],
            $digest['recipients']
        ));

        return self::SUCCESS;
    }
}
