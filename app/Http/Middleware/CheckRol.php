<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRol
{
    public function handle(Request $request, Closure $next, ...$rol)
    {
        $user = Auth::user();

    if (!$user->hasRole($rol)) {
        return response()->json(['error' => 'Acceso denegado'], 403);
    }


        return $next($request);
    }
}
