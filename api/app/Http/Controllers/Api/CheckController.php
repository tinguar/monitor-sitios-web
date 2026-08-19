<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteMonitor;
use Illuminate\Http\JsonResponse;

class CheckController extends Controller
{
    public function run(SiteMonitor $monitor): JsonResponse
    {
        return response()->json($monitor->runChecks());
    }
}
