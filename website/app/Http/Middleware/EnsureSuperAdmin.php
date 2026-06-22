<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->is_superadmin) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk Tim Emotext.');
        }

        return $next($request);
    }
}
