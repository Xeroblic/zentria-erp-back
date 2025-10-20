<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class VerificarActivacion
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->is_active) {
            return response()->json(['error' => 'ERROR NO TIENES TU CUENTA ACTIVADA'], 403);
        }

        return $next($request);
    }
}
