<?php
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

// Endpoint untuk menerima data dari Extension [cite: 15, 34]
Route::post('/analyze', [MessageController::class, 'store']);
Route::middleware('auth:sanctum')->post('/analyze', [MessageController::class, 'store']);