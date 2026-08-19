<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\SiteMonitor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sites:check {--force : Ignora el intervalo configurado}')]
#[Description('Chequea todos los sitios y envía avisos de WhatsApp')]
class CheckSitesCommand extends Command
{
    public function handle(SiteMonitor $monitor): int
    {
        if (! $this->option('force') && ! Setting::isCheckDue()) {
            $this->comment(sprintf(
                'Aún no toca chequear. Intervalo: %d min.',
                Setting::checkIntervalMinutes()
            ));

            return self::SUCCESS;
        }

        $result = $monitor->runChecks();
        $sites = $result['sites'] ?? [];
        $down = array_filter($sites, fn (array $site) => $site['status'] === 'down');

        $this->info(sprintf(
            '[%s] Chequeados %d sitios (%d desconectados)',
            gmdate('c'),
            count($sites),
            count($down)
        ));

        foreach ($sites as $site) {
            $this->line(sprintf(' - %s [%s] %s', $site['name'], $site['status'], $site['url']));
        }

        return self::SUCCESS;
    }
}
