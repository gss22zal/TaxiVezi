<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcceptOrderController extends Controller
{
    public function __invoke(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'driver') {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }
        
        // Получаем водителя
        $driver = Driver::where('user_id', $user->id)->first();
        
        // ОТЛАДКА
        Log::info('AcceptOrder: user_id=' . $user->id . ', role=' . $user->role . ', driver_found=' . ($driver ? $driver->id : 'NULL'));
        
        if (!$driver) {
            return response()->json([
                'message' => 'Водитель не найден (user_id: ' . $user->id . ')',
                'debug' => ['user_id' => $user->id, 'role' => $user->role]
            ], 404);
        }
        
        // Проверяем, что водитель онлайн
        if (!$driver->is_online) {
            return response()->json(['message' => 'Вы офлайн. Перейдите в онлайн для приема заказов'], 400);
        }
        
        // Проверяем статус заказа
        if (!in_array($order->status, ['new', 'accepted'])) {
            return response()->json(['message' => 'Заказ уже недоступен'], 400);
        }
        
        // Проверяем, что заказ не принят другим водителем (приводим к одному типу)
        if ($order->driver_id && intval($order->driver_id) !== intval($driver->id)) {
            return response()->json(['message' => 'Заказ уже принят другим водителем'], 400);
        }
        
        // Используем сырой SQL запрос для обновления с GETDATE()
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'driver_id' => $driver->id,
                'status' => 'accepted',
                'accepted_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ]);
        
        // Делаем водителя занятым
        DB::table('drivers')
            ->where('id', $driver->id)
            ->update([
                'is_online' => true,
                'can_accept_orders' => false,
                'updated_at' => DB::raw("GETDATE()"),
            ]);
        
        // Обновляем локальную модель
        $order->refresh();
        
        return response()->json([
            'message' => 'Заказ принят',
            'debug' => [
                'user_id' => $user->id,
                'role' => $user->role,
                'driver_id' => $driver->id,
                'driver_user_id' => $driver->user_id
            ],
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'pickup_address' => $order->pickup_address,
                'dropoff_address' => $order->dropoff_address,
                'distance' => $order->distance,
                'final_price' => $order->final_price,
                'passenger_name' => $order->passenger?->user?->name ?? 'Пассажир',
                'passenger_phone' => $order->passenger_phone,
            ]
        ]);
    }
}
