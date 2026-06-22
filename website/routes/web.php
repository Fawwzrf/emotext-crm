<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProductFeedbackController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/contact', [ProductFeedbackController::class, 'store'])->name('contact.store');

Route::get('/install', function () {
    return view('install');
})->name('install');

Route::get('/download-extension', function () {
    $path = 'd:/Emotext-CRM/docs/EmoText.zip';
    if (file_exists($path)) {
        return response()->download($path);
    }
    abort(404, 'File Ekstensi tidak ditemukan di server.');
})->name('download.extension');

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

// ─── Super Admin Routes (Tim Emotext) ────────────────────────────────────────
Route::prefix('superadmin')->middleware(['auth', 'superadmin'])->name('superadmin.')->group(function () {
    Route::get('/',            [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users',       [SuperAdminController::class, 'users'])->name('users');
    Route::get('/users/export',[SuperAdminController::class, 'exportUsers'])->name('users.export');
    Route::post('/users/{user}/extend-trial', [SuperAdminController::class, 'extendTrial'])->name('users.extend');
    Route::post('/users/{user}/change-status',[SuperAdminController::class, 'changeStatus'])->name('users.status');
    Route::post('/users/{user}/reset-token',  [SuperAdminController::class, 'resetToken'])->name('users.reset-token');
    Route::delete('/users/{user}',             [SuperAdminController::class, 'destroy'])->name('users.destroy');
    Route::get('/feedback',    [SuperAdminController::class, 'feedback'])->name('feedback');
    Route::get('/product-feedbacks', [SuperAdminController::class, 'productFeedbacks'])->name('product-feedbacks');
    Route::patch('/product-feedbacks/{id}/read', [SuperAdminController::class, 'markFeedbackRead'])->name('product-feedbacks.read');
    Route::get('/analytics',   [SuperAdminController::class, 'analytics'])->name('analytics');
    Route::get('/settings',    [SuperAdminController::class, 'settings'])->name('settings');
});

require __DIR__.'/auth.php';