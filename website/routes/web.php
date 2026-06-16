<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController; 
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');
Route::get('/dashboard/export', [DashboardController::class, 'exportCsv'])
    ->middleware(['auth'])
    ->name('dashboard.export');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // BUG-01 FIXED: Melindungi endpoint ini dari eksploitasi tanpa login
    Route::patch('/messages/{id}/resolve', [DashboardController::class, 'resolve'])->name('messages.resolve');
    
    // Feature RAG    // Knowledge Base Routes (RAG) - Sekarang berada di halaman dashboard utama
    Route::post('/dashboard/knowledge-base/upload', [DashboardController::class, 'uploadKb'])->name('kb.upload');
    Route::delete('/dashboard/knowledge-base/{id}', [DashboardController::class, 'deleteKb'])->name('kb.delete');
});

require __DIR__.'/auth.php';