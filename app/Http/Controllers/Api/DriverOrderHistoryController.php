<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverOrderHistoryController extends Controller
{
    /**
     * Получить историю завершённых заказов водителя
     */
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'driver') {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json(['message' => 'Профиль водителя не найден'], 404);
        }

        $perPage = 10;
        $page = $request->input('page', 1);

        // Получаем статистику за последние 24 часа (для учёта часового пояса)
        $yesterday = now()->subHours(24);
        
        $stats = [
            'today_trips' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $yesterday)
                ->count(),
            'today_earnings' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $yesterday)
                ->sum('driver_earnings'),
            'today_distance' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $yesterday)
                ->sum('distance'),
        ];

        // Получаем завершённые заказы водителя с пагинацией
        $query = Order::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->where('is_hidden', false)
            ->orderByDesc('completed_at');

        $total = $query->count();
        $orders = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'pickup_address' => $order->pickup_address,
                    'dropoff_address' => $order->dropoff_address,
                    'distance' => $order->distance,
                    'duration' => $order->duration,
                    'final_price' => $order->final_price,
                    'driver_earnings' => $order->driver_earnings,
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'passenger_name' => $order->passenger_name,
                ];
            });

        $pagination = [
            'current_page' => (int) $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ];

        return response()->json([
            'orders' => $orders,
            'stats' => $stats,
            'pagination' => $pagination,
        ]);
    }
}
