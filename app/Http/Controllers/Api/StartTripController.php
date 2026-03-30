<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StartTripController extends Controller
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

        // Проверяем статус заказа - можно начать поездку если принят или прибыл
        if (!in_array($order->status, ['accepted', 'arrived'])) {
            return response()->json([
                'message' => 'Нельзя начать поездку. Статус заказа: ' . $order->status
            ], 400);
        }

        // Обновляем заказ - статус "в пути"
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'status' => 'in_transit',
                'started_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ]);

        $order->refresh();

        return response()->json([
            'message' => 'Поездка начата',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => 'in_transit',
                'started_at' => $order->started_at,
            ]
        ]);
    }
}
