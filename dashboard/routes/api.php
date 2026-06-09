<?php

use App\Http\Controllers\Api\ExtensionAuthController;
use App\Http\Middleware\ValidateExtensionToken;
use Illuminate\Support\Facades\Route;

// ─── Extension Config (Remote Config untuk ketahanan DOM) ────────────────────
Route::get('/extension/config', [\App\Http\Controllers\Api\ExtensionConfigController::class, 'index']);

// ─── Extension Auth (tanpa token, untuk login awal) ──────────────────────────
Route::middleware('throttle:5,1')->post('/extension/login', [ExtensionAuthController::class, 'login']);

// ─── Extension Protected Routes (butuh api_token) ────────────────────────────
Route::middleware(['throttle:60,1', ValidateExtensionToken::class])->group(function () {
    Route::get('/extension/status', [ExtensionAuthController::class, 'status']);
});

// ─── Internal Webhook (Untuk FastAPI) ──────────────────────────────────────────
Route::middleware('throttle:120,1')->post('/internal/broadcast', [\App\Http\Controllers\Api\InternalWebhookController::class, 'broadcastMessage']);
