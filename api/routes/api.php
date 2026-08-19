<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\WhatsAppTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'name' => 'Monitor de sitios',
        'status' => 'ok',
    ];
});

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');

Route::middleware(\App\Http\Middleware\AuthenticateApiToken::class)->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/sites', [SiteController::class, 'index']);
    Route::post('/sites', [SiteController::class, 'store']);
    Route::get('/sites/{site}/checks', [SiteController::class, 'checks']);
    Route::delete('/sites/{site}', [SiteController::class, 'destroy']);

    Route::post('/checks/run', [CheckController::class, 'run']);

    Route::get('/settings', [SettingController::class, 'show']);
    Route::put('/settings', [SettingController::class, 'update']);

    Route::post('/whatsapp/test', [WhatsAppTestController::class, 'send'])
        ->middleware('throttle:whatsapp-test');
});
