<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverActiveOrderController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'driver') {
            return response()->json(['order' => null]);
        }
        
        // Получаем водителя
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['order' => null]);
        }
        
        // Ищем активный заказ водителя (в процессе или завершённый/отменённый)
        $order = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['accepted', 'arrived', 'in_transit', 'started', 'in_progress', 'completed', 'cancelled'])
            ->orderByDesc('created_at')
            ->first();
        
        // Если заказ не найден - возвращаем null
        if (!$order) {
            return response()->json(['order' => null]);
        }
        
        // Если заказ завершён - возвращаем его с completed_at для отображения
        if ($order->status === 'completed') {
            return response()->json([
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'pickup_address' => $order->pickup_address,
                    'dropoff_address' => $order->dropoff_address,
                    'distance' => $order->distance,
                    'final_price' => $order->final_price,
                    'status' => 'completed',
                    'completed_at' => $order->completed_at,
                    'passenger_name' => $order->passenger_name,
                    'passenger_phone' => $order->passenger_phone,
                ]
            ]);
        }

        // Если заказ отменён - возвращаем информацию об отмене
        if ($order->status === 'cancelled') {
            return response()->json([
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => 'cancelled',
                    'cancelled_by' => $order->cancelled_by,
                    'cancellation_reason' => $order->cancellation_reason,
                ]
            ]);
        }

        // Активный заказ (в процессе)
        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'pickup_address' => $order->pickup_address,
                'dropoff_address' => $order->dropoff_address,
                'distance' => $order->distance,
                'final_price' => $order->final_price,
                'status' => $order->status,
                'cancelled_by' => $order->cancelled_by,
                'cancellation_reason' => $order->cancellation_reason,
                'passenger_name' => $order->passenger_name,
                'passenger_phone' => $order->passenger_phone,
            ]
        ]);
    }
}
