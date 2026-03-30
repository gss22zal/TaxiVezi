<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvailableOrdersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'driver') {
            Log::info('AvailableOrders: user not driver', ['user_id' => $user?->id, 'role' => $user?->role]);
            return response()->json([]);
        }
        
        // Получаем водителя
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            Log::info('AvailableOrders: driver not found', ['user_id' => $user->id]);
            return response()->json([]);
        }
        
        Log::info('AvailableOrders debug', [
            'driver_id' => $driver->id,
            'is_online' => $driver->is_online,
            'can_accept_orders' => $driver->can_accept_orders,
        ]);

        // Проверяем, что водитель онлайн и может принимать заказы
        if (!$driver->is_online || !$driver->can_accept_orders) {
            Log::info('AvailableOrders: driver not available', [
                'is_online' => $driver->is_online,
                'can_accept_orders' => $driver->can_accept_orders
            ]);
            return response()->json([]);
        }
        
        // Получаем настройки
        $ordersLimit = (int) Setting::get('api.available_orders_limit', 10);
        $ordersHours = (int) Setting::get('api.available_orders_hours', 2);
        
        // Получаем новые заказы (только new, без водителя)
        // Используем DATEADD для SQL Server
        $orders = Order::where('status', 'new')
            ->whereNull('driver_id')
            ->whereRaw("created_at >= DATEADD(hour, ?, GETDATE())", [-$ordersHours])
            ->orderBy('created_at', 'desc')
            ->limit($ordersLimit)
            ->get(['id', 'order_number', 'pickup_address', 'dropoff_address', 
                   'distance', 'final_price', 'created_at', 'status']);
        
        Log::info('AvailableOrders: found orders', ['count' => $orders->count()]);

        return response()->json($orders);
    }
}
