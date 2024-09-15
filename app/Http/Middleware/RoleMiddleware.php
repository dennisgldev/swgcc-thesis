<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        Log::info('Roles: ' . json_encode($user->roles));

        $userRole = strtolower($user->roles->first()->name ?? '');
        $normalizedRoles = array_map('strtolower', $roles);

        Log::info('User role: ' . $userRole);
        Log::info('Allowed roles: ' . json_encode($normalizedRoles));

        if (!in_array($userRole, $normalizedRoles)) {
            return redirect('/home')->with('error', 'No tiene permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
