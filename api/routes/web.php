<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Monitor de sitios',
        'status' => 'ok',
    ]);
});
