<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_SUPER_ADMIN'))) {
            return response()->json([
                'success' => false,
                'message' => "Accès refusé. Réservé aux administrateurs."
            ], 403);
        }

        return $next($request);
    }
}
