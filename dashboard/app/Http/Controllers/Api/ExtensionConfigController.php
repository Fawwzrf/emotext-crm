<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ExtensionConfigController extends Controller
{
    /**
     * Menyediakan konfigurasi remote (CSS selectors dll) untuk Ekstensi Chrome.
     * 
     * Endpoint ini dibuat publik tanpa proteksi auth agar ekstensi bisa segera memuat 
     * DOM Selector pada saat WhatsApp Web pertama kali dibuka, tanpa peduli user sudah login atau belum.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'version' => '1.0',
            'selectors' => config('extension.selectors', [])
        ]);
    }
}
