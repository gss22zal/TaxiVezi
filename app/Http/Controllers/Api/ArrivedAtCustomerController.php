<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Setting;
use Carbon\Carbon;
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

        // Получаем время с учётом часового пояса
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        $now = Carbon::now($timezone)->format('Y-m-d H:i:s.v');

        // Обновляем заказ - статус "прибыл"
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'status' => 'arrived',
                'arrived_at' => DB::raw("CAST('$now' AS DATETIME2)"),
                'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
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
