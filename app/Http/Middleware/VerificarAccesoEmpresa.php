<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class VerificarAccesoEmpresa
{
    public function handle($request, Closure $next)
    {
        $company = $request->route('company');

        // Si no hay parámetro, solo continúa
        if (!$company) {
            return $next($request);
        }

        if (
            Auth::check() &&
            Auth::user()->company_id !== $company->id &&
            !Auth::user()->hasRole('super-admin')
        ) {
            return response()->json(['error' => 'No tienes acceso a esta empresa.'], 403);
        }

        return $next($request);
    }
}

