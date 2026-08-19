<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Site;
use App\Models\SiteCheck;
use RuntimeException;

class SiteMonitor
{
    public function __construct(
        private SiteChecker $checker,
        private WhatsAppNotifier $notifier,
    ) {}

    public function runChecks(): array
    {
        $threshold = Setting::failThreshold();
        $results = [];

        foreach (Site::query()->orderBy('name')->get() as $site) {
            $check = $this->checker->check($site->url, (int) $site->timeout_seconds);
            $updated = $this->recordCheck($site, $check, $threshold);

            if (in_array($updated['event'], ['down', 'up'], true)) {
                $this->notifier->notifySite($site->fresh(), $updated['event']);
            }

            $results[] = $updated;
        }

        Setting::markCheckedNow();

        return [
            'checked_at' => gmdate('c'),
            'sites' => $results,
        ];
    }

    public function checkNewSite(Site $site): array
    {
        $threshold = Setting::failThreshold();
        $check = $this->checker->check($site->url, (int) $site->timeout_seconds);
        $updated = $this->recordCheck($site, $check, $threshold);

        if ($updated['event']) {
            $this->notifier->notifySite($site->fresh(), $updated['event']);
        }

        return $updated;
    }

    public function recordCheck(Site $site, array $result, int $failThreshold): array
    {
        $site->refresh();
        if (! $site->exists) {
            throw new RuntimeException('Sitio no encontrado');
        }

        $ok = (bool) $result['ok'];
        $now = gmdate('c');
        $previousStatus = $site->last_status;
        $failures = (int) $site->consecutive_failures;

        if ($ok) {
            $failures = 0;
            $newStatus = 'up';
        } else {
            $failures++;
            $newStatus = $failures >= $failThreshold ? 'down' : $previousStatus;
            if ($newStatus === 'unknown') {
                $newStatus = $failures >= $failThreshold ? 'down' : 'unknown';
            }
        }

        $event = null;
        if ($newStatus === 'down' && $previousStatus !== 'down' && $failures >= $failThreshold) {
            $event = 'down';
        } elseif ($ok && $previousStatus === 'down') {
            $event = 'up';
        }

        SiteCheck::query()->create([
            'site_id' => $site->id,
            'ok' => $ok,
            'http_code' => $result['http_code'],
            'response_ms' => $result['response_ms'],
            'error' => $result['error'],
            'ssl_days_left' => $result['ssl_days_left'],
            'checked_at' => $now,
        ]);

        $site->fill([
            'consecutive_failures' => $failures,
            'last_status' => $newStatus,
            'last_checked_at' => $now,
            'last_response_ms' => $result['response_ms'],
            'last_http_code' => $result['http_code'],
            'last_error' => $result['error'],
            'ssl_days_left' => $result['ssl_days_left'],
        ])->save();

        $presented = $this->present($site->fresh(['latestCheck']));
        $presented['event'] = $event;
        $presented['previous_status'] = $previousStatus;

        return $presented;
    }

    public function present(Site $site): array
    {
        $site->loadMissing('latestCheck');

        $slowMs = Setting::slowThresholdMs();
        $latest = $site->latestCheck;
        $ok = (int) ($latest?->ok ? 1 : 0) === 1;
        $ms = $site->last_response_ms !== null ? (int) $site->last_response_ms : null;
        $status = $site->last_status;

        $display = 'unknown';
        $label = 'Sin datos';
        if ($status === 'down') {
            $display = 'down';
            $label = 'No se detectó actividad, sitio desconectado';
        } elseif (! $ok && $latest) {
            $display = 'retrying';
            $label = 'Sin respuesta, reintentando';
        } elseif ($ok && $ms !== null && $ms >= $slowMs) {
            $display = 'slow';
            $label = 'Sitio lento';
        } elseif ($ok || $status === 'up') {
            $display = 'up';
            $label = 'Sitio activo';
        }

        $ssl = $site->ssl_days_left !== null ? (int) $site->ssl_days_left : null;

        return [
            'id' => (int) $site->id,
            'name' => $site->name,
            'url' => $site->url,
            'country_code' => $site->country_code,
            'phone' => $site->phone,
            'whatsapp_e164' => $site->whatsapp_e164,
            'status' => $display,
            'label' => $label,
            'last_status' => $site->last_status,
            'consecutive_failures' => (int) $site->consecutive_failures,
            'last_checked_at' => $site->last_checked_at,
            'last_response_ms' => $ms,
            'last_http_code' => $site->last_http_code !== null ? (int) $site->last_http_code : null,
            'last_error' => $site->last_error,
            'ssl_days_left' => $ssl,
            'ssl_expiring' => $ssl !== null && $ssl <= 14,
            'event' => null,
        ];
    }

    /**
     * @return array{sent: int, recipients: int}
     */
    public function sendDigests(): array
    {
        $sent = 0;
        $recipients = [];

        foreach (Site::query()->orderBy('name')->get() as $site) {
            $presented = $this->present($site);
            $to = $presented['whatsapp_e164'] ?: $this->notifier->e164For($site);
            if (! $to) {
                continue;
            }

            if ($this->notifier->notifyDigestSite($site, $presented)) {
                $sent++;
                $recipients[$to] = true;
            }
        }

        return [
            'sent' => $sent,
            'recipients' => count($recipients),
        ];
    }
}
