<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            abort(401, 'No autenticado.');
        }
    }


    public function handle($request, Closure $next, ...$guards)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token no enviado.'], 401);
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
            
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado.'], 401);
            }

            // Bloquear acceso a usuarios desactivados
            if (property_exists($user, 'is_active') && !$user->is_active) {
                return response()->json(['error' => 'ERROR NO TIENES TU CUENTA ACTIVADA'], 403);
            }

            Auth::setUser($user);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expirado.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token inválido.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Error con el token.'], 401);
        }

        return $next($request);
    }


}
