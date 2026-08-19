<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Site;
use App\Services\SiteMonitor;
use App\Services\WhatsAppNotifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        private SiteMonitor $monitor,
        private WhatsAppNotifier $notifier,
    ) {}

    public function index(): JsonResponse
    {
        $sites = Site::query()
            ->with('latestCheck')
            ->orderBy('name')
            ->get()
            ->map(fn (Site $site) => $this->monitor->present($site))
            ->values();

        return response()->json([
            'sites' => $sites,
            'slow_threshold_ms' => Setting::slowThresholdMs(),
            'fail_threshold' => Setting::failThreshold(),
            'check_interval_minutes' => Setting::checkIntervalMinutes(),
            'whatsapp_configured' => $this->notifier->configured(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));
        $url = trim((string) $request->input('url', ''));
        $countryCode = trim((string) $request->input('country_code', ''));
        $phone = trim((string) $request->input('phone', ''));
        $timeout = (int) $request->input('timeout_seconds', 15);

        if ($name === '' || $url === '') {
            return response()->json(['error' => 'Nombre y URL son obligatorios'], 422);
        }
        $e164 = $this->notifier->normalizePhone($countryCode, $phone);
        if ($e164 === '') {
            return response()->json(['error' => 'Código de país y número de WhatsApp son obligatorios'], 422);
        }
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return response()->json(['error' => 'URL inválida'], 422);
        }

        try {
            $site = Site::query()->create([
                'name' => $name,
                'url' => $url,
                'country_code' => preg_replace('/\D+/', '', $countryCode) ?: null,
                'phone' => preg_replace('/\D+/', '', $phone) ?: null,
                'whatsapp_e164' => $e164,
                'timeout_seconds' => max(3, min(60, $timeout)),
                'created_at' => gmdate('c'),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')) {
                return response()->json(['error' => 'Ese sitio ya está registrado'], 409);
            }

            throw $e;
        }

        return response()->json($this->monitor->checkNewSite($site), 201);
    }

    public function checks(Site $site): JsonResponse
    {
        $checks = $site->checks()
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json(['checks' => $checks]);
    }

    public function destroy(Site $site): JsonResponse
    {
        $site->delete();

        return response()->json(['deleted' => true]);
    }
}
