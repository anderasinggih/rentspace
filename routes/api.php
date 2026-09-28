<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShortcutController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Jalur API untuk integrasi eksternal (Shortcut iPhone, dll)
|
*/

Route::prefix('v1')->group(function () {
    Route::post('/shortcut/unit-action', [ShortcutController::class, 'handleAction']);
    Route::post('/wa/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);
    Route::get('/wa/broadcast-targets', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'getBroadcastTargets']);
});
