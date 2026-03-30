<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticated
{
    /**
     * Редирект авторизованных пользователей на страницу по роли
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ($request->path() === 'login' || $request->path() === 'register')) {
            $roleName = Auth::user()->role;
            
            return match($roleName) {
                'admin', 'super_admin' => redirect()->route('admin.dashboard'),
                'dispatcher' => redirect()->route('dispatcher'),
                'driver' => redirect()->route('driver'),
                'passenger' => redirect()->route('passenger'),
                default => redirect('/'),
            };
        }

        return $next($request);
    }
}
