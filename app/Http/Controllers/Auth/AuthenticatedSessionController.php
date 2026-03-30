<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Отладка
        info('Login POST - attempting authentication');
        
        $request->authenticate();

        // Проверка: не заблокирован ли пользователь
        $user = $request->user();

        if (!$user->is_active) {
            // Выходим из системы
            Auth::guard('web')->logout();

            // Проверяем роль и возвращаем соответствующее сообщение
            if ($user->role === 'driver') {
                return redirect()->route('login')->with('error', 'Ваш аккаунт заблокирован. Для разблокировки обратитесь к администратору.');
            } elseif ($user->role === 'dispatcher') {
                return redirect()->route('login')->with('error', 'Ваш аккаунт заблокирован. Для разблокировки обратитесь к администратору.');
            } elseif ($user->role === 'passenger') {
                return redirect()->route('login')->with('error', 'Ваш аккаунт заблокирован. Для разблокировки обратитесь в поддержку.');
            } else {
                return redirect()->route('login')->with('error', 'Ваш аккаунт заблокирован. Обратитесь к администратору.');
            }
        }

        $request->session()->regenerate();

        // Редирект в зависимости от роли пользователя
        $roleName = $user->role;

        return match($roleName) {
            'admin', 'super_admin' => redirect()->route('admin.dashboard'),
            'dispatcher' => redirect()->route('dispatcher'),
            'driver' => redirect()->route('driver'),
            'passenger' => redirect()->route('passenger'),
            default => redirect('/'),
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
