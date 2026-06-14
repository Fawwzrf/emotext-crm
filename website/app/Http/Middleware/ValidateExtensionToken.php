<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateExtensionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'API token tidak ditemukan.'], 401);
        }

        $hashed_token = hash('sha256', $token);
        $user = User::where('api_token', $hashed_token)->first();

        if (!$user) {
            return response()->json(['message' => 'API token tidak valid.'], 401);
        }

        // Cek status langganan — user expired tidak bisa pakai extension
        if (!$user->isActive()) {
            return response()->json(['message' => 'Langganan Anda telah berakhir. Silakan upgrade.'], 403);
        }

        // Attach user ke request agar bisa diakses di controller
        $request->user_from_token = $user;

        return $next($request);
    }
}
