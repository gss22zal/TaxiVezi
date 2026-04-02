<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PassengerOrderHideController extends Controller
{
    /**
     * Скрыть заказ из истории пассажира
     */
    public function __invoke(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'passenger') {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $passenger = \App\Models\Passenger::where('user_id', $user->id)->first();
        
        if (!$passenger) {
            return response()->json(['message' => 'Профиль пассажира не найден'], 404);
        }

        // Проверяем, что заказ принадлежит этому пассажиру
        if (intval($order->passenger_id) !== intval($passenger->id)) {
            return response()->json([
                'message' => 'Заказ не принадлежит вам',
                'debug' => [
                    'order_passenger_id' => $order->passenger_id,
                    'passenger_id' => $passenger->id,
                    'order_id' => $order->id
                ]
            ], 403);
        }

        // Проверяем, что заказ уже завершён или отменён
        if (!in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'Можно скрыть только завершённые или отменённые заказы'], 400);
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
