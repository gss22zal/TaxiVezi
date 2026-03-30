<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminOrdersController extends Controller
{
    /**
     * Страница заказов
     */
    public function index(Request $request)
    {
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

        $orders = $query->orderByDesc('id')->paginate(15);

        // Статистика
        $stats = [
            'total' => Order::count(),
            'new' => Order::where('status', 'new')->count(),
            'in_progress' => Order::whereIn('status', ['accepted', 'arrived', 'started'])->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'today_completed' => Order::where('status', 'completed')
                ->whereDate('completed_at', today())->count(),
            'today_earnings' => Order::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('final_price'),
            'month_earnings' => Order::where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
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
            ],
            'stats' => $stats,
        ]);
    }
}