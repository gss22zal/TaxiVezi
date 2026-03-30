<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Setting;
use App\Models\Order;
use App\Models\Driver;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Настройки API (время повторения запросов)
        $apiSettings = [
            'orders_repeat_time' => (int) Setting::get('api.orders_repeat_time', 5000),
            'driver_repeat_time' => (int) Setting::get('api.driver_repeat_time', 3000),
            'order_stats_repeat_time' => (int) Setting::get('api.order_stats_repeat_time', 30000),
        ];

        // Статистика для диспетчера и админа (sidebar)
        $dispatcherStats = null;
        $driverStats = null;

        if ($user && in_array($user->role, ['dispatcher', 'admin'])) {
            $today = now()->toDateString();

            // Статистика заказов для sidebar
            $dispatcherStats = [
                'completed' => Order::whereDate('completed_at', $today)
                    ->where('status', 'completed')
                    ->count(),
                'active' => Order::whereIn('status', ['new', 'accepted', 'arrived', 'started'])
                    ->count(),
                'revenue' => (float) Order::whereDate('completed_at', $today)
                    ->where('status', 'completed')
                    ->sum('final_price'),
            ];

            // Статистика водителей для sidebar
            $totalDrivers = Driver::count();
            $onlineDrivers = Driver::whereHas('user', fn($q) => $q->where('is_active', true))->count();
            $busyDrivers = Driver::where('status', 'busy')->count();
            $freeDrivers = $onlineDrivers - $busyDrivers;

            $driverStats = [
                'total' => $totalDrivers,
                'online' => $onlineDrivers,
                'busy' => $busyDrivers,
                'free' => max(0, $freeDrivers),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'settings' => [
                'api' => $apiSettings,
            ],
            // Статистика для sidebar диспетчера/админа
            'dispatcherStats' => $dispatcherStats,
            'driverStats' => $driverStats,
        ];
    }
}
