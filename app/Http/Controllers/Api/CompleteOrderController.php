<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompleteOrderController extends Controller
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
            return response()->json(['message' => 'Водитель не найден (user_id: ' . $user->id . ')'], 404);
        }
        
        // ДЛЯ ОТЛАДКИ
        Log::info('CompleteOrder: order_id=' . $order->id . ', order_driver_id=' . $order->driver_id . ', driver_id=' . $driver->id);
        
        // Проверяем, что заказ принадлежит этому водителю (приводим к одному типу)
        if (intval($order->driver_id) !== intval($driver->id)) {
            return response()->json([
                'message' => 'Заказ не принадлежит вам',
                'debug' => [
                    'order_id' => $order->id,
                    'order_driver_id' => $order->driver_id,
                    'driver_id' => $driver->id,
                    'driver_user_id' => $driver->user_id
                ]
            ], 403);
        }
        
        // Проверяем статус заказа
        if (!in_array($order->status, ['accepted', 'arrived', 'in_transit', 'started', 'in_progress'])) {
            return response()->json([
                'message' => 'Заказ нельзя завершить',
                'debug' => ['current_status' => $order->status]
            ], 400);
        }
        
        // Рассчитываем driver_earnings, если ещё не рассчитано
        $driverEarnings = $order->driver_earnings;
        $commissionAmount = $order->commission_amount;
        
        if ($driverEarnings === null && $order->final_price) {
            // Получаем процент комиссии из настроек
            $commissionPercent = \App\Models\Setting::get('commission_percent', 10);
            $commissionAmount = round($order->final_price * ($commissionPercent / 100), 2);
            $driverEarnings = round($order->final_price - $commissionAmount, 2);
        }
        
        // Обновляем заказ
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'status' => 'completed',
                'completed_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
                'driver_earnings' => $driverEarnings,
                'commission_amount' => $commissionAmount,
            ]);
        
        // Обновляем водителя - делаем свободным
        DB::table('drivers')
            ->where('id', $driver->id)
            ->update([
                'is_online' => true,
                'can_accept_orders' => true,
                'total_rides' => DB::raw('total_rides + 1'),
                'updated_at' => DB::raw("GETDATE()"),
            ]);
        
        // Обновляем количество поездок
        $order->refresh();
        
        return response()->json([
            'message' => 'Заказ завершен',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => 'completed',
                'completed_at' => $order->completed_at,
                'final_price' => $order->final_price,
                'driver_earnings' => $order->driver_earnings,
                'distance' => $order->distance,
            ],
            'driver' => [
                'id' => $driver->id,
                'is_available' => true,
            ]
        ]);
    }
}
