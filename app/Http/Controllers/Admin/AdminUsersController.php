<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Driver;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminUsersController extends Controller
{
    /**
     * Проверка что пользователь - админ
     */
    private function checkAdmin()
    {
        $user = Auth::user();
        // role хранится как строка (role_id или напрямую role)
        $role = $user?->role ?? $user?->role_id ?? null;
        if (!$role || !in_array($role, ['admin', 'super_admin'])) {
            abort(403, 'Доступ запрещён');
        }
    }
    /**
     * Страница водителей
     */
    public function drivers(Request $request)
    {
        $query = Driver::with(['user:id,role_id,first_name,last_name,phone,email,is_active,created_at', 'user.role:id,name', 'car' => function($q) {
                $q->where('is_primary', true);
            }])
            ->withCount('orders');

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->whereHas('user', function($q) {
                    $q->where('is_active', true);
                });
            } elseif ($request->status === 'blocked') {
                $query->whereHas('user', function($q) {
                    $q->where('is_active', false);
                });
            }
        }

        $drivers = $query->orderByDesc('id')->paginate(15);

        return Inertia::render('Admin/Users/Drivers', [
            'drivers' => $drivers->items(),
            'pagination' => [
                'current_page' => $drivers->currentPage(),
                'last_page' => $drivers->lastPage(),
                'per_page' => $drivers->perPage(),
                'total' => $drivers->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
            ]
        ]);
    }

    /**
     * Страница пассажиров
     */
    public function passengers(Request $request)
    {
        $query = Passenger::with(['user:id,role_id,first_name,last_name,phone,email,is_active,created_at', 'user.role:id,name'])
            ->withCount('orders');

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->whereHas('user', function($q) {
                    $q->where('is_active', true);
                });
            } elseif ($request->status === 'blocked') {
                $query->whereHas('user', function($q) {
                    $q->where('is_active', false);
                });
            }
        }

        $passengers = $query->orderByDesc('id')->paginate(15);

        return Inertia::render('Admin/Users/Passengers', [
            'passengers' => $passengers->items(),
            'pagination' => [
                'current_page' => $passengers->currentPage(),
                'last_page' => $passengers->lastPage(),
                'per_page' => $passengers->perPage(),
                'total' => $passengers->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
            ]
        ]);
    }

    /**
     * Страница диспетчеров
     */
    public function dispatchers(Request $request)
    {
        $query = User::whereIn('role', ['dispatcher', 'admin', 'super_admin'])
            ->with('role:id,name,code');

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'blocked') {
                $query->where('is_active', false);
            }
        }

        $dispatchers = $query->orderByDesc('id')->paginate(15);

        return Inertia::render('Admin/Users/Dispatchers', [
            'dispatchers' => $dispatchers->items(),
            'pagination' => [
                'current_page' => $dispatchers->currentPage(),
                'last_page' => $dispatchers->lastPage(),
                'per_page' => $dispatchers->perPage(),
                'total' => $dispatchers->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
            ]
        ]);
    }

    /**
     * Статистика для дашборда
     */
    public function stats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_drivers' => Driver::count(),
            'total_passengers' => Passenger::count(),
            'active_drivers' => Driver::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->count(),
            'total_orders_today' => 0,
            'total_earnings_today' => 0,
        ];

        return response()->json($stats);
    }

    /**
     * Блокировка/разблокировка пользователя
     */
    public function toggleBlock(Request $request)
    {
        $this->checkAdmin();
        
        $request->validate([
            'user_id' => 'required|integer',
            'type' => 'required|in:driver,passenger,dispatcher'
        ]);

        $user = User::findOrFail($request->user_id);
        $newStatus = !$user->is_active;
        
        // Обновляем напрямую, чтобы избежать проблем с timestamp
        DB::table('users')
            ->where('id', $user->id)
            ->update(['is_active' => $newStatus]);

        $status = $newStatus ? 'разблокирован' : 'заблокирован';

        return back()->with('success', "Пользователь {$status}");
    }

    /**
     * Удаление пользователя
     */
    public function destroy(Request $request)
    {
        $this->checkAdmin();
        
        $request->validate([
            'user_id' => 'required|integer',
            'type' => 'required|in:driver,passenger,dispatcher'
        ]);

        $user = User::findOrFail($request->user_id);
        $userId = $user->id;

        // Удаляем связанные данные в зависимости от типа
        switch ($request->type) {
            case 'driver':
                DB::table('drivers')->where('user_id', $userId)->delete();
                break;
            case 'passenger':
                DB::table('passengers')->where('user_id', $userId)->delete();
                break;
            // Для диспетчеров/админов - только пользователь
        }

        // Удаляем пользователя напрямую
        DB::table('users')->where('id', $userId)->delete();

        return back()->with('success', 'Пользователь удалён');
    }
}
