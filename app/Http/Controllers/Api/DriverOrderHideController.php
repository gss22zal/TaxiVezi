<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverOrderHideController extends Controller
{
    /**
     * Скрыть заказ из истории водителя
     */
    public function __invoke(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'driver') {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $driver = \App\Models\Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Профиль водителя не найден'], 404);
        }

        // Проверяем, что заказ принадлежит этому водителю
        if (intval($order->driver_id) !== intval($driver->id)) {
            return response()->json([
                'message' => 'Заказ не принадлежит вам',
                'debug' => [
                    'order_driver_id' => $order->driver_id,
                    'driver_id' => $driver->id,
                    'order_id' => $order->id
                ]
            ], 403);
        }

        // Проверяем, что заказ завершён
        if ($order->status !== 'completed') {
            return response()->json(['message' => 'Можно скрыть только завершённые заказы'], 400);
        }

        // Скрываем заказ
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'is_hidden' => true,
                'hidden_at' => DB::raw("GETDATE()"),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Заказ скрыт из истории',
        ]);
    }
}
