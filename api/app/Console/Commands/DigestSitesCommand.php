<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\SiteMonitor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sites:digest {--force : Envía aunque el resumen esté desactivado o ya se haya enviado en esta franja}')]
#[Description('Envía el resumen de condiciones por WhatsApp (cada 6 horas)')]
class DigestSitesCommand extends Command
{
    public function handle(SiteMonitor $monitor): int
    {
        if (! $this->option('force') && ! Setting::digestEnabled()) {
            $this->comment('Resumen cada 6 horas desactivado.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! Setting::isDigestHour()) {
            $this->comment('Fuera de horario de resumen (00, 06, 12 y 18 America/Guayaquil).');

            return self::SUCCESS;
        }

        if (! $this->option('force') && Setting::digestSlotAlreadySent()) {
            $this->comment('El resumen de esta franja ya se envió.');

            return self::SUCCESS;
        }

        $monitor->runChecks();
        $digest = $monitor->sendDigests();
        Setting::markDigestSlotSent();

        $this->info(sprintf(
            '[%s] Resumen: %d enviados, %d fallidos, %d sin número',
            now()->toIso8601String(),
            $digest['sent'],
            $digest['failed'],
            $digest['skipped']
        ));

        foreach ($digest['errors'] as $error) {
            $this->warn(' - '.$error);
        }

        return self::SUCCESS;
    }
}
