<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PassengerOrderController extends Controller
{
    /**
     * Получить активный заказ пассажира
     */
    public function activeOrder(Request $request)
    {
        try {
            $user = Auth::user();

            // Ищем существующий профиль пассажира
            $passenger = \App\Models\Passenger::where('user_id', $user->id)->first();

            if (!$passenger) {
                return response()->json([
                    'has_active_order' => false,
                    'order' => null,
                    'debug' => 'passenger not found'
                ]);
            }

            // Получаем активный заказ (new, accepted, arrived, in_transit, started, completed, cancelled)
            $order = Order::where('passenger_id', $passenger->id)
                ->whereIn('status', ['new', 'accepted', 'arrived', 'in_transit', 'started', 'completed', 'cancelled'])
                ->with([
                    'driver:id,user_id,is_available',
                    'driver.user:id,first_name,last_name,phone',
                    'driver.car:id,driver_id,brand,model,color,plate_number,is_primary',
                    'tariff:id,name,code'
                ])
                ->orderByDesc('created_at')
                ->first();

            // Отладка
            \Illuminate\Support\Facades\Log::info('Passenger activeOrder debug', [
                'user_id' => $user->id,
                'passenger_id' => $passenger->id,
                'order_found' => $order ? true : false,
                'order_id' => $order?->id,
                'order_status' => $order?->status,
                'order_passenger_id' => $order?->passenger_id,
            ]);

        if (!$order) {
            return response()->json([
                'has_active_order' => false,
                'order' => null,
                'debug' => 'no active order'
            ]);
        }

        // Проверяем наличие свободных водителей
        $freeDriversCount = Driver::where('is_available', true)
            ->where('can_accept_orders', true)
            ->count();

        return response()->json([
            'has_active_order' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'cancelled_by' => $order->cancelled_by,
                'cancellation_reason' => $order->cancellation_reason,
                'pickup_address' => $order->pickup_address,
                'dropoff_address' => $order->dropoff_address,
                'distance' => $order->distance,
                'duration' => $order->duration,
                'final_price' => $order->final_price,
                'tariff' => $order->tariff->name ?? 'Эконом',
                'tariff_id' => $order->tariff_id,
                'notes' => $order->notes,
                'driver' => $order->driver ? [
                    'name' => $order->driver->user->first_name . ' ' . $order->driver->user->last_name,
                    'phone' => $order->driver->user->phone,
                    'car' => $order->driver->car ? [
                        'brand' => $order->driver->car->brand,
                        'model' => $order->driver->car->model,
                        'color' => $order->driver->car->color,
                        'license_plate' => $order->driver->car->plate_number,
                    ] : null,
                ] : null,
                'created_at' => $order->created_at,
                'completed_at' => $order->completed_at,
            ],
            'free_drivers_count' => $freeDriversCount,
        ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Passenger activeOrder error: ' . $e->getMessage());
            return response()->json([
                'has_active_order' => false,
                'order' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отменить заказ
     */
    public function cancelOrder(Request $request, Order $order)
    {
        $user = Auth::user();
        
        // Получаем passenger_id из заказа
        $orderPassengerId = $order->passenger_id;
        
        // Ищем профиль пассажира по user_id
        $passenger = \App\Models\Passenger::where('user_id', $user->id)->first();
        
        // Также пробуем найти по ID из заказа (если есть)
        $passengerByOrderId = $orderPassengerId ? \App\Models\Passenger::find($orderPassengerId) : null;
        
        \Illuminate\Support\Facades\Log::info('CancelOrder debug', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'order_passenger_id' => $orderPassengerId,
            'passenger_found' => $passenger ? $passenger->id : null,
            'passenger_by_order_id' => $passengerByOrderId ? $passengerByOrderId->user_id : null,
        ]);

        // Проверяем: либо passenger совпадает с user, либо заказ принадлежит этому user
        $hasAccess = false;
        
        if ($passenger && $passenger->id == $orderPassengerId) {
            $hasAccess = true;
        } elseif ($passengerByOrderId && $passengerByOrderId->user_id == $user->id) {
            $hasAccess = true;
        } elseif (!$orderPassengerId) {
            // Если passenger_id в заказе null - разрешаем отмену (создадим профиль)
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            return response()->json([
                'error' => 'Заказ не принадлежит этому пользователю',
                'debug' => [
                    'order_passenger_id' => $orderPassengerId,
                    'passenger_id' => $passenger?->id,
                    'passenger_user_id' => $passengerByOrderId?->user_id,
                    'current_user_id' => $user->id
                ]
            ], 403);
        }

        if (!in_array($order->status, ['new', 'accepted'])) {
            return response()->json(['error' => 'Заказ нельзя отменить'], 400);
        }

        // Обновляем статус и информацию об отмене
        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => 'passenger',
            'cancellation_reason' => $request->reason ?? 'Отменено пассажиром',
        ]);

        // Если заказ был принят водителем - освобождаем его
        if ($order->driver_id) {
            \App\Models\Driver::where('id', $order->driver_id)->update([
                'can_accept_orders' => true,
                'is_online' => true,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Заказ отменён']);
    }
}
