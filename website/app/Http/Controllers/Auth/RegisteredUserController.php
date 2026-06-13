<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $plain_token = User::generateApiToken();
        
        $user = User::create([
            'name'                => $request->name,
            'company_name'        => $request->company_name,
            'email'               => $request->email,
            'password'            => Hash::make($request->password),
            // Trial 7 hari mulai sekarang
            'trial_started_at'    => now(),
            'trial_ends_at'       => now()->addDays(7),
            'subscription_status' => 'trial',
            // Token unik untuk autentikasi extension
            'api_token'           => hash('sha256', $plain_token),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
