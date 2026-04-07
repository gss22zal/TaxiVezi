<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminOrdersController extends Controller
{
    /**
     * Страница заказов
     */
    public function index(Request $request)
    {
        // Получаем часовой пояс из настроек
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        $now = Carbon::now($timezone);
        
        $query = Order::with([
            'passenger:id,user_id',
            'passenger.user:id,first_name,last_name,phone',
            'driver:id,user_id',
            'driver.user:id,first_name,last_name',
            'review:id,order_id,passenger_rating,driver_rating,passenger_comment,driver_comment,passenger_tags,driver_tags',
            'review.driver:id,user_id',
            'review.driver.user:id,first_name,last_name',
            'review.passenger:id,user_id',
            'review.passenger.user:id,first_name,last_name',
        ]);

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('dropoff_address', 'like', "%{$search}%");
            });
        }

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Фильтр по дате
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->orderByDesc('id')->paginate(15);

        // Статистика (с учётом фильтров даты)
        $statsQuery = Order::query();
        if ($dateFrom) {
            $statsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $statsQuery->whereDate('created_at', '<=', $dateTo);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'all' => (clone $statsQuery)->count(),
            'new' => (clone $statsQuery)->where('status', 'new')->count(),
            'accepted' => (clone $statsQuery)->where('status', 'accepted')->count(),
            'arrived' => (clone $statsQuery)->where('status', 'arrived')->count(),
            'in_transit' => (clone $statsQuery)->where('status', 'in_transit')->count(),
            'in_progress' => (clone $statsQuery)->whereIn('status', ['accepted', 'arrived', 'started'])->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $statsQuery)->where('status', 'cancelled')->count(),
            'today_completed' => Order::where('status', 'completed')
                ->whereDate('completed_at', $now->toDateString())->count(),
            'today_earnings' => Order::where('status', 'completed')
                ->whereDate('completed_at', $now->toDateString())
                ->sum('final_price'),
            'month_earnings' => Order::where('status', 'completed')
                ->whereMonth('completed_at', $now->month)
                ->whereYear('completed_at', $now->year)
                ->sum('final_price'),
        ];

        return Inertia::render('Admin/Orders', [
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
            'serverDate' => $now->toDateString(),
            'stats' => $stats,
            'orderStats' => [
                'new' => Order::where('status', 'new')->count(),
                'accepted' => Order::where('status', 'accepted')->count(),
                'arrived' => Order::where('status', 'arrived')->count(),
                'in_transit' => Order::where('status', 'in_transit')->count(),
            ],
        ]);
    }
}