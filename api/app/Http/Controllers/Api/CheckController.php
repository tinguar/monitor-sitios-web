<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteMonitor;
use Illuminate\Http\JsonResponse;

class CheckController extends Controller
{
    public function run(SiteMonitor $monitor): JsonResponse
    {
        $payload = $monitor->runChecks();
        $digest = $monitor->maybeSendScheduledDigests();
        if ($digest !== null) {
            $payload['digest'] = $digest;
        }

        return response()->json($payload);
    }
}
