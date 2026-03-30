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

        // Получаем завершённые заказы водителя
        $orders = Order::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(50)
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

        // Получаем статистику
        $stats = [
            'total_trips' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->count(),
            'total_earnings' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->sum('driver_earnings'),
            'total_distance' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->sum('distance'),
            'today_trips' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'today_earnings' => Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('driver_earnings'),
        ];

        return response()->json([
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }
}
