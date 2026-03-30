<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Driver;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    /**
     * Аналитика для диспетчера
     */
    public function dispatcher(Request $request)
    {
        // Статистика за сегодня
        $today = now()->toDateString();
        
        $todayOrders = Order::whereDate('created_at', $today);
        $completedOrders = Order::where('status', 'completed');
        
        // За сегодня
        $statsToday = [
            'orders_count' => Order::whereDate('created_at', $today)->count(),
            'orders_completed' => Order::whereDate('completed_at', $today)->where('status', 'completed')->count(),
            'orders_cancelled' => Order::whereDate('created_at', $today)->where('status', 'cancelled')->count(),
            'earnings_today' => Order::whereDate('completed_at', $today)->where('status', 'completed')->sum('final_price'),
            'active_drivers' => Driver::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->count(),
        ];

        // За неделю
        $weekAgo = now()->subDays(7)->toDateString();
        $statsWeek = [
            'orders_count' => Order::whereDate('created_at', '>=', $weekAgo)->count(),
            'orders_completed' => Order::whereDate('completed_at', '>=', $weekAgo)->where('status', 'completed')->count(),
            'earnings_week' => Order::whereDate('completed_at', '>=', $weekAgo)->where('status', 'completed')->sum('final_price'),
        ];

        // За месяц
        $monthAgo = now()->subDays(30)->toDateString();
        $statsMonth = [
            'orders_count' => Order::whereDate('created_at', '>=', $monthAgo)->count(),
            'orders_completed' => Order::whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed')->count(),
            'earnings_month' => Order::whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed')->sum('final_price'),
        ];

        // Общая статистика
        $statsTotal = [
            'total_orders' => Order::count(),
            'total_completed' => Order::where('status', 'completed')->count(),
            'total_cancelled' => Order::where('status', 'cancelled')->count(),
            'total_earnings' => Order::where('status', 'completed')->sum('final_price'),
            'total_drivers' => Driver::count(),
            'total_passengers' => Passenger::count(),
            'total_active_drivers' => Driver::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->count(),
        ];

        // Заказы по дням за последние 7 дней
        $ordersByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $ordersByDay[] = [
                'date' => now()->subDays($i)->format('d.m'),
                'day_name' => now()->subDays($i)->format('D'),
                'orders' => Order::whereDate('created_at', $date)->count(),
                'completed' => Order::whereDate('completed_at', $date)->where('status', 'completed')->count(),
                'earnings' => Order::whereDate('completed_at', $date)->where('status', 'completed')->sum('final_price'),
            ];
        }

        // Топ водителей за неделю
        $topDrivers = Driver::with(['user:id,first_name,last_name'])
            ->withCount(['orders' => function($q) use ($weekAgo) {
                $q->whereDate('completed_at', '>=', $weekAgo)->where('status', 'completed');
            }])
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get()
            ->map(function($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user ? $driver->user->first_name . ' ' . $driver->user->last_name : 'Водитель #' . $driver->id,
                    'orders_count' => $driver->orders_count,
                ];
            });

        // Статистика для sidebar (сегодня)
        $dispatcherStats = [
            'completed' => $statsToday['orders_completed'],
            'active' => Order::whereIn('status', ['new', 'accepted', 'arrived', 'started'])->count(),
            'revenue' => $statsToday['earnings_today'] ?? 0,
        ];

        return Inertia::render('Dispatcher/Analytics', [
            'stats_today' => $statsToday,
            'stats_week' => $statsWeek,
            'stats_month' => $statsMonth,
            'stats_total' => $statsTotal,
            'orders_by_day' => $ordersByDay,
            'top_drivers' => $topDrivers,
            'dispatcherStats' => $dispatcherStats,
        ]);
    }

    /**
     * Аналитика для админа
     */
    public function admin(Request $request)
    {
        // Статистика за сегодня
        $today = now()->toDateString();
        
        // За сегодня
        $statsToday = [
            'orders_count' => Order::whereDate('created_at', $today)->count(),
            'orders_completed' => Order::whereDate('completed_at', $today)->where('status', 'completed')->count(),
            'orders_cancelled' => Order::whereDate('created_at', $today)->where('status', 'cancelled')->count(),
            'earnings_today' => Order::whereDate('completed_at', $today)->where('status', 'completed')->sum('final_price'),
            'new_passengers' => User::whereDate('created_at', $today)->where('role', 'passenger')->count(),
            'new_drivers' => User::whereDate('created_at', $today)->where('role', 'driver')->count(),
            'active_drivers' => Driver::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->count(),
        ];

        // За неделю
        $weekAgo = now()->subDays(7)->toDateString();
        $statsWeek = [
            'orders_count' => Order::whereDate('created_at', '>=', $weekAgo)->count(),
            'orders_completed' => Order::whereDate('completed_at', '>=', $weekAgo)->where('status', 'completed')->count(),
            'earnings_week' => Order::whereDate('completed_at', '>=', $weekAgo)->where('status', 'completed')->sum('final_price'),
            'new_passengers' => User::whereDate('created_at', '>=', $weekAgo)->where('role', 'passenger')->count(),
            'new_drivers' => User::whereDate('created_at', '>=', $weekAgo)->where('role', 'driver')->count(),
        ];

        // За месяц
        $monthAgo = now()->subDays(30)->toDateString();
        $statsMonth = [
            'orders_count' => Order::whereDate('created_at', '>=', $monthAgo)->count(),
            'orders_completed' => Order::whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed')->count(),
            'earnings_month' => Order::whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed')->sum('final_price'),
        ];

        // Общая статистика
        $statsTotal = [
            'total_orders' => Order::count(),
            'total_completed' => Order::where('status', 'completed')->count(),
            'total_cancelled' => Order::where('status', 'cancelled')->count(),
            'total_earnings' => Order::where('status', 'completed')->sum('final_price'),
            'total_drivers' => Driver::count(),
            'total_passengers' => Passenger::count(),
            'total_users' => User::count(),
            'total_active_drivers' => Driver::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->count(),
        ];

        // Заказы по дням за последние 30 дней
        $ordersByDay = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $ordersByDay[] = [
                'date' => now()->subDays($i)->format('d.m'),
                'orders' => Order::whereDate('created_at', $date)->count(),
                'completed' => Order::whereDate('completed_at', $date)->where('status', 'completed')->count(),
                'earnings' => Order::whereDate('completed_at', $date)->where('status', 'completed')->sum('final_price'),
            ];
        }

        // Топ водителей за месяц
        $topDrivers = Driver::with(['user:id,first_name,last_name'])
            ->withCount(['orders' => function($q) use ($monthAgo) {
                $q->whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed');
            }])
            ->orderByDesc('orders_count')
            ->limit(10)
            ->get()
            ->map(function($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user ? $driver->user->first_name . ' ' . $driver->user->last_name : 'Водитель #' . $driver->id,
                    'orders_count' => $driver->orders_count,
                ];
            });

        // Статистика по тарифам
        $tariffsStats = Order::where('status', 'completed')
            ->whereDate('completed_at', '>=', $monthAgo)
            ->selectRaw('tariff_id, COUNT(*) as count, SUM(final_price) as earnings')
            ->groupBy('tariff_id')
            ->get();

        return Inertia::render('Admin/Analytics', [
            'stats_today' => $statsToday,
            'stats_week' => $statsWeek,
            'stats_month' => $statsMonth,
            'stats_total' => $statsTotal,
            'orders_by_day' => $ordersByDay,
            'top_drivers' => $topDrivers,
            'tariffs_stats' => $tariffsStats,
        ]);
    }
}
