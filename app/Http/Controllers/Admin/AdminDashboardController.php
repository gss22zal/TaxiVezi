<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Driver;
use App\Models\Passenger;
use App\Models\Tariff;
use App\Models\Setting;
use App\Models\DailyRevenue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    /**
     * Дашборд администратора
     */
    public function index(Request $request)
    {
        // Получаем часовой пояс из настроек (по умолчанию Europe/Moscow)
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        
        // Создаём объект Carbon с учётом часового пояса
        $now = Carbon::now($timezone);
        $today = $now->toDateString();
        $weekAgo = $now->copy()->subDays(7)->toDateString();
        $monthAgo = $now->copy()->subDays(30)->toDateString();

        // Статистика за сегодня
        $statsToday = [
            'orders_count' => Order::whereDate('created_at', $today)->count(),
            'orders_completed' => Order::whereDate('completed_at', $today)->where('status', 'completed')->count(),
            'orders_cancelled' => Order::whereDate('created_at', $today)->where('status', 'cancelled')->count(),
            'earnings_today' => (float) Order::whereDate('completed_at', $today)->where('status', 'completed')->sum('final_price'),
            'new_passengers' => User::whereDate('created_at', $today)->where('role', 'passenger')->count(),
            'new_drivers' => User::whereDate('created_at', $today)->where('role', 'driver')->count(),
            'active_drivers' => Driver::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->where('is_online', true)->count(),
            'total_drivers' => Driver::count(),
        ];

        // Статистика за неделю
        $statsWeek = [
            'orders_count' => Order::whereDate('created_at', '>=', $weekAgo)->count(),
            'orders_completed' => Order::whereDate('completed_at', '>=', $weekAgo)->where('status', 'completed')->count(),
            'earnings_week' => (float) Order::whereDate('completed_at', '>=', $weekAgo)->where('status', 'completed')->sum('final_price'),
        ];

        // Статистика за месяц
        $statsMonth = [
            'orders_count' => Order::whereDate('created_at', '>=', $monthAgo)->count(),
            'orders_completed' => Order::whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed')->count(),
            'earnings_month' => (float) Order::whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed')->sum('final_price'),
        ];

        // Общая статистика
        $statsTotal = [
            'total_orders' => Order::count(),
            'total_completed' => Order::where('status', 'completed')->count(),
            'total_cancelled' => Order::where('status', 'cancelled')->count(),
            'total_earnings' => (float) Order::where('status', 'completed')->sum('final_price'),
            'total_drivers' => Driver::count(),
            'total_passengers' => Passenger::count(),
            'total_users' => User::count(),
            'total_active_drivers' => Driver::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->where('is_online', true)->count(),
        ];

        // Выручка по дням за последние 7 дней (из БД)
        
        // Обновляем данные за сегодня (чтобы они были актуальны)
        DailyRevenue::updateForDate($today);
        
        // Получаем все данные за последние 7 дней
        $dailyRevenues = DailyRevenue::getLastDays(7);
        
        // Создаём карту дат для быстрого поиска
        $revenueMap = [];
        foreach ($dailyRevenues as $dr) {
            $drDate = $dr->date;
            if ($drDate instanceof \Carbon\Carbon) {
                $drDate = $drDate->format('Y-m-d');
            }
            $revenueMap[$drDate] = (float) $dr->revenue;
        }
        
        // Создаём массив дат для последних 7 дней
        $revenueChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $dayName = Carbon::parse($date)->locale('ru')->isoFormat('ddd');
            
            $revenueChart[] = [
                'day' => $dayName,
                'value' => $revenueMap[$date] ?? 0,
            ];
        }

        // Заказы по часам за сегодня
        $ordersChart = [];
        for ($hour = 6; $hour <= 22; $hour += 2) {
            $count = Order::whereDate('created_at', $today)
                ->whereRaw("DATEPART(HOUR, created_at) = ?", [$hour])
                ->count();
            $ordersChart[] = [
                'hour' => (string) $hour,
                'value' => $count,
            ];
        }

        // Активные заказы
        $activeOrders = Order::whereIn('status', ['new', 'accepted', 'arrived', 'started', 'in_transit'])
            ->count();

        // Процент выполнения
        $totalOrdersThisMonth = Order::whereDate('created_at', '>=', $monthAgo)->count();
        $completedOrdersThisMonth = Order::whereDate('completed_at', '>=', $monthAgo)->where('status', 'completed')->count();
        $completionRate = $totalOrdersThisMonth > 0 ? round(($completedOrdersThisMonth / $totalOrdersThisMonth) * 100, 1) : 0;

        // Распределение по тарифам
        $tariffs = Tariff::active()->get();
        $totalCompletedWithTariff = Order::where('status', 'completed')
            ->whereNotNull('tariff_id')
            ->count();

        $classDistribution = $tariffs->map(function ($tariff) use ($totalCompletedWithTariff) {
            $count = Order::where('status', 'completed')
                ->where('tariff_id', $tariff->id)
                ->count();
            $percent = $totalCompletedWithTariff > 0 ? round(($count / $totalCompletedWithTariff) * 100) : 0;
            
            return [
                'name' => $tariff->name,
                'percent' => $percent,
                'count' => $count,
                'color' => match($tariff->code) {
                    'econom' => 'bg-gray-600',
                    'comfort' => 'bg-blue-600',
                    'business' => 'bg-yellow-600',
                    default => 'bg-gray-600',
                },
            ];
        })->toArray();

        // Недавняя активность (последние заказы)
        $recentOrders = Order::with(['passenger.user', 'driver.user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($order) use ($timezone) {
                $passengerName = $order->passenger?->user?->first_name 
                    ?? $order->passenger?->user?->name 
                    ?? 'Пассажир';
                $driverName = $order->driver?->user?->first_name 
                    ?? $order->driver?->user?->name 
                    ?? null;
                
                $text = match($order->status) {
                    'completed' => 'Заказ ' . $order->order_number . ' завершён',
                    'cancelled' => 'Заказ ' . $order->order_number . ' отменён',
                    'accepted' => 'Заказ ' . $order->order_number . ' принят' . ($driverName ? ' (' . $driverName . ')' : ''),
                    'arrived' => 'Водитель прибыл: ' . $order->order_number,
                    'started' => 'Поездка начата: ' . $order->order_number,
                    default => 'Новый заказ ' . $order->order_number,
                };

                // Конвертируем время в часовой пояс из настроек для корректного отображения
                $createdAt = $order->created_at->timezone($timezone);

                return [
                    'type' => $order->status === 'completed' ? 'order' : 
                             ($order->status === 'cancelled' ? 'alert' : 'order'),
                    'text' => $text,
                    'time' => $createdAt->format('d.m.y H:i'),
                ];
            });

        // Системный статус
        $systemStatus = [
            'server' => true,
            'database' => true,
            'api' => true,
            'connections' => User::count(), // Общее количество пользователей
        ];

        // Статистика заказов для меню (такая же как в AppServiceProvider)
        $orderStats = [
            'new' => Order::where('status', 'new')->count(),
            'accepted' => Order::where('status', 'accepted')->count(),
            'in_progress' => Order::whereIn('status', ['accepted', 'arrived', 'started'])->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'orderStats' => $orderStats,
            'stats_today' => $statsToday,
            'stats_week' => $statsWeek,
            'stats_month' => $statsMonth,
            'stats_total' => $statsTotal,
            'revenue_chart' => $revenueChart,
            'orders_chart' => $ordersChart,
            'active_orders' => $activeOrders,
            'completion_rate' => $completionRate,
            'class_distribution' => $classDistribution,
            'recent_activity' => $recentOrders,
            'system_status' => $systemStatus,
        ]);
    }
}
