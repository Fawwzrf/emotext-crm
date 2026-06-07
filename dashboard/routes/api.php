<?php

use App\Http\Controllers\Api\ExtensionAuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Middleware\ValidateExtensionToken;
use Illuminate\Support\Facades\Route;

// ─── Extension Auth (tanpa token, untuk login awal) ──────────────────────────
Route::post('/extension/login', [ExtensionAuthController::class, 'login']);

// ─── Extension Protected Routes (butuh api_token) ────────────────────────────
Route::middleware(ValidateExtensionToken::class)->group(function () {
    Route::get('/extension/status', [ExtensionAuthController::class, 'status']);
});