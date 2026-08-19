<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\WhatsAppCloudClient;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        private WhatsAppNotifier $notifier,
        private WhatsAppCloudClient $cloud,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json($this->publicSettings());
    }

    public function update(Request $request): JsonResponse
    {
        $allowed = [
            'fail_threshold',
            'slow_threshold_ms',
            'check_interval_minutes',
        ];

        foreach ($allowed as $key) {
            if (! $request->exists($key)) {
                continue;
            }

            Setting::setValue($key, trim((string) $request->input($key)));
        }

        if ($request->exists('digest_enabled')) {
            $enabled = filter_var($request->input('digest_enabled'), FILTER_VALIDATE_BOOL);
            Setting::setValue('digest_enabled', $enabled ? '1' : '0');
        }

        return response()->json($this->publicSettings());
    }

    private function publicSettings(): array
    {
        return [
            'whatsapp_configured' => $this->notifier->configured(),
            'whatsapp_cloud_ready' => $this->cloud->isConfigured(),
            'fail_threshold' => Setting::failThreshold(),
            'slow_threshold_ms' => Setting::slowThresholdMs(),
            'check_interval_minutes' => Setting::checkIntervalMinutes(),
            'digest_enabled' => Setting::digestEnabled(),
        ];
    }
}
