<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class StopedIfNotAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        // Verifica si el usuario está autenticado
        $user = Auth::guard('sanctum')->user();

        // Verifica si el usuario es una instancia de AdminUser
        if ($user instanceof \App\Models\AdminUser) {
            return $next($request); // Permite el acceso si es un AdminUser
        }

        // Devuelve un error si no es un AdminUser
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
