<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ExtensionAuthController extends Controller
{
    /**
     * POST /api/extension/login
     * Digunakan oleh Chrome Extension untuk mendapatkan api_token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        return response()->json([
            'api_token'           => $user->api_token,
            'subscription_status' => $user->subscription_status,
            'is_active'           => $user->isActive(),
            'trial_days_left'     => $user->trialDaysLeft(),
            'trial_ends_at'       => $user->trial_ends_at?->toIso8601String(),
            'company_name'        => $user->company_name,
            'name'                => $user->name,
            'email'               => $user->email,
        ]);
    }

    /**
     * GET /api/extension/status
     * Digunakan oleh extension untuk cek status langganan terkini.
     * Dilindungi oleh middleware custom 'extension.token'.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user_from_token; // diset oleh middleware

        return response()->json([
            'subscription_status' => $user->subscription_status,
            'is_active'           => $user->isActive(),
            'trial_days_left'     => $user->trialDaysLeft(),
            'trial_ends_at'       => $user->trial_ends_at?->toIso8601String(),
        ]);
    }
}
