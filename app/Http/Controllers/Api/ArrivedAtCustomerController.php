<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArrivedAtCustomerController extends Controller
{
    public function __invoke(Request $request, Order $order)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'driver') {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Получаем водителя
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json(['message' => 'Водитель не найден'], 404);
        }

        // Проверяем, что заказ принадлежит этому водителю
        if (intval($order->driver_id) !== intval($driver->id)) {
            return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
        }

        // Проверяем статус заказа
        if (!in_array($order->status, ['accepted'])) {
            return response()->json([
                'message' => 'Нельзя отметить прибытие. Статус заказа: ' . $order->status
            ], 400);
        }

        // Обновляем заказ - статус "прибыл"
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'status' => 'arrived',
                'arrived_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ]);

        $order->refresh();

        \Illuminate\Support\Facades\Log::info('Driver arrived at customer', [
            'order_id' => $order->id,
            'order_status' => $order->status,
        ]);

        return response()->json([
            'message' => 'Водитель прибыл',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => 'arrived',
                'arrived_at' => $order->arrived_at,
            ]
        ]);
    }
}
