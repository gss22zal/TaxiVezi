<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Проверка роли пользователя
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $userRole = Auth::user()->role;

        // Разрешённые роли для каждого роута
        $allowedRoles = [
            'passenger' => ['passenger'],
            'driver' => ['driver'],
            'dispatcher' => ['dispatcher', 'admin', 'super_admin'],
            'admin' => ['admin', 'super_admin'],
        ];

        $allowed = $allowedRoles[$role] ?? [];

        if (!in_array($userRole, $allowed)) {
            // Редирект на страницу в соответствии с ролью пользователя
            return match($userRole) {
                'passenger' => redirect('/passenger'),
                'driver' => redirect('/driver'),
                'dispatcher' => redirect('/dispatcher'),
                'admin', 'super_admin' => redirect('/admin'),
                default => redirect('/login'),
            };
        }

        return $next($request);
    }
}
